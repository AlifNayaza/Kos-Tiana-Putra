<?php

namespace Tests\Unit;

use App\Support\ProcessRunner;
use Tests\TestCase;

class ProcessRunnerTest extends TestCase
{
    public function test_captures_stdout_stderr_and_exit_code(): void
    {
        $result = ProcessRunner::run([PHP_BINARY, '-r', 'fwrite(STDOUT, "halo"); fwrite(STDERR, "peringatan"); exit(3);']);

        $this->assertSame(3, $result['exit']);
        $this->assertSame('halo', $result['stdout']);
        $this->assertSame('peringatan', $result['stderr']);
        $this->assertFalse($result['timed_out']);
    }

    public function test_successful_process_returns_zero(): void
    {
        $result = ProcessRunner::run([PHP_BINARY, '-r', 'echo "ok";']);

        $this->assertSame(0, $result['exit']);
        $this->assertSame('ok', $result['stdout']);
    }

    public function test_passes_extra_environment_variables(): void
    {
        $result = ProcessRunner::run([PHP_BINARY, '-r', 'echo getenv("KOS_UJI");'], null, ['KOS_UJI' => 'nilai-uji']);

        $this->assertSame('nilai-uji', $result['stdout']);
    }

    public function test_times_out_long_running_process(): void
    {
        $result = ProcessRunner::run([PHP_BINARY, '-r', 'sleep(10);'], null, [], 1);

        $this->assertTrue($result['timed_out']);
    }

    public function test_does_not_depend_on_system_temp_dir_and_cleans_up(): void
    {
        ProcessRunner::run([PHP_BINARY, '-r', 'echo "x";']);

        $leftovers = glob(storage_path('framework/cache/process/*')) ?: [];
        $this->assertSame([], $leftovers, 'Berkas output sementara harus dibersihkan.');
    }

    public function test_missing_executable_reports_failure_instead_of_throwing(): void
    {
        $result = ProcessRunner::run(['program-yang-tidak-ada-xyz']);

        $this->assertNotSame(0, $result['exit']);
    }
}
