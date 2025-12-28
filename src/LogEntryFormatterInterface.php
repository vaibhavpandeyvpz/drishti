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
 * Interface for log entry formatters.
 *
 * Formatters are responsible for formatting log entries into strings that
 * can be written by backends. This allows for customization of log output
 * format while keeping backends focused on writing.
 *
 * @author  Vaibhav Pandey <contact@vaibhavpandey.com>
 */
interface LogEntryFormatterInterface
{
    /**
     * Formats a log entry into a string.
     *
     * This method is responsible for:
     * - Interpolating placeholders in the message with context values
     * - Handling exceptions in the context (if present)
     * - Formatting the final log entry with timestamp, level, etc.
     *
     * @param  string  $level  The log level (e.g., 'emergency', 'error', 'info')
     * @param  string  $message  The raw log message (may contain placeholders like {key})
     * @param  array<string, mixed>  $context  The context data for interpolation
     * @return string The formatted log entry ready to be written
     */
    public function format(string $level, string $message, array $context): string;
}
