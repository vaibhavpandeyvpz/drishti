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
 * Test suite for JsonLogEntryFormatter class.
 *
 * @author Vaibhav Pandey <contact@vaibhavpandey.com>
 */
final class JsonLogEntryFormatterTest extends TestCase
{
    /**
     * Tests that the formatter formats log entries as JSON.
     */
    public function test_formats_log_entry_as_json(): void
    {
        $formatter = new JsonLogEntryFormatter;
        $formatted = $formatter->format('info', 'Test message', []);

        $this->assertJson($formatted);
        $decoded = \json_decode($formatted, true);
        $this->assertIsArray($decoded);
        $this->assertEquals('INFO', $decoded['level']);
        $this->assertEquals('Test message', $decoded['message']);
        $this->assertArrayHasKey('timestamp', $decoded);
    }

    /**
     * Tests that the formatter includes timestamp in ISO 8601 format.
     */
    public function test_includes_iso8601_timestamp(): void
    {
        $formatter = new JsonLogEntryFormatter;
        $formatted = $formatter->format('error', 'Error message', []);

        $decoded = \json_decode($formatted, true);
        $this->assertArrayHasKey('timestamp', $decoded);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/', $decoded['timestamp']);

        // Verify it's a valid date
        $date = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $decoded['timestamp']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $date);
    }

    /**
     * Tests that the formatter uppercases log levels.
     */
    public function test_uppercases_log_level(): void
    {
        $formatter = new JsonLogEntryFormatter;

        $this->assertStringContainsString('"level":"INFO"', $formatter->format('info', 'Message', []));
        $this->assertStringContainsString('"level":"ERROR"', $formatter->format('error', 'Message', []));
        $this->assertStringContainsString('"level":"DEBUG"', $formatter->format('debug', 'Message', []));
        $this->assertStringContainsString('"level":"WARNING"', $formatter->format('warning', 'Message', []));
    }

    /**
     * Tests that the formatter handles all log levels.
     */
    public function test_handles_all_log_levels(): void
    {
        $formatter = new JsonLogEntryFormatter;
        $levels = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

        foreach ($levels as $level) {
            $formatted = $formatter->format($level, 'Message', []);
            $decoded = \json_decode($formatted, true);
            $this->assertEquals(\strtoupper($level), $decoded['level']);
        }
    }

    /**
     * Tests that the formatter interpolates message placeholders.
     */
    public function test_interpolates_message_placeholders(): void
    {
        $formatter = new JsonLogEntryFormatter;
        $formatted = $formatter->format('info', 'User {username} logged in from {ip}', [
            'username' => 'john',
            'ip' => '192.168.1.1',
        ]);

        $decoded = \json_decode($formatted, true);
        $this->assertEquals('User john logged in from 192.168.1.1', $decoded['message']);
    }

    /**
     * Tests that the formatter includes context in JSON.
     */
    public function test_includes_context_in_json(): void
    {
        $formatter = new JsonLogEntryFormatter;
        $formatted = $formatter->format('info', 'Message', [
            'key1' => 'value1',
            'key2' => 42,
            'key3' => true,
        ]);

        $decoded = \json_decode($formatted, true);
        $this->assertArrayHasKey('context', $decoded);
        $this->assertEquals('value1', $decoded['context']['key1']);
        $this->assertEquals(42, $decoded['context']['key2']);
        $this->assertTrue($decoded['context']['key3']);
    }

    /**
     * Tests that the formatter handles exceptions.
     */
    public function test_handles_exceptions(): void
    {
        $formatter = new JsonLogEntryFormatter;
        $exception = new \RuntimeException('Test exception', 500);

        $formatted = $formatter->format('error', 'An error occurred', [
            'exception' => $exception,
        ]);

        $decoded = \json_decode($formatted, true);
        $this->assertArrayHasKey('exception', $decoded);
        $this->assertEquals('RuntimeException', $decoded['exception']['class']);
        $this->assertEquals('Test exception', $decoded['exception']['message']);
        $this->assertEquals(500, $decoded['exception']['code']);
        $this->assertArrayHasKey('file', $decoded['exception']);
        $this->assertArrayHasKey('line', $decoded['exception']);
    }

    /**
     * Tests that the formatter handles chained exceptions.
     */
    public function test_handles_chained_exceptions(): void
    {
        $formatter = new JsonLogEntryFormatter;
        $innerException = new \RuntimeException('Inner exception');
        $outerException = new \RuntimeException('Outer exception', 0, $innerException);

        $formatted = $formatter->format('error', 'Chained exception', [
            'exception' => $outerException,
        ]);

        $decoded = \json_decode($formatted, true);
        $this->assertArrayHasKey('exception', $decoded);
        $this->assertArrayHasKey('previous', $decoded['exception']);
        $this->assertEquals('RuntimeException', $decoded['exception']['previous']['class']);
        $this->assertEquals('Inner exception', $decoded['exception']['previous']['message']);
    }

    /**
     * Tests that the formatter uses the provided clock for timestamps.
     */
    public function test_uses_provided_clock(): void
    {
        $fixedTime = new \DateTimeImmutable('2024-01-15T14:30:45+00:00');
        $clock = new FrozenClock($fixedTime);
        $formatter = new JsonLogEntryFormatter($clock);
        $formatted = $formatter->format('info', 'Test message', []);

        $decoded = \json_decode($formatted, true);
        $this->assertEquals('2024-01-15T14:30:45+00:00', $decoded['timestamp']);
    }

    /**
     * Tests that the formatter handles empty messages.
     */
    public function test_handles_empty_message(): void
    {
        $formatter = new JsonLogEntryFormatter;
        $formatted = $formatter->format('info', '', []);

        $decoded = \json_decode($formatted, true);
        $this->assertEquals('', $decoded['message']);
        $this->assertEquals('INFO', $decoded['level']);
    }

    /**
     * Tests that the formatter handles Unicode characters.
     */
    public function test_handles_unicode_characters(): void
    {
        $formatter = new JsonLogEntryFormatter;
        $unicodeMessage = 'Unicode: 你好世界 🎉 émoji';
        $formatted = $formatter->format('info', $unicodeMessage, []);

        $decoded = \json_decode($formatted, true);
        $this->assertEquals($unicodeMessage, $decoded['message']);
    }

    /**
     * Tests that the formatter handles arrays in context.
     */
    public function test_handles_arrays_in_context(): void
    {
        $formatter = new JsonLogEntryFormatter;
        $formatted = $formatter->format('info', 'Message', [
            'roles' => ['admin', 'user'],
            'metadata' => ['key' => 'value'],
        ]);

        $decoded = \json_decode($formatted, true);
        $this->assertIsArray($decoded['context']['roles']);
        $this->assertEquals(['admin', 'user'], $decoded['context']['roles']);
        $this->assertIsArray($decoded['context']['metadata']);
    }

    /**
     * Tests that the formatter handles nested arrays in context.
     */
    public function test_handles_nested_arrays_in_context(): void
    {
        $formatter = new JsonLogEntryFormatter;
        $formatted = $formatter->format('info', 'Message', [
            'data' => [
                'level1' => [
                    'level2' => ['deep' => 'value'],
                ],
            ],
        ]);

        $decoded = \json_decode($formatted, true);
        $this->assertEquals('value', $decoded['context']['data']['level1']['level2']['deep']);
    }

    /**
     * Tests that the formatter excludes exception from context when exception key exists.
     */
    public function test_excludes_exception_from_context(): void
    {
        $formatter = new JsonLogEntryFormatter;
        $exception = new \RuntimeException('Test exception');

        $formatted = $formatter->format('error', 'Error', [
            'exception' => $exception,
            'other' => 'value',
        ]);

        $decoded = \json_decode($formatted, true);
        $this->assertArrayHasKey('exception', $decoded);
        $this->assertArrayHasKey('context', $decoded);
        $this->assertArrayNotHasKey('exception', $decoded['context']);
        $this->assertEquals('value', $decoded['context']['other']);
    }

    /**
     * Tests that the formatter omits context key when context is empty.
     */
    public function test_omits_empty_context(): void
    {
        $formatter = new JsonLogEntryFormatter;
        $formatted = $formatter->format('info', 'Message', []);

        $decoded = \json_decode($formatted, true);
        $this->assertArrayNotHasKey('context', $decoded);
    }

    /**
     * Tests that the formatter handles null values in context.
     */
    public function test_handles_null_values_in_context(): void
    {
        $formatter = new JsonLogEntryFormatter;
        $formatted = $formatter->format('info', 'Message', [
            'null_value' => null,
            'string_value' => 'test',
        ]);

        $decoded = \json_decode($formatted, true);
        $this->assertNull($decoded['context']['null_value']);
        $this->assertEquals('test', $decoded['context']['string_value']);
    }
}
