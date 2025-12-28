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
 * Backend implementation for writing logs to daily rotating files.
 *
 * This backend creates a new log file each day, following a logrotate-style
 * pattern. Files are named with the date in the format: filename-YYYY-MM-DD.log
 * This helps with log management and prevents individual log files from
 * growing too large.
 *
 * @author  Vaibhav Pandey <contact@vaibhavpandey.com>
 */
final class DailyFileBackend implements BackendInterface
{
    /**
     * The base path for log files (without date suffix).
     */
    private readonly string $basePath;

    /**
     * The formatter used to format log entries.
     */
    private readonly LogEntryFormatterInterface $formatter;

    /**
     * The clock instance for date-based file rotation.
     */
    private readonly ClockInterface $clock;

    /**
     * The current date string (YYYY-MM-DD) for the active log file.
     */
    private ?string $currentDate = null;

    /**
     * The current file path being used.
     */
    private ?string $currentFilePath = null;

    /**
     * Creates a new daily file backend instance.
     *
     * @param  string  $basePath  The base path for log files. The date suffix
     *                            (YYYY-MM-DD) will be automatically appended.
     *                            Example: '/var/log/app' becomes '/var/log/app-2024-01-15.log'
     * @param  LogEntryFormatterInterface|null  $formatter  Optional formatter (defaults to SimpleLogEntryFormatter)
     * @param  ClockInterface|null  $clock  Optional clock instance (defaults to system clock)
     *
     * @throws \InvalidArgumentException If the base path is empty
     * @throws \RuntimeException If the directory cannot be created
     */
    public function __construct(string $basePath, ?LogEntryFormatterInterface $formatter = null, ?ClockInterface $clock = null)
    {
        if (empty(\trim($basePath))) {
            throw new \InvalidArgumentException('Base path cannot be empty');
        }

        $this->basePath = $basePath;
        $this->clock = $clock ?? new class implements ClockInterface
        {
            public function now(): \DateTimeImmutable
            {
                return new \DateTimeImmutable;
            }
        };
        $this->formatter = $formatter ?? new SimpleLogEntryFormatter($this->clock);
        $this->ensureDirectoryExists();
    }

    /**
     * Writes a log entry to the current day's log file.
     *
     * Automatically rotates to a new file if the date has changed.
     *
     * @param  string  $level  The log level
     * @param  string  $message  The formatted log message
     * @param  array<string, mixed>  $context  The original context data
     */
    public function write(string $level, string $message, array $context): void
    {
        $today = $this->clock->now()->format('Y-m-d');

        // Rotate to new file if date changed
        if ($this->currentDate !== $today) {
            $this->currentDate = $today;
            $this->currentFilePath = $this->basePath.'-'.$today.'.log';
        }

        $formattedEntry = $this->formatter->format($level, $message, $context);
        $result = @\file_put_contents($this->currentFilePath, $formattedEntry, \FILE_APPEND | \LOCK_EX);

        if ($result === false) {
            // Silently fail to avoid interrupting application flow
            return;
        }
    }

    /**
     * Ensures the directory for the log files exists.
     *
     * Creates the directory structure if it doesn't exist.
     *
     * @throws \RuntimeException If the directory cannot be created
     */
    private function ensureDirectoryExists(): void
    {
        $directory = \dirname($this->basePath);

        // Handle case where basePath is just a filename (no directory)
        if ($directory === '.' || $directory === '') {
            $directory = \getcwd() ?: \sys_get_temp_dir();
        }

        if (! \is_dir($directory)) {
            if (! @\mkdir($directory, 0755, true)) {
                throw new \RuntimeException(
                    \sprintf('Cannot create directory for log files: %s', $directory)
                );
            }
        }

        if (! \is_writable($directory)) {
            throw new \RuntimeException(
                \sprintf('Directory is not writable: %s', $directory)
            );
        }
    }
}
