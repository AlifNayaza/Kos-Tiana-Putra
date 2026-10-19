<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class PredictStockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stok:prediksi';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Jalankan model AI Machine Learning (Python) untuk memprediksi kebutuhan stok warkop & galon 7 hari ke depan (FR-13)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('================================================================');
        $this->info('  MEMULAI PROSES PREDIKSI KEBUTUHAN STOK BERBASIS PYTHON (ML)   ');
        $this->info('  Kos Tiana Putra 1 - Skrip Eksternal: python-model/predict.py   ');
        $this->info('================================================================');

        $scriptPath = base_path('python-model/predict.py');

        if (!file_exists($scriptPath)) {
            $this->error("File skrip Python tidak ditemukan di: {$scriptPath}");
            return Command::FAILURE;
        }

        // Cari executable Python yang tersedia (python, python3, py)
        $pythonBinary = $this->detectPythonBinary();

        $this->line("Executable Python terdeteksi: <comment>{$pythonBinary}</comment>");
        $this->line("Menjalankan analisis regresi & proyeksi konsumsi stok...");

        $process = new Process([$pythonBinary, $scriptPath], base_path(), [
            'DB_HOST' => config('database.connections.mysql.host', '127.0.0.1'),
            'DB_PORT' => (string) config('database.connections.mysql.port', '3306'),
            'DB_DATABASE' => config('database.connections.mysql.database', 'kos_tiana_putra'),
            'DB_USERNAME' => config('database.connections.mysql.username', 'root'),
            'DB_PASSWORD' => (string) (config('database.connections.mysql.password') ?? ''),
        ]);
        $process->setTimeout(60);

        try {
            $process->run(function ($type, $buffer) {
                if ($type === Process::ERR) {
                    $this->output->write("<fg=yellow>{$buffer}</>");
                } else {
                    $this->output->write($buffer);
                }
            });

            if (!$process->isSuccessful()) {
                $err = trim($process->getErrorOutput() ?: $process->getOutput());
                $this->error("Gagal mengeksekusi model prediksi Python: {$err}");
                if (str_contains($err, '2003') || str_contains($err, 'Gagal terhubung ke MySQL') || str_contains($err, 'Connection refused')) {
                    $this->warn("TIPS: Pastikan service MySQL/MariaDB (port 3306) sudah aktif.");
                }
                return Command::FAILURE;
            }

            $this->newLine();
            $this->info('✓ Prediksi stok berhasil diperbarui dan tersimpan di database MySQL!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Terjadi kesalahan saat memanggil skrip Python: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Deteksi executable python yang valid dan memiliki package pandas, sklearn & pymysql terpasang.
     */
    private function detectPythonBinary(): string
    {
        $localAppData = getenv('LOCALAPPDATA') ?: '';
        $candidates = [
            $localAppData ? $localAppData . '\Programs\Python\Python312\python.exe' : '',
            'C:\Python312\python.exe',
            'py',
            'python',
            'python3',
        ];

        foreach ($candidates as $bin) {
            if (empty($bin)) {
                continue;
            }

            // Jika berupa path file absolut, pastikan file ada
            if (str_contains($bin, '\\') && !file_exists($bin)) {
                continue;
            }

            // Uji apakah binary ini bisa mengimpor pandas, sklearn, dan pymysql
            $test = new Process([$bin, '-c', 'import pandas, sklearn, pymysql; print("PACKAGES_OK")']);
            try {
                $test->run();
                if ($test->isSuccessful() && str_contains($test->getOutput(), 'PACKAGES_OK')) {
                    return $bin;
                }
            } catch (\Exception) {
                continue;
            }
        }

        // Fallback jika deteksi ketat belum berhasil
        return 'python';
    }
}
