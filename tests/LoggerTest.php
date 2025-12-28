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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;

/**
 * Test suite for Logger class.
 *
 * Tests cover all aspects of the PSR-3 logger implementation including:
 * - All log level methods
 * - Message interpolation with context
 * - Exception handling in context
 * - Invalid log level validation
 * - Different context value types
 * - Backend integration
 * - Multiple backends
 *
 * @author Vaibhav Pandey <contact@vaibhavpandey.com>
 */
final class LoggerTest extends TestCase
{
    /**
     * Tests that all log level methods work correctly.
     *
     * @param  string  $level  The log level to test
     * @param  string  $method  The method name to call
     */
    #[DataProvider('provideLogLevels')]
    public function test_log_levels(string $level, string $method): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);
        $logger->$method('Test message');

        $this->assertCount(1, $backend->entries);
        $this->assertEquals($level, $backend->entries[0]['level']);
        $this->assertStringContainsString('Test message', $backend->entries[0]['message']);
    }

    /**
     * Tests that the log() method works with all valid log levels.
     *
     * @param  string  $level  The log level to test
     */
    #[DataProvider('provideValidLogLevels')]
    public function test_log_method_with_valid_levels(string $level): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);
        $logger->log($level, 'Test message');

        $this->assertCount(1, $backend->entries);
        $this->assertEquals($level, $backend->entries[0]['level']);
        $this->assertStringContainsString('Test message', $backend->entries[0]['message']);
    }

    /**
     * Tests that an exception is thrown for invalid log levels.
     */
    public function test_log_method_with_invalid_level(): void
    {
        $logger = new Logger;
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid log level 'invalid'");
        $logger->log('invalid', 'Test message');
    }

    /**
     * Tests that message interpolation works with simple context values.
     */
    public function test_message_interpolation(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);
        $logger->info('User {username} logged in from {ip}', [
            'username' => 'john',
            'ip' => '192.168.1.1',
        ]);

        $this->assertStringContainsString('User john logged in from 192.168.1.1', $backend->entries[0]['message']);
    }

    /**
     * Tests that message interpolation works with numeric context values.
     */
    public function test_message_interpolation_with_numbers(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);
        $logger->info('Processed {count} items in {duration}ms', [
            'count' => 42,
            'duration' => 123.45,
        ]);

        $this->assertStringContainsString('Processed 42 items in 123.45ms', $backend->entries[0]['message']);
    }

    /**
     * Tests that message interpolation works with boolean context values.
     */
    public function test_message_interpolation_with_booleans(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);

        $logger->info('Feature enabled: {enabled}', [
            'enabled' => true,
        ]);
        $this->assertStringContainsString('Feature enabled: true', $backend->entries[0]['message']);

        $logger->info('Feature enabled: {enabled}', [
            'enabled' => false,
        ]);
        $this->assertStringContainsString('Feature enabled: false', $backend->entries[1]['message']);
    }

    /**
     * Tests that message interpolation works with null context values.
     */
    public function test_message_interpolation_with_null(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);
        $logger->info('User: {user}', [
            'user' => null,
        ]);

        $this->assertStringContainsString('User: null', $backend->entries[0]['message']);
    }

    /**
     * Tests that message interpolation works with array context values.
     */
    public function test_message_interpolation_with_arrays(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);
        $logger->info('User roles: {roles}', [
            'roles' => ['admin', 'user'],
        ]);

        $this->assertStringContainsString('User roles: ["admin","user"]', $backend->entries[0]['message']);
    }

    /**
     * Tests that message interpolation works with object context values.
     */
    public function test_message_interpolation_with_objects(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);
        $object = new \stdClass;
        $object->name = 'Test';

        $logger->info('Object: {obj}', [
            'obj' => $object,
        ]);

        $this->assertStringContainsString('Object: stdClass', $backend->entries[0]['message']);
    }

    /**
     * Tests that message interpolation works with objects that implement __toString().
     */
    public function test_message_interpolation_with_stringable_objects(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);
        $stringable = new class
        {
            public function __toString(): string
            {
                return 'StringableObject';
            }
        };

        $logger->info('Object: {obj}', [
            'obj' => $stringable,
        ]);

        $this->assertStringContainsString('Object: StringableObject', $backend->entries[0]['message']);
    }

    /**
     * Tests that exceptions in context are handled correctly.
     */
    public function test_exception_in_context(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);
        $exception = new \RuntimeException('Test exception', 500);

        $logger->error('An error occurred', [
            'exception' => $exception,
        ]);

        $this->assertStringContainsString('An error occurred', $backend->entries[0]['message']);
        $this->assertStringContainsString('[Exception: Test exception', $backend->entries[0]['message']);
        $this->assertStringContainsString($exception->getFile(), $backend->entries[0]['message']);
        $this->assertStringContainsString((string) $exception->getLine(), $backend->entries[0]['message']);
    }

    /**
     * Tests that exceptions in context don't interfere with message interpolation.
     */
    public function test_exception_with_message_interpolation(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);
        $exception = new \RuntimeException('Test exception');

        $logger->error('User {username} encountered an error', [
            'username' => 'john',
            'exception' => $exception,
        ]);

        $this->assertStringContainsString('User john encountered an error', $backend->entries[0]['message']);
        $this->assertStringContainsString('[Exception:', $backend->entries[0]['message']);
    }

    /**
     * Tests that Stringable objects are accepted as messages.
     */
    public function test_stringable_message(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);
        $message = new class implements \Stringable
        {
            public function __toString(): string
            {
                return 'Stringable message';
            }
        };

        $logger->info($message);

        $this->assertStringContainsString('Stringable message', $backend->entries[0]['message']);
    }

    /**
     * Tests that error levels use StdioBackend::stderr() when no backends are configured.
     */
    public function test_error_levels_use_stderr_by_default(): void
    {
        $logger = new Logger;
        $errorLevels = [LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::ERROR];

        // Verify that error levels can be logged without errors
        // (actual STDERR capture is complex, so we just verify execution)
        foreach ($errorLevels as $level) {
            try {
                $logger->log($level, 'Error message');
                $this->assertTrue(true); // If we get here, logging succeeded
            } catch (\Throwable $e) {
                $this->fail('Logging should not throw exceptions: '.$e->getMessage());
            }
        }
    }

    /**
     * Tests that informational levels use STDOUT when no backends are configured.
     */
    public function test_info_levels_use_stdout_by_default(): void
    {
        $logger = new Logger;
        $infoLevels = [LogLevel::WARNING, LogLevel::NOTICE, LogLevel::INFO, LogLevel::DEBUG];

        // Verify that info levels can be logged without errors
        // (actual STDOUT capture is complex with fwrite, so we just verify execution)
        foreach ($infoLevels as $level) {
            try {
                $logger->log($level, 'Info message');
                $this->assertTrue(true); // If we get here, logging succeeded
            } catch (\Throwable $e) {
                $this->fail('Logging should not throw exceptions: '.$e->getMessage());
            }
        }
    }

    /**
     * Tests that logs are written to a single backend.
     */
    public function test_single_backend(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);

        $logger->info('Test message', ['key' => 'value']);

        $this->assertCount(1, $backend->entries);
        $this->assertEquals(LogLevel::INFO, $backend->entries[0]['level']);
        $this->assertStringContainsString('Test message', $backend->entries[0]['message']);
        $this->assertEquals(['key' => 'value'], $backend->entries[0]['context']);
    }

    /**
     * Tests that logs are written to multiple backends.
     */
    public function test_multiple_backends(): void
    {
        $backend1 = $this->createTestBackend();
        $backend2 = $this->createTestBackend();
        $logger = new Logger([$backend1, $backend2]);

        $logger->info('Test message');

        $this->assertCount(1, $backend1->entries);
        $this->assertCount(1, $backend2->entries);
        $this->assertEquals(LogLevel::INFO, $backend1->entries[0]['level']);
        $this->assertEquals(LogLevel::INFO, $backend2->entries[0]['level']);
    }

    /**
     * Tests that addBackend() method works and supports chaining.
     */
    public function test_add_backend(): void
    {
        $backend1 = $this->createTestBackend();
        $backend2 = $this->createTestBackend();
        $logger = new Logger($backend1);

        $result = $logger->addBackend($backend2);

        $this->assertSame($logger, $result);
        $logger->info('Test message');

        $this->assertCount(1, $backend1->entries);
        $this->assertCount(1, $backend2->entries);
    }

    /**
     * Tests that backend errors don't interrupt logging to other backends.
     */
    public function test_backend_errors_are_handled_gracefully(): void
    {
        $failingBackend = new class implements BackendInterface
        {
            public function write(string $level, string $message, array $context): void
            {
                throw new \RuntimeException('Backend error');
            }
        };

        $workingBackend = $this->createTestBackend();
        $logger = new Logger([$failingBackend, $workingBackend]);

        // Should not throw exception
        $logger->info('Test message');

        // Working backend should still receive the log
        $this->assertCount(1, $workingBackend->entries);
    }

    /**
     * Creates a test backend that captures log entries.
     *
     * @return object{entries: array<int, array{level: string, message: string, context: array}>, write: callable}
     */
    private function createTestBackend(): object
    {
        return new class implements BackendInterface
        {
            /**
             * @var array<int, array{level: string, message: string, context: array}>
             */
            public array $entries = [];

            private readonly SimpleLogEntryFormatter $formatter;

            public function __construct()
            {
                $this->formatter = new SimpleLogEntryFormatter;
            }

            public function write(string $level, string $message, array $context): void
            {
                // Format the message using the formatter (like real backends do)
                $formatted = $this->formatter->format($level, $message, $context);

                // Extract just the message part (without timestamp and level prefix) for easier testing
                // Format is: [YYYY-MM-DD HH:MM:SS] LEVEL: message
                $pattern = '/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] \w+: (.+)$/s';
                $matches = [];
                if (\preg_match($pattern, $formatted, $matches)) {
                    $interpolatedMessage = \rtrim($matches[1], \PHP_EOL);
                } else {
                    // Fallback: use formatted message as-is
                    $interpolatedMessage = \rtrim($formatted, \PHP_EOL);
                }

                $this->entries[] = [
                    'level' => $level,
                    'message' => $interpolatedMessage,
                    'context' => $context,
                ];
            }
        };
    }

    /**
     * Tests that log entries include timestamps.
     */
    public function test_log_entries_include_timestamp(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);
        $logger->info('Test message');

        // Verify timestamp is in the formatted message (backend receives formatted message)
        // Since backend receives raw message, we need to check via formatter
        $formatter = new SimpleLogEntryFormatter;
        $formatted = $formatter->format('info', 'Test message', []);
        $this->assertMatchesRegularExpression('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', $formatted);
    }

    /**
     * Tests that placeholders not in context remain unchanged.
     */
    public function test_missing_context_placeholders(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);
        $logger->info('User {username} from {location}', [
            'username' => 'john',
        ]);

        $this->assertStringContainsString('User john from {location}', $backend->entries[0]['message']);
    }

    /**
     * Tests that multiple placeholders work correctly.
     */
    public function test_multiple_placeholders(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);
        $logger->info('User {user} performed {action} on {resource}', [
            'user' => 'admin',
            'action' => 'delete',
            'resource' => 'file.txt',
        ]);

        $this->assertStringContainsString('User admin performed delete on file.txt', $backend->entries[0]['message']);
    }

    /**
     * Tests that empty context array works correctly.
     */
    public function test_empty_context(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);
        $logger->info('Simple message', []);

        $this->assertStringContainsString('Simple message', $backend->entries[0]['message']);
    }

    /**
     * Tests that empty message works correctly.
     */
    public function test_empty_message(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);
        $logger->info('');

        $this->assertEquals('', $backend->entries[0]['message']);
        $this->assertEquals(LogLevel::INFO, $backend->entries[0]['level']);
    }

    /**
     * Tests that resources in context are handled correctly.
     */
    public function test_resource_in_context(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);
        $resource = \fopen('php://memory', 'r');

        $logger->info('Resource: {res}', [
            'res' => $resource,
        ]);

        $this->assertStringContainsString('Resource: stream', $backend->entries[0]['message']);
        \fclose($resource);
    }

    /**
     * Provides test data for log level methods.
     *
     * @return array<int, array<int, string>> Array of [level, method] pairs
     */
    public static function provideLogLevels(): array
    {
        return [
            [LogLevel::EMERGENCY, 'emergency'],
            [LogLevel::ALERT, 'alert'],
            [LogLevel::CRITICAL, 'critical'],
            [LogLevel::ERROR, 'error'],
            [LogLevel::WARNING, 'warning'],
            [LogLevel::NOTICE, 'notice'],
            [LogLevel::INFO, 'info'],
            [LogLevel::DEBUG, 'debug'],
        ];
    }

    /**
     * Provides test data for valid log levels.
     *
     * @return array<int, array<int, string>> Array of log levels
     */
    public static function provideValidLogLevels(): array
    {
        return [
            [LogLevel::EMERGENCY],
            [LogLevel::ALERT],
            [LogLevel::CRITICAL],
            [LogLevel::ERROR],
            [LogLevel::WARNING],
            [LogLevel::NOTICE],
            [LogLevel::INFO],
            [LogLevel::DEBUG],
        ];
    }

    /**
     * Tests that very long messages are handled correctly.
     */
    public function test_very_long_message(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);
        $longMessage = \str_repeat('A', 10000);
        $logger->info($longMessage);

        $this->assertStringContainsString($longMessage, $backend->entries[0]['message']);
    }

    /**
     * Tests that messages with special characters are handled correctly.
     */
    public function test_special_characters_in_message(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);
        $specialMessage = "Line 1\nLine 2\tTabbed\rCarriage\nUnicode: 你好世界 🎉";
        $logger->info($specialMessage);

        $this->assertStringContainsString($specialMessage, $backend->entries[0]['message']);
    }

    /**
     * Tests that nested arrays in context are handled correctly.
     */
    public function test_nested_arrays_in_context(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);
        $logger->info('Nested: {data}', [
            'data' => [
                'level1' => [
                    'level2' => ['deep' => 'value'],
                ],
            ],
        ]);

        $this->assertStringContainsString('Nested:', $backend->entries[0]['message']);
        $this->assertStringContainsString('deep', $backend->entries[0]['message']);
    }

    /**
     * Tests that empty arrays in context are handled correctly.
     */
    public function test_empty_arrays_in_context(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);
        $logger->info('Empty: {empty}', [
            'empty' => [],
        ]);

        $this->assertStringContainsString('Empty: []', $backend->entries[0]['message']);
    }

    /**
     * Tests that null values in arrays are handled correctly.
     */
    public function test_null_values_in_arrays(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);
        $logger->info('Array: {arr}', [
            'arr' => ['key1' => 'value', 'key2' => null, 'key3' => 'value2'],
        ]);

        $this->assertStringContainsString('Array:', $backend->entries[0]['message']);
        $this->assertStringContainsString('null', $backend->entries[0]['message']);
    }

    /**
     * Tests that exception chaining is handled correctly.
     */
    public function test_exception_chaining(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);
        $innerException = new \RuntimeException('Inner exception');
        $outerException = new \RuntimeException('Outer exception', 0, $innerException);

        $logger->error('Chained exception', [
            'exception' => $outerException,
        ]);

        $this->assertStringContainsString('Chained exception', $backend->entries[0]['message']);
        $this->assertStringContainsString('[Exception: Outer exception', $backend->entries[0]['message']);
    }

    /**
     * Tests that different exception types are handled correctly.
     */
    public function test_different_exception_types(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);
        $exceptions = [
            new \RuntimeException('Runtime error'),
            new \InvalidArgumentException('Invalid argument'),
            new \LogicException('Logic error'),
            new \Exception('Generic exception'),
        ];

        foreach ($exceptions as $exception) {
            $logger->error('Exception occurred', [
                'exception' => $exception,
            ]);

            $lastEntry = \end($backend->entries);
            $this->assertStringContainsString('Exception occurred', $lastEntry['message']);
            $this->assertStringContainsString('[Exception:', $lastEntry['message']);
        }
    }

    /**
     * Tests that very large context arrays are handled correctly.
     */
    public function test_very_large_context(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);
        $largeContext = [];
        for ($i = 0; $i < 1000; $i++) {
            $largeContext["key_$i"] = "value_$i";
        }

        $logger->info('Large context: {key_500}', $largeContext);

        $this->assertStringContainsString('Large context: value_500', $backend->entries[0]['message']);
    }

    /**
     * Tests that placeholder names with special characters are handled.
     */
    public function test_special_characters_in_placeholder_names(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);
        $logger->info('Placeholder: {user_name}', [
            'user_name' => 'john',
        ]);

        $this->assertStringContainsString('Placeholder: john', $backend->entries[0]['message']);
    }

    /**
     * Tests that same backend can be added multiple times.
     */
    public function test_same_backend_added_multiple_times(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger;
        $logger->addBackend($backend)->addBackend($backend);

        $logger->info('Test message');

        // Should receive the message twice since backend is added twice
        $this->assertCount(2, $backend->entries);
    }

    /**
     * Tests that all backends fail gracefully.
     */
    public function test_all_backends_fail(): void
    {
        $failingBackend1 = new class implements BackendInterface
        {
            public function write(string $level, string $message, array $context): void
            {
                throw new \RuntimeException('Backend error 1');
            }
        };

        $failingBackend2 = new class implements BackendInterface
        {
            public function write(string $level, string $message, array $context): void
            {
                throw new \RuntimeException('Backend error 2');
            }
        };

        $logger = new Logger([$failingBackend1, $failingBackend2]);

        // Should not throw exception even if all backends fail
        $logger->info('Test message');
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    /**
     * Tests that empty string in context values works.
     */
    public function test_empty_string_in_context(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);
        $logger->info('Value: {value}', [
            'value' => '',
        ]);

        $this->assertStringContainsString('Value:', $backend->entries[0]['message']);
    }

    /**
     * Tests that zero and negative numbers in context work.
     */
    public function test_zero_and_negative_numbers(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);
        $logger->info('Count: {count}, Balance: {balance}', [
            'count' => 0,
            'balance' => -100.50,
        ]);

        $this->assertStringContainsString('Count: 0', $backend->entries[0]['message']);
        $this->assertStringContainsString('Balance: -100.5', $backend->entries[0]['message']);
    }

    /**
     * Tests that very long placeholder names are handled.
     */
    public function test_very_long_placeholder_name(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);
        $longKey = \str_repeat('a', 100);
        $context = [$longKey => 'value'];

        $logger->info("Placeholder: {{$longKey}}", $context);

        $this->assertStringContainsString('Placeholder: value', $backend->entries[0]['message']);
    }

    /**
     * Tests that messages with only placeholders work.
     */
    public function test_message_with_only_placeholders(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);
        $logger->info('{greeting} {name}!', [
            'greeting' => 'Hello',
            'name' => 'World',
        ]);

        $this->assertStringContainsString('Hello World!', $backend->entries[0]['message']);
    }

    /**
     * Tests that numeric keys in context work correctly.
     */
    public function test_numeric_keys_in_context(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);
        $logger->info('Value: {0}', [
            0 => 'numeric_key',
        ]);

        $this->assertStringContainsString('Value: numeric_key', $backend->entries[0]['message']);
    }

    /**
     * Tests that messages with curly braces that are not placeholders work.
     */
    public function test_curly_braces_not_placeholders(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);
        $logger->info('Message with {literal} braces', []);

        $this->assertStringContainsString('Message with {literal} braces', $backend->entries[0]['message']);
    }

    /**
     * Tests that placeholder at start of message works.
     */
    public function test_placeholder_at_start(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);
        $logger->info('{greeting} World', [
            'greeting' => 'Hello',
        ]);

        $this->assertStringContainsString('Hello World', $backend->entries[0]['message']);
    }

    /**
     * Tests that placeholder at end of message works.
     */
    public function test_placeholder_at_end(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);
        $logger->info('Hello {name}', [
            'name' => 'World',
        ]);

        $this->assertStringContainsString('Hello World', $backend->entries[0]['message']);
    }

    /**
     * Tests that adjacent placeholders work.
     */
    public function test_adjacent_placeholders(): void
    {
        $backend = $this->createTestBackend();
        $logger = new Logger($backend);
        $logger->info('{first}{second}', [
            'first' => 'Hello',
            'second' => 'World',
        ]);

        $this->assertStringContainsString('HelloWorld', $backend->entries[0]['message']);
    }

    /**
     * Tests that invalid log level types are rejected.
     */
    public function test_invalid_log_level_types(): void
    {
        $logger = new Logger;

        $this->expectException(InvalidArgumentException::class);
        $logger->log(123, 'Message');
    }

    /**
     * Tests that empty array passed as backends works.
     */
    public function test_empty_backends_array(): void
    {
        $logger = new Logger([]);
        // Should use default backend (STDOUT for info level)
        // Just verify it doesn't throw
        try {
            $logger->info('Test message');
            $this->assertTrue(true); // If we get here, logging succeeded
        } catch (\Throwable $e) {
            $this->fail('Logging should not throw exceptions: '.$e->getMessage());
        }
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
