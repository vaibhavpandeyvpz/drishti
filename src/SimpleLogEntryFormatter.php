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

        $timestamp = \date('Y-m-d H:i:s');

        return \sprintf('[%s] %s: %s%s', $timestamp, \strtoupper($level), $interpolatedMessage, \PHP_EOL);
    }

    /**
     * Interpolates context values into the message placeholders.
     *
     * Replaces placeholders in the form {key} with the corresponding value
     * from the context array. If a placeholder is not found in the context,
     * it remains unchanged in the message.
     *
     * @param  string  $message  The message with placeholders
     * @param  array<string, mixed>  $context  The context data
     * @return string The interpolated message
     */
    private function interpolate(string $message, array $context): string
    {
        $replace = [];

        foreach ($context as $key => $value) {
            // Skip exception as it's handled separately
            if ($key === 'exception' && $value instanceof \Throwable) {
                continue;
            }

            // Convert value to string representation
            $replace['{'.$key.'}'] = match (true) {
                \is_object($value) && \method_exists($value, '__toString') => (string) $value,
                \is_object($value) => \get_class($value),
                \is_array($value) => \json_encode($value, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE),
                \is_resource($value) => \get_resource_type($value),
                \is_bool($value) => $value ? 'true' : 'false',
                $value === null => 'null',
                default => (string) $value,
            };
        }

        return \strtr($message, $replace);
    }
}
