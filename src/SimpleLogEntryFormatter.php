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

use Psr\Clock\ClockInterface;

/**
 * Simple log entry formatter implementation.
 *
 * Formats log entries in a simple, readable format:
 * [YYYY-MM-DD HH:MM:SS] LEVEL: message
 *
 * @author  Vaibhav Pandey <contact@vaibhavpandey.com>
 */
final readonly class SimpleLogEntryFormatter implements LogEntryFormatterInterface
{
    use InterpolatesMessages;

    /**
     * The clock instance for generating timestamps.
     */
    private readonly ClockInterface $clock;

    /**
     * Creates a new simple log entry formatter.
     *
     * @param  ClockInterface|null  $clock  Optional clock instance (defaults to system clock)
     */
    public function __construct(?ClockInterface $clock = null)
    {
        $this->clock = $clock ?? new class implements ClockInterface
        {
            public function now(): \DateTimeImmutable
            {
                return new \DateTimeImmutable;
            }
        };
    }

    /**
     * Formats a log entry into a string.
     *
     * Interpolates placeholders in the message, handles exceptions, and formats
     * the final entry with timestamp and level.
     *
     * @param  string  $level  The log level
     * @param  string  $message  The raw log message (may contain placeholders)
     * @param  array<string, mixed>  $context  The context data for interpolation
     * @return string The formatted log entry
     */
    public function format(string $level, string $message, array $context): string
    {
        // Interpolate message with context
        $interpolatedMessage = $this->interpolate($message, $context);

        // Handle exception in context
        if (isset($context['exception']) && $context['exception'] instanceof \Throwable) {
            $exception = $context['exception'];
            $interpolatedMessage .= \sprintf(
                ' [Exception: %s in %s:%d]',
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            );
        }

        $timestamp = $this->clock->now()->format('Y-m-d H:i:s');

        return \sprintf('[%s] %s: %s%s', $timestamp, \strtoupper($level), $interpolatedMessage, \PHP_EOL);
    }
}
