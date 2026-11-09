<?php

namespace App\Console\Commands;

use App\Support\ProcessRunner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class PredictStockCommand extends Command
{
    protected $signature = 'stok:prediksi {--redetect : Abaikan cache dan deteksi ulang executable Python}';

    protected $description = 'Jalankan model Machine Learning (Python) untuk memprediksi kebutuhan stok warkop & galon 7 hari ke depan (FR-13)';

    private const PYTHON_CACHE_KEY = 'stok_prediksi_python_bin';

    /** @var array<int, string> alasan tiap kandidat Python ditolak (untuk pesan error) */
    private array $detectionLog = [];

    public function handle(): int
    {
        $this->info('Prediksi kebutuhan stok (Python) - ' . config('kos.name'));

        $scriptPath = base_path('python-model/predict.py');

        if (!file_exists($scriptPath)) {
            $this->error("File skrip Python tidak ditemukan di: {$scriptPath}");

            return Command::FAILURE;
        }

        $pythonBinary = $this->resolvePythonBinary();

        if ($pythonBinary === null) {
            $this->error('Python dengan paket pandas, scikit-learn, dan pymysql tidak ditemukan.');
            foreach ($this->detectionLog as $line) {
                $this->line('  - ' . $line);
            }
            $this->warn('Pasang dependensi: pip install -r python-model/requirements.txt');
            $this->warn('Atau isi PYTHON_BIN di .env dengan path python yang benar.');

            return Command::FAILURE;
        }

        $this->line("Executable Python: <comment>{$pythonBinary}</comment>");

        $result = ProcessRunner::run(
            [$pythonBinary, $scriptPath],
            base_path(),
            $this->baseEnvironment() + $this->databaseEnvironment(),
            90,
        );

        if ($result['stdout'] !== '') {
            $this->output->write($result['stdout']);
        }

        if ($result['timed_out'] || $result['exit'] !== 0) {
            // Hasil cache bisa kedaluwarsa (mis. Python dihapus); paksa deteksi ulang di percobaan berikutnya.
            Cache::forget(self::PYTHON_CACHE_KEY);

            $err = $result['timed_out']
                ? 'Proses melewati batas waktu 90 detik.'
                : trim($result['stderr'] ?: $result['stdout']);

            $this->error("Gagal mengeksekusi model prediksi Python: {$err}");

            if (str_contains($err, '2003') || str_contains($err, 'Gagal terhubung ke MySQL') || str_contains($err, 'Connection refused')) {
                $this->warn('TIPS: Pastikan service MySQL/MariaDB (port 3306) sudah aktif.');
            }

            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('Prediksi stok berhasil diperbarui.');

        return Command::SUCCESS;
    }

    /**
     * Koneksi database yang sama dengan aplikasi diteruskan ke skrip Python.
     *
     * @return array<string, string>
     */
    private function databaseEnvironment(): array
    {
        $name = config('database.default');
        $cfg = config("database.connections.{$name}", []);

        $env = ['DB_CONNECTION' => (string) $name];

        if ($name === 'sqlite') {
            $env['DB_DATABASE'] = (string) ($cfg['database'] ?? '');
        } else {
            $env += [
                'DB_HOST' => (string) ($cfg['host'] ?? '127.0.0.1'),
                'DB_PORT' => (string) ($cfg['port'] ?? '3306'),
                'DB_DATABASE' => (string) ($cfg['database'] ?? ''),
                'DB_USERNAME' => (string) ($cfg['username'] ?? 'root'),
                'DB_PASSWORD' => (string) ($cfg['password'] ?? ''),
            ];
        }

        return $env;
    }

    /**
     * Urutan: PYTHON_BIN di .env -> hasil deteksi yang di-cache -> deteksi otomatis.
     * Deteksi menjalankan beberapa proses Python (mengimpor pandas/sklearn), jadi
     * hasilnya disimpan 24 jam agar tombol "Hitung Ulang" tidak lambat.
     */
    private function resolvePythonBinary(): ?string
    {
        $configured = config('kos.python_bin');

        if (!empty($configured)) {
            return $configured;
        }

        if ($this->option('redetect')) {
            Cache::forget(self::PYTHON_CACHE_KEY);
        }

        $cached = Cache::get(self::PYTHON_CACHE_KEY);
        if ($cached) {
            return $cached;
        }

        foreach ($this->candidateBinaries() as $bin) {
            $test = ProcessRunner::run(
                [$bin, '-c', 'import pandas, sklearn, pymysql; print("PACKAGES_OK")'],
                null,
                $this->baseEnvironment(),
                30,
            );

            if (!$test['timed_out'] && $test['exit'] === 0 && str_contains($test['stdout'], 'PACKAGES_OK')) {
                Cache::put(self::PYTHON_CACHE_KEY, $bin, now()->addDay());

                return $bin;
            }

            $lines = array_filter(array_map('trim', preg_split('/\R/', trim($test['stderr']))));
            $this->detectionLog[] = "{$bin}: " . ($test['timed_out'] ? 'timeout' : (end($lines) ?: 'exit ' . $test['exit']));
        }

        return null;
    }

    /**
     * Daftar executable Python yang dicoba, versi terbaru lebih dulu.
     * Tidak bergantung pada PATH/LOCALAPPDATA karena proses server web sering tidak mewarisinya.
     *
     * @return array<int, string>
     */
    private function candidateBinaries(): array
    {
        $candidates = [];

        if (PHP_OS_FAMILY === 'Windows') {
            $patterns = [];

            foreach ([getenv('LOCALAPPDATA'), getenv('USERPROFILE') ? getenv('USERPROFILE') . '\\AppData\\Local' : null] as $localAppData) {
                if ($localAppData) {
                    $patterns[] = $localAppData . '\\Programs\\Python\\Python3*\\python.exe';
                }
            }

            // Pemasangan Python per-pengguna di profil yang sama dengan lokasi proyek (mis. C:\Users\Nama\...).
            if (preg_match('#^[A-Za-z]:[\\\\/]+Users[\\\\/]+[^\\\\/]+#', base_path(), $m)) {
                $patterns[] = $m[0] . '\\AppData\\Local\\Programs\\Python\\Python3*\\python.exe';
            }

            foreach ([getenv('ProgramFiles'), getenv('ProgramW6432'), 'C:\\Program Files'] as $programFiles) {
                if ($programFiles) {
                    $patterns[] = $programFiles . '\\Python3*\\python.exe';
                }
            }
            $patterns[] = 'C:\\Python3*\\python.exe';

            foreach ($patterns as $pattern) {
                $found = glob($pattern) ?: [];
                rsort($found, SORT_NATURAL); // Python312 sebelum Python310
                foreach ($found as $path) {
                    $candidates[] = $path;
                }
            }

            $candidates[] = 'py';

            // "python3" di Windows hanyalah pintasan Microsoft Store; tidak dicoba.
            return array_values(array_unique(array_merge($candidates, ['python'])));
        }

        return ['python3', 'python'];
    }

    /**
     * Variabel lingkungan minimal agar Python dapat berjalan di Windows walau server web
     * tidak mewarisi lingkungan pengguna (tanpa SystemRoot Python gagal start).
     *
     * @return array<string, string>
     */
    private function baseEnvironment(): array
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return [];
        }

        $systemRoot = getenv('SystemRoot') ?: (getenv('windir') ?: 'C:\\Windows');

        return [
            'SystemRoot' => $systemRoot,
            'PATH' => (getenv('PATH') ?: '') . ';' . $systemRoot . '\\System32;' . $systemRoot,
        ];
    }
}
