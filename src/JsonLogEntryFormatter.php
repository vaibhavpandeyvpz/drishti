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
 * JSON log entry formatter implementation.
 *
 * Formats log entries as JSON objects, making them suitable for structured
 * logging systems, log aggregation tools, and machine-readable log processing.
 *
 * Example output:
 * {"timestamp":"2024-01-15T10:30:45+00:00","level":"INFO","message":"User logged in","context":{"username":"john"}}
 *
 * @author  Vaibhav Pandey <contact@vaibhavpandey.com>
 */
final readonly class JsonLogEntryFormatter implements LogEntryFormatterInterface
{
    use InterpolatesMessages;

    /**
     * The clock instance for generating timestamps.
     */
    private readonly ClockInterface $clock;

    /**
     * Creates a new JSON log entry formatter.
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
     * Formats a log entry into a JSON string.
     *
     * Interpolates placeholders in the message, handles exceptions, and formats
     * the final entry as a JSON object.
     *
     * @param  string  $level  The log level
     * @param  string  $message  The raw log message (may contain placeholders)
     * @param  array<string, mixed>  $context  The context data for interpolation
     * @return string The formatted log entry as JSON
     */
    public function format(string $level, string $message, array $context): string
    {
        // Interpolate message with context
        $interpolatedMessage = $this->interpolate($message, $context);

        // Build the log entry
        $entry = [
            'timestamp' => $this->clock->now()->format('c'), // ISO 8601 format
            'level' => \strtoupper($level),
            'message' => $interpolatedMessage,
        ];

        // Handle exception in context
        if (isset($context['exception']) && $context['exception'] instanceof \Throwable) {
            $exception = $context['exception'];
            $entry['exception'] = [
                'class' => \get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];

            // Include previous exception if present
            if ($exception->getPrevious() !== null) {
                $previous = $exception->getPrevious();
                $entry['exception']['previous'] = [
                    'class' => \get_class($previous),
                    'message' => $previous->getMessage(),
                    'code' => $previous->getCode(),
                ];
            }
        }

        // Add remaining context (excluding exception which is handled above)
        $remainingContext = $context;
        unset($remainingContext['exception']);

        if (! empty($remainingContext)) {
            $entry['context'] = $remainingContext;
        }

        return \json_encode($entry, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR).\PHP_EOL;
    }
}
