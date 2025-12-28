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
 * Test suite for SimpleLogEntryFormatter class.
 *
 * @author Vaibhav Pandey <contact@vaibhavpandey.com>
 */
final class SimpleLogEntryFormatterTest extends TestCase
{
    /**
     * Tests that the formatter formats log entries correctly.
     */
    public function test_formats_log_entry(): void
    {
        $formatter = new SimpleLogEntryFormatter;
        $formatted = $formatter->format('info', 'Test message', []);

        $this->assertStringContainsString('INFO', $formatted);
        $this->assertStringContainsString('Test message', $formatted);
        $this->assertMatchesRegularExpression('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', $formatted);
        $this->assertStringEndsWith(\PHP_EOL, $formatted);
    }

    /**
     * Tests that the formatter includes timestamp.
     */
    public function test_includes_timestamp(): void
    {
        $formatter = new SimpleLogEntryFormatter;
        $formatted = $formatter->format('error', 'Error message', []);

        // Check for timestamp format YYYY-MM-DD HH:MM:SS
        $this->assertMatchesRegularExpression('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', $formatted);
    }

    /**
     * Tests that the formatter uppercases log levels.
     */
    public function test_uppercases_log_level(): void
    {
        $formatter = new SimpleLogEntryFormatter;

        $this->assertStringContainsString('INFO', $formatter->format('info', 'Message', []));
        $this->assertStringContainsString('ERROR', $formatter->format('error', 'Message', []));
        $this->assertStringContainsString('DEBUG', $formatter->format('debug', 'Message', []));
        $this->assertStringContainsString('WARNING', $formatter->format('warning', 'Message', []));
    }

    /**
     * Tests that the formatter handles different log levels.
     */
    public function test_handles_all_log_levels(): void
    {
        $formatter = new SimpleLogEntryFormatter;
        $levels = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

        foreach ($levels as $level) {
            $formatted = $formatter->format($level, 'Message', []);
            $this->assertStringContainsString(\strtoupper($level), $formatted);
            $this->assertStringContainsString('Message', $formatted);
        }
    }

    /**
     * Tests that the formatter preserves the message content.
     */
    public function test_preserves_message(): void
    {
        $formatter = new SimpleLogEntryFormatter;
        $message = 'User {username} logged in from {ip}';
        $formatted = $formatter->format('info', $message, []);

        $this->assertStringContainsString($message, $formatted);
    }

    /**
     * Tests that the formatter ignores context (formatting is done by Logger).
     */
    public function test_context_parameter_accepted(): void
    {
        $formatter = new SimpleLogEntryFormatter;
        $formatted = $formatter->format('info', 'Message', ['key' => 'value']);

        // Context is not used in formatting, but parameter is accepted
        $this->assertStringContainsString('Message', $formatted);
    }

    /**
     * Tests that the formatter handles empty messages.
     */
    public function test_handles_empty_message(): void
    {
        $formatter = new SimpleLogEntryFormatter;
        $formatted = $formatter->format('info', '', []);

        $this->assertStringContainsString('INFO:', $formatted);
        $this->assertMatchesRegularExpression('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', $formatted);
    }

    /**
     * Tests that the formatter handles messages with newlines.
     */
    public function test_handles_multiline_message(): void
    {
        $formatter = new SimpleLogEntryFormatter;
        $multilineMessage = "Line 1\nLine 2\nLine 3";
        $formatted = $formatter->format('error', $multilineMessage, []);

        $this->assertStringContainsString($multilineMessage, $formatted);
        $this->assertStringContainsString('ERROR:', $formatted);
    }

    /**
     * Tests that the formatter handles Unicode characters.
     */
    public function test_handles_unicode_characters(): void
    {
        $formatter = new SimpleLogEntryFormatter;
        $unicodeMessage = 'Unicode: 你好世界 🎉 émoji';
        $formatted = $formatter->format('info', $unicodeMessage, []);

        $this->assertStringContainsString($unicodeMessage, $formatted);
    }

    /**
     * Tests that the formatter handles very long messages.
     */
    public function test_handles_very_long_message(): void
    {
        $formatter = new SimpleLogEntryFormatter;
        $longMessage = \str_repeat('A', 10000);
        $formatted = $formatter->format('debug', $longMessage, []);

        $this->assertStringContainsString($longMessage, $formatted);
        $this->assertStringContainsString('DEBUG:', $formatted);
    }

    /**
     * Tests that the formatter produces consistent output format.
     */
    public function test_consistent_output_format(): void
    {
        $formatter = new SimpleLogEntryFormatter;
        $formatted = $formatter->format('warning', 'Test message', []);

        // Should match pattern: [YYYY-MM-DD HH:MM:SS] LEVEL: message\n
        $this->assertMatchesRegularExpression(
            '/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] WARNING: Test message'.\preg_quote(\PHP_EOL, '/').'$/',
            $formatted
        );
    }

    /**
     * Tests that the formatter uses the provided clock for timestamps.
     */
    public function test_uses_provided_clock(): void
    {
        $fixedTime = new \DateTimeImmutable('2024-01-15 14:30:45');
        $clock = new FrozenClock($fixedTime);
        $formatter = new SimpleLogEntryFormatter($clock);
        $formatted = $formatter->format('info', 'Test message', []);

        $this->assertStringContainsString('[2024-01-15 14:30:45]', $formatted);
        $this->assertStringContainsString('INFO:', $formatted);
        $this->assertStringContainsString('Test message', $formatted);
    }

    /**
     * Tests that the formatter uses system clock when no clock is provided.
     */
    public function test_uses_system_clock_by_default(): void
    {
        $formatter = new SimpleLogEntryFormatter;
        $before = new \DateTimeImmutable;
        $formatted = $formatter->format('info', 'Test message', []);
        $after = new \DateTimeImmutable;

        // Extract timestamp from formatted string
        \preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $formatted, $matches);
        $this->assertNotEmpty($matches);
        $timestamp = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $matches[1]);

        // Timestamp should be between before and after
        $this->assertGreaterThanOrEqual($before->getTimestamp(), $timestamp->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $timestamp->getTimestamp());
    }
}
