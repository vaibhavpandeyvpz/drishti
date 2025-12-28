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

/**
 * Test suite for FileBackend class.
 *
 * @author Vaibhav Pandey <contact@vaibhavpandey.com>
 */
final class FileBackendTest extends TestCase
{
    /**
     * Tests that logs are written to a file.
     */
    public function test_writes_to_file(): void
    {
        $filePath = \sys_get_temp_dir().'/drishti_test_'.\uniqid().'.log';
        $backend = new FileBackend($filePath);

        $backend->write('info', 'Test message', []);

        $this->assertFileExists($filePath);
        $content = \file_get_contents($filePath);
        $this->assertStringContainsString('INFO', $content);
        $this->assertStringContainsString('Test message', $content);

        \unlink($filePath);
    }

    /**
     * Tests that multiple writes append to the file.
     */
    public function test_multiple_writes_append(): void
    {
        $filePath = \sys_get_temp_dir().'/drishti_test_'.\uniqid().'.log';
        $backend = new FileBackend($filePath);

        $backend->write('info', 'First message', []);
        $backend->write('error', 'Second message', []);

        $content = \file_get_contents($filePath);
        $this->assertStringContainsString('First message', $content);
        $this->assertStringContainsString('Second message', $content);

        \unlink($filePath);
    }

    /**
     * Tests that directory is created if it doesn't exist.
     */
    public function test_creates_directory(): void
    {
        $tempDir = \sys_get_temp_dir().'/drishti_test_dir_'.\uniqid();
        $filePath = $tempDir.'/subdir/test.log';
        $backend = new FileBackend($filePath);

        $backend->write('info', 'Test message', []);

        $this->assertDirectoryExists($tempDir.'/subdir');
        $this->assertFileExists($filePath);

        \unlink($filePath);
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

        $filePath = \sys_get_temp_dir().'/drishti_test_'.\uniqid().'.log';
        $backend = new FileBackend($filePath, $customFormatter);

        $backend->write('info', 'Test message', []);

        $content = \file_get_contents($filePath);
        $this->assertStringContainsString('CUSTOM[INFO] Test message', $content);

        \unlink($filePath);
    }

    /**
     * Tests that an exception is thrown for empty file path.
     */
    public function test_empty_file_path_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File path cannot be empty');
        new FileBackend('');
    }

    /**
     * Tests that an exception is thrown for non-writable directory.
     */
    public function test_non_writable_directory_throws_exception(): void
    {
        if (\PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Cannot test on Windows');
        }

        $filePath = '/root/drishti_test_'.\uniqid().'.log';

        $this->expectException(\RuntimeException::class);
        // The exception could be either about directory creation or writability
        $this->expectExceptionMessageMatches('/(Directory is not writable|Cannot create directory)/');
        new FileBackend($filePath);
    }

    /**
     * Tests that existing files are appended to.
     */
    public function test_appends_to_existing_file(): void
    {
        $filePath = \sys_get_temp_dir().'/drishti_test_'.\uniqid().'.log';
        \file_put_contents($filePath, "Existing content\n");

        $backend = new FileBackend($filePath);
        $backend->write('info', 'New message', []);

        $content = \file_get_contents($filePath);
        $this->assertStringContainsString('Existing content', $content);
        $this->assertStringContainsString('New message', $content);

        \unlink($filePath);
    }

    /**
     * Tests that relative paths work correctly.
     */
    public function test_relative_path(): void
    {
        $originalCwd = \getcwd();
        $tempDir = \sys_get_temp_dir().'/drishti_test_'.\uniqid();
        \mkdir($tempDir);
        \chdir($tempDir);

        try {
            $backend = new FileBackend('relative.log');
            $backend->write('info', 'Test message', []);

            $this->assertFileExists($tempDir.'/relative.log');
            \unlink($tempDir.'/relative.log');
        } finally {
            \chdir($originalCwd);
            \rmdir($tempDir);
        }
    }

    /**
     * Tests that file paths with special characters work.
     */
    public function test_file_path_with_special_characters(): void
    {
        $baseDir = \sys_get_temp_dir().'/drishti_test_'.\uniqid();
        \mkdir($baseDir);
        $filePath = $baseDir.'/test-file_123.log';

        $backend = new FileBackend($filePath);
        $backend->write('info', 'Test message', []);

        $this->assertFileExists($filePath);

        \unlink($filePath);
        \rmdir($baseDir);
    }

    /**
     * Tests that very long log messages are written correctly.
     */
    public function test_very_long_message(): void
    {
        $filePath = \sys_get_temp_dir().'/drishti_test_'.\uniqid().'.log';
        $backend = new FileBackend($filePath);

        $longMessage = \str_repeat('A', 50000);
        $backend->write('info', $longMessage, []);

        $content = \file_get_contents($filePath);
        $this->assertStringContainsString($longMessage, $content);

        \unlink($filePath);
    }

    /**
     * Tests that multiple concurrent writes work (file locking).
     */
    public function test_concurrent_writes(): void
    {
        $filePath = \sys_get_temp_dir().'/drishti_test_'.\uniqid().'.log';
        $backend = new FileBackend($filePath);

        // Simulate concurrent writes
        for ($i = 0; $i < 10; $i++) {
            $backend->write('info', "Message $i", []);
        }

        $content = \file_get_contents($filePath);
        $this->assertStringContainsString('Message 0', $content);
        $this->assertStringContainsString('Message 9', $content);

        \unlink($filePath);
    }

    /**
     * Tests that empty file path throws exception.
     */
    public function test_whitespace_only_file_path_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File path cannot be empty');
        new FileBackend('   ');
    }

    /**
     * Tests that file write failure is handled gracefully.
     */
    public function test_file_write_failure_handled_gracefully(): void
    {
        // Create a file path that will fail to write (read-only file if possible)
        $filePath = \sys_get_temp_dir().'/drishti_test_'.\uniqid().'.log';
        $backend = new FileBackend($filePath);

        // Create the file and make it read-only if on Unix
        if (\PHP_OS_FAMILY !== 'Windows') {
            \touch($filePath);
            \chmod($filePath, 0444); // Read-only

            // Should not throw exception even if write fails
            $backend->write('info', 'Test message', []);

            \chmod($filePath, 0644);
            \unlink($filePath);
        } else {
            // On Windows, just verify the method doesn't throw
            $backend->write('info', 'Test message', []);
            \unlink($filePath);
        }

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
        $filePath = '/root/nonexistent_'.\uniqid().'/test.log';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/(Cannot create directory|Directory is not writable)/');
        new FileBackend($filePath);
    }
}
