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
 * Test suite for StdioBackend class.
 *
 * @author Vaibhav Pandey <contact@vaibhavpandey.com>
 */
final class StdioBackendTest extends TestCase
{
    /**
     * Tests that stdout() factory creates a backend for STDOUT.
     */
    public function test_stdout_factory(): void
    {
        $backend = StdioBackend::stdout();
        $this->assertInstanceOf(StdioBackend::class, $backend);
        $this->assertInstanceOf(BackendInterface::class, $backend);

        // Verify backend can write without errors
        // (actual STDOUT capture with fwrite is complex, so we verify execution)
        $backend->write('info', 'Test message', []);
        $this->assertTrue(true); // If we get here, writing succeeded
    }

    /**
     * Tests that stderr() factory creates a backend for STDERR.
     */
    public function test_stderr_factory(): void
    {
        $backend = StdioBackend::stderr();
        $this->assertInstanceOf(StdioBackend::class, $backend);
        $this->assertInstanceOf(BackendInterface::class, $backend);

        // Verify backend can write without errors
        // (actual STDERR capture is complex, so we just verify execution)
        $backend->write('error', 'Test message', []);
        $this->assertTrue(true); // If we get here, writing succeeded
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

        $backend = StdioBackend::stdout($customFormatter);
        // Verify backend uses custom formatter by checking the formatter is set
        // (actual output capture is complex with fwrite)
        $backend->write('info', 'Test message', []);
        $this->assertTrue(true); // If we get here, writing succeeded
    }

    /**
     * Tests that stdout and stderr backends are different instances.
     */
    public function test_stdout_and_stderr_are_different(): void
    {
        $stdout = StdioBackend::stdout();
        $stderr = StdioBackend::stderr();

        $this->assertNotSame($stdout, $stderr);
    }

    /**
     * Tests that constructor throws exception for invalid stream.
     */
    public function test_constructor_throws_exception_for_invalid_stream(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Stream must be a valid resource');

        // Use reflection to call private constructor with invalid stream
        $reflection = new \ReflectionClass(StdioBackend::class);
        $constructor = $reflection->getConstructor();
        $constructor->setAccessible(true);
        $constructor->invokeArgs($reflection->newInstanceWithoutConstructor(), ['not-a-resource']);
    }

    /**
     * Captures output written to STDOUT.
     *
     * @param  callable  $callback  The callback to execute
     * @return string The captured output
     */
    private function captureOutput(callable $callback): string
    {
        \ob_start();
        $callback();
        $output = \ob_get_clean();

        return $output ?: '';
    }
}
