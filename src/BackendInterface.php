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
 * Interface for log backends.
 *
 * Backends are responsible for writing log entries to their respective
 * destinations (files, streams, services, etc.). Multiple backends can
 * be registered with a Logger instance to write logs to multiple destinations.
 * Each backend can have its own formatter to customize the log output format.
 *
 * @author  Vaibhav Pandey <contact@vaibhavpandey.com>
 */
interface BackendInterface
{
    /**
     * Writes a log entry to the backend.
     *
     * The backend receives the log level, raw message (with placeholders), and context.
     * It uses its configured formatter to interpolate placeholders and format the entry,
     * then writes it to its destination.
     * The backend should handle any errors internally and not throw exceptions
     * that would interrupt logging.
     *
     * @param  string  $level  The log level (e.g., 'emergency', 'error', 'info')
     * @param  string  $message  The raw log message (may contain placeholders like {key})
     * @param  array<string, mixed>  $context  The context data for interpolation
     */
    public function write(string $level, string $message, array $context): void;
}
