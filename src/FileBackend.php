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
 * Backend implementation for writing logs to a single file.
 *
 * This backend writes all log entries to a specified file. The file
 * will be created if it doesn't exist, and entries will be appended
 * to the end of the file.
 *
 * @author  Vaibhav Pandey <contact@vaibhavpandey.com>
 */
final class FileBackend implements BackendInterface
{
    /**
     * The path to the log file.
     */
    private readonly string $filePath;

    /**
     * The formatter used to format log entries.
     */
    private readonly LogEntryFormatterInterface $formatter;

    /**
     * Creates a new file backend instance.
     *
     * @param  string  $filePath  The path to the log file
     * @param  LogEntryFormatterInterface|null  $formatter  Optional formatter (defaults to SimpleLogEntryFormatter)
     * @param  ClockInterface|null  $clock  Optional clock instance (defaults to system clock)
     *
     * @throws \InvalidArgumentException If the file path is empty
     * @throws \RuntimeException If the directory cannot be created or file cannot be written
     */
    public function __construct(string $filePath, ?LogEntryFormatterInterface $formatter = null, ?ClockInterface $clock = null)
    {
        if (empty(\trim($filePath))) {
            throw new \InvalidArgumentException('File path cannot be empty');
        }

        $this->filePath = $filePath;
        $this->formatter = $formatter ?? new SimpleLogEntryFormatter($clock);
        $this->ensureDirectoryExists();
    }

    /**
     * Writes a log entry to the file.
     *
     * @param  string  $level  The log level
     * @param  string  $message  The formatted log message
     * @param  array<string, mixed>  $context  The original context data
     */
    public function write(string $level, string $message, array $context): void
    {
        $formattedEntry = $this->formatter->format($level, $message, $context);
        $result = @\file_put_contents($this->filePath, $formattedEntry, \FILE_APPEND | \LOCK_EX);

        if ($result === false) {
            // Silently fail to avoid interrupting application flow
            // In production, you might want to log this to error_log
            return;
        }
    }

    /**
     * Ensures the directory for the log file exists.
     *
     * Creates the directory structure if it doesn't exist.
     *
     * @throws \RuntimeException If the directory cannot be created
     */
    private function ensureDirectoryExists(): void
    {
        $directory = \dirname($this->filePath);

        if (! \is_dir($directory)) {
            if (! @\mkdir($directory, 0755, true)) {
                throw new \RuntimeException(
                    \sprintf('Cannot create directory for log file: %s', $directory)
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
