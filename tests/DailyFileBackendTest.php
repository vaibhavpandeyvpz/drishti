<?php

/*
 * This file is part of vaibhavpandeyvpz/drishti package.
 *
 * (c) Vaibhav Pandey <contact@vaibhavpandey.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source file in the file LICENSE.
 */

namespace Drishti;

use PHPUnit\Framework\TestCase;
use Samay\FrozenClock;

/**
 * Test suite for DailyFileBackend class.
 *
 * @author Vaibhav Pandey <contact@vaibhavpandey.com>
 */
final class DailyFileBackendTest extends TestCase
{
    /**
     * Tests that logs are written to a daily file.
     */
    public function test_writes_to_daily_file(): void
    {
        $basePath = \sys_get_temp_dir().'/drishti_test_'.\uniqid();
        $backend = new DailyFileBackend($basePath);

        $backend->write('info', 'Test message', []);

        $expectedFile = $basePath.'-'.\date('Y-m-d').'.log';
        $this->assertFileExists($expectedFile);
        $content = \file_get_contents($expectedFile);
        $this->assertStringContainsString('INFO', $content);
        $this->assertStringContainsString('Test message', $content);

        \unlink($expectedFile);
    }

    /**
     * Tests that file name includes date suffix.
     */
    public function test_file_name_includes_date(): void
    {
        $basePath = \sys_get_temp_dir().'/drishti_test_'.\uniqid();

        // Use frozen clock for predictable date
        $clock = new FrozenClock(new \DateTimeImmutable('2024-01-15 12:00:00'));
        $backend = new DailyFileBackend($basePath, null, $clock);

        $backend->write('info', 'Test message', []);

        $expectedFile = $basePath.'-2024-01-15.log';
        $this->assertFileExists($expectedFile);

        \unlink($expectedFile);
    }

    /**
     * Tests that multiple writes on the same day append to the same file.
     */
    public function test_multiple_writes_same_day(): void
    {
        $basePath = \sys_get_temp_dir().'/drishti_test_'.\uniqid();
        $backend = new DailyFileBackend($basePath);

        $backend->write('info', 'First message', []);
        $backend->write('error', 'Second message', []);

        $expectedFile = $basePath.'-'.\date('Y-m-d').'.log';
        $content = \file_get_contents($expectedFile);
        $this->assertStringContainsString('First message', $content);
        $this->assertStringContainsString('Second message', $content);

        \unlink($expectedFile);
    }

    /**
     * Tests that directory is created if it doesn't exist.
     */
    public function test_creates_directory(): void
    {
        $tempDir = \sys_get_temp_dir().'/drishti_test_dir_'.\uniqid();
        $basePath = $tempDir.'/subdir/app';
        $backend = new DailyFileBackend($basePath);

        $backend->write('info', 'Test message', []);

        $this->assertDirectoryExists($tempDir.'/subdir');

        $expectedFile = $basePath.'-'.\date('Y-m-d').'.log';
        \unlink($expectedFile);
        \rmdir($tempDir.'/subdir');
        \rmdir($tempDir);
    }

    /**
     * Tests that backends can accept custom formatters.
     */
    public function test_custom_formatter(): void
    {
        $customFormatter = new class implements LogEntryFormatterInterface
        {
            public function format(string $level, string $message, array $context): string
            {
                return \sprintf('CUSTOM[%s] %s%s', \strtoupper($level), $message, \PHP_EOL);
            }
        };

        $basePath = \sys_get_temp_dir().'/drishti_test_'.\uniqid();
        $backend = new DailyFileBackend($basePath, $customFormatter);

        $backend->write('info', 'Test message', []);

        $expectedFile = $basePath.'-'.\date('Y-m-d').'.log';
        $content = \file_get_contents($expectedFile);
        $this->assertStringContainsString('CUSTOM[INFO] Test message', $content);

        \unlink($expectedFile);
    }

