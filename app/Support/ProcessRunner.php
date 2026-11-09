<?php

namespace App\Support;

/**
 * Menjalankan proses eksternal dengan proc_open, tanpa bergantung pada folder temp sistem.
 *
 * Symfony Process di Windows menulis berkas sementara ke sys_get_temp_dir(). Pada server web yang
 * tidak mewarisi variabel TEMP/TMP, lokasi itu menjadi C:\Windows dan penulisan ditolak (Permission denied),
 * sehingga setiap perintah gagal. Di sini output diarahkan ke berkas di storage/ milik aplikasi sendiri.
 */
class ProcessRunner
{
    /**
     * @param  array<int, string>  $command  perintah beserta argumennya
     * @param  array<string, string>  $env  variabel tambahan (digabung dengan lingkungan saat ini)
     * @return array{exit: int, stdout: string, stderr: string, timed_out: bool}
     */
    public static function run(array $command, ?string $cwd = null, array $env = [], int $timeout = 60): array
    {
        $dir = storage_path('framework/cache/process');
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $id = bin2hex(random_bytes(6));
        $outFile = "{$dir}/{$id}.out";
        $errFile = "{$dir}/{$id}.err";

        $result = ['exit' => -1, 'stdout' => '', 'stderr' => '', 'timed_out' => false];

        try {
            $process = @proc_open(
                $command,
                [0 => ['pipe', 'r'], 1 => ['file', $outFile, 'w'], 2 => ['file', $errFile, 'w']],
                $pipes,
                $cwd,
                array_merge(self::currentEnvironment(), $env),
            );

            if (!is_resource($process)) {
                $result['stderr'] = 'Proses tidak dapat dijalankan: ' . implode(' ', $command);

                return $result;
            }

            fclose($pipes[0]);

            $deadline = microtime(true) + $timeout;
            $exitCode = null;

            while (true) {
                $status = proc_get_status($process);

                if (!$status['running']) {
                    // exitcode hanya valid pada pembacaan pertama setelah proses selesai.
                    $exitCode = $status['exitcode'];
                    break;
                }

                if (microtime(true) > $deadline) {
                    proc_terminate($process);
                    $result['timed_out'] = true;
                    break;
                }

                usleep(50_000);
            }

            $closeCode = proc_close($process);
            $result['exit'] = $result['timed_out'] ? -1 : (int) ($exitCode !== null && $exitCode >= 0 ? $exitCode : $closeCode);
        } finally {
            $result['stdout'] = is_file($outFile) ? (string) file_get_contents($outFile) : '';
            $result['stderr'] = is_file($errFile) ? (string) file_get_contents($errFile) : '';
            @unlink($outFile);
            @unlink($errFile);
        }

        return $result;
    }

    /**
     * @return array<string, string>
     */
    private static function currentEnvironment(): array
    {
        $env = getenv();

        return is_array($env) ? $env : [];
    }
}
