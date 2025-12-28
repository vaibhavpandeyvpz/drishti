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
 * Backend implementation for writing logs to standard I/O streams.
 *
 * This backend can write to either STDOUT or STDERR, making it suitable for
 * applications running in containers or environments where logs are captured
 * from standard output/error streams.
 *
 * @author  Vaibhav Pandey <contact@vaibhavpandey.com>
 */
final class StdioBackend implements BackendInterface
{
    /**
     * The stream resource to write to (STDOUT or STDERR).
     *
     * Note: Cannot be readonly as resources cannot be type-hinted in PHP.
     *
     * @var resource
     */
    private $stream;

    /**
     * The formatter used to format log entries.
     */
    private readonly LogEntryFormatterInterface $formatter;

    /**
     * Creates a new stdio backend instance.
     *
     * @param  resource  $stream  The stream resource to write to (STDOUT or STDERR)
     * @param  LogEntryFormatterInterface|null  $formatter  Optional formatter (defaults to SimpleLogEntryFormatter)
     *
     * @throws \InvalidArgumentException If the stream is not a valid resource
     */
    private function __construct($stream, ?LogEntryFormatterInterface $formatter = null)
    {
        if (! \is_resource($stream)) {
            throw new \InvalidArgumentException('Stream must be a valid resource');
        }

        $this->stream = $stream;
        $this->formatter = $formatter ?? new SimpleLogEntryFormatter;
    }

    /**
     * Creates a backend that writes to standard output (STDOUT).
     *
     * @param  LogEntryFormatterInterface|null  $formatter  Optional formatter (defaults to SimpleLogEntryFormatter)
     * @return static A new StdioBackend instance for STDOUT
     */
    public static function stdout(?LogEntryFormatterInterface $formatter = null): static
    {
        return new self(\STDOUT, $formatter);
    }

    /**
     * Creates a backend that writes to standard error (STDERR).
     *
     * @param  LogEntryFormatterInterface|null  $formatter  Optional formatter (defaults to SimpleLogEntryFormatter)
     * @return static A new StdioBackend instance for STDERR
     */
    public static function stderr(?LogEntryFormatterInterface $formatter = null): static
    {
        return new static(\STDERR, $formatter);
    }

    /**
     * Writes a log entry to the configured stream.
     *
     * @param  string  $level  The log level
     * @param  string  $message  The formatted log message
     * @param  array<string, mixed>  $context  The original context data
     */
    public function write(string $level, string $message, array $context): void
    {
        $formattedEntry = $this->formatter->format($level, $message, $context);
        \fwrite($this->stream, $formattedEntry);
    }
}