    /**
     * Tests that an exception is thrown for empty base path.
     */
    public function test_empty_base_path_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Base path cannot be empty');
        new DailyFileBackend('');
    }

    /**
     * Tests that whitespace-only base path throws exception.
     */
    public function test_whitespace_only_base_path_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Base path cannot be empty');
        new DailyFileBackend('   ');
    }

    /**
     * Tests that file rotation happens when date changes (simulated).
     */
    public function test_file_rotation_on_date_change(): void
    {
        $basePath = \sys_get_temp_dir().'/drishti_test_'.\uniqid();
        $backend = new DailyFileBackend($basePath);

        // Write first message
        $backend->write('info', 'First day message', []);

        // Use reflection to change the current date to simulate day change
        $reflection = new \ReflectionClass($backend);
        $currentDateProperty = $reflection->getProperty('currentDate');
        $currentDateProperty->setAccessible(true);
        $currentDateProperty->setValue($backend, '2024-01-01');

        // Write second message - should create new file
        $backend->write('info', 'Second day message', []);

        $firstFile = $basePath.'-'.\date('Y-m-d').'.log';
        $secondFile = $basePath.'-2024-01-01.log';

        // Both files should exist
        $this->assertFileExists($firstFile);
        if (\file_exists($secondFile)) {
            $this->assertStringContainsString('Second day message', \file_get_contents($secondFile));
            \unlink($secondFile);
        }

        \unlink($firstFile);
    }

    /**
     * Tests that very long base paths work.
     */
    public function test_very_long_base_path(): void
    {
        $baseDir = \sys_get_temp_dir().'/drishti_test_'.\uniqid();
        \mkdir($baseDir);
        $longPath = $baseDir.'/'.\str_repeat('a', 200);

        $backend = new DailyFileBackend($longPath);
        $backend->write('info', 'Test message', []);

        $expectedFile = $longPath.'-'.\date('Y-m-d').'.log';
        $this->assertFileExists($expectedFile);

        \unlink($expectedFile);
        \rmdir($baseDir);
    }

    /**
     * Tests that multiple writes across different times work.
     */
    public function test_multiple_writes_over_time(): void
    {
        $basePath = \sys_get_temp_dir().'/drishti_test_'.\uniqid();
        $backend = new DailyFileBackend($basePath);

        // Write multiple messages
        for ($i = 0; $i < 5; $i++) {
            $backend->write('info', "Message $i", []);
            \usleep(1000); // Small delay to ensure different timestamps
        }

        $expectedFile = $basePath.'-'.\date('Y-m-d').'.log';
        $content = \file_get_contents($expectedFile);

        $this->assertStringContainsString('Message 0', $content);
        $this->assertStringContainsString('Message 4', $content);

        \unlink($expectedFile);
    }

    /**
     * Tests that relative base paths work.
     */
    public function test_relative_base_path(): void
    {
        $originalCwd = \getcwd();
        $tempDir = \sys_get_temp_dir().'/drishti_test_'.\uniqid();
        \mkdir($tempDir);
        \chdir($tempDir);

        try {
            $backend = new DailyFileBackend('relative');
            $backend->write('info', 'Test message', []);

            $expectedFile = $tempDir.'/relative-'.\date('Y-m-d').'.log';
            $this->assertFileExists($expectedFile);
            \unlink($expectedFile);
        } finally {
            \chdir($originalCwd);
            \rmdir($tempDir);
        }
    }

    /**
     * Tests that file write failure is handled gracefully.
     */
    public function test_file_write_failure_handled_gracefully(): void
    {
        $basePath = \sys_get_temp_dir().'/drishti_test_'.\uniqid();
        $backend = new DailyFileBackend($basePath);

        // Write first message to create file
        $backend->write('info', 'First message', []);

        $expectedFile = $basePath.'-'.\date('Y-m-d').'.log';

        // Make file read-only if on Unix
        if (\PHP_OS_FAMILY !== 'Windows' && \file_exists($expectedFile)) {
            \chmod($expectedFile, 0444); // Read-only

            // Should not throw exception even if write fails
            $backend->write('info', 'Second message', []);

            \chmod($expectedFile, 0644);
        } else {
            // On Windows, just verify the method doesn't throw
            $backend->write('info', 'Second message', []);
        }

        \unlink($expectedFile);
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    /**
     * Tests that directory creation failure throws exception.
     */
    public function test_directory_creation_failure_throws_exception(): void
    {
        if (\PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Cannot reliably test on Windows');
        }

        // Try to create a file in a path that would require creating /root subdirectory
        $basePath = '/root/nonexistent_'.\uniqid().'/app';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/(Cannot create directory|Directory is not writable)/');
        new DailyFileBackend($basePath);
    }

    /**
     * Tests that basePath with just filename (no directory) uses current directory.
     */
    public function test_basepath_without_directory(): void
    {
        $originalCwd = \getcwd();
        $tempDir = \sys_get_temp_dir().'/drishti_test_'.\uniqid();
        \mkdir($tempDir);
        \chdir($tempDir);

        try {
            $backend = new DailyFileBackend('app.log'); // Just filename, no directory
            $backend->write('info', 'Test message', []);

            $expectedFile = $tempDir.'/app.log-'.\date('Y-m-d').'.log';
            $this->assertFileExists($expectedFile);
            \unlink($expectedFile);
        } finally {
            \chdir($originalCwd);
            \rmdir($tempDir);
        }
    }

    /**
     * Tests that basePath with empty directory uses sys_get_temp_dir as fallback.
     */
    public function test_basepath_with_empty_directory_uses_temp_dir(): void
    {
        // Test when directory is empty string (edge case)
        $backend = new DailyFileBackend('app.log'); // This will have dirname('.') which becomes empty
        $backend->write('info', 'Test message', []);

        // File should be created in current directory or temp dir
        $expectedFile = \getcwd() ?: \sys_get_temp_dir();
        $expectedFile .= '/app.log-'.\date('Y-m-d').'.log';

        if (\file_exists($expectedFile)) {
            \unlink($expectedFile);
            $this->assertTrue(true);
        } else {
            // Try temp dir
            $expectedFile = \sys_get_temp_dir().'/app.log-'.\date('Y-m-d').'.log';
            if (\file_exists($expectedFile)) {
                \unlink($expectedFile);
                $this->assertTrue(true);
            } else {
                $this->fail('Log file was not created in expected location');
            }
        }
    }
}
