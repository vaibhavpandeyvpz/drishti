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

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * PSR-3 compliant logger implementation.
 *
 * This class provides a simple, no-frills implementation of the PSR-3 LoggerInterface.
 * It supports all standard log levels and context-based message interpolation.
 * Logs can be sent to one or more backends (files, streams, services, etc.).
 *
 * @author  Vaibhav Pandey <contact@vaibhavpandey.com>
 */
final class Logger implements LoggerInterface
{
    /**
     * Array of backends to write logs to.
     *
     * @var array<int, BackendInterface>
     */
    private array $backends = [];

    /**
     * Creates a new logger instance.
     *
     * Optionally accepts one or more backends to write logs to. If no backends
     * are provided, logs will be written to STDOUT for informational levels
     * and STDERR for error levels (maintaining backward compatibility).
     *
     * @param  BackendInterface|array<int, BackendInterface>  $backends  Optional backend(s) to use
     */
    public function __construct(BackendInterface|array $backends = [])
    {
        if (\is_array($backends)) {
            foreach ($backends as $backend) {
                $this->addBackend($backend);
            }
        } else {
            $this->addBackend($backends);
        }
    }

    /**
     * Adds a backend to the logger.
     *
     * Logs will be written to all registered backends. This method supports
     * method chaining.
     *
     * @param  BackendInterface  $backend  The backend to add
     * @return static Returns self for method chaining
     */
    public function addBackend(BackendInterface $backend): static
    {
        $this->backends[] = $backend;

        return $this;
    }

    /**
     * Logs with an arbitrary level.
     *
     * The message MUST be a string or object implementing __toString().
     * The message MAY contain placeholders in the form: {foo} where foo
     * will be replaced by the context data in key "foo".
     *
     * The context array can contain arbitrary data. The only assumption that
     * can be made by implementors is that if an Exception instance is given
     * to produce a stack trace, it MUST be in a key named "exception".
     *
     * @param  mixed  $level  The log level (must be a valid PSR-3 log level)
     * @param  string|\Stringable  $message  The log message
     * @param  array<string, mixed>  $context  Additional context data
     *
     * @throws \Psr\Log\InvalidArgumentException If the log level is invalid
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        // Validate log level
        $validLevels = [
            LogLevel::EMERGENCY,
            LogLevel::ALERT,
            LogLevel::CRITICAL,
            LogLevel::ERROR,
            LogLevel::WARNING,
            LogLevel::NOTICE,
            LogLevel::INFO,
            LogLevel::DEBUG,
        ];

        if (! \in_array($level, $validLevels, true)) {
            throw new \Psr\Log\InvalidArgumentException(
                \sprintf(
                    "Invalid log level '%s'. Must be one of: %s",
                    $level,
                    \implode(', ', $validLevels)
                )
            );
        }

        // Write to all registered backends (each backend formats and interpolates using its own formatter)
        $this->writeToBackends($level, (string) $message, $context);
    }

    /**
     * System is unusable.
     *
     * @param  string|\Stringable  $message  The log message
     * @param  array<string, mixed>  $context  Additional context data
     */
    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param  string|\Stringable  $message  The log message
     * @param  array<string, mixed>  $context  Additional context data
     */
    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param  string|\Stringable  $message  The log message
     * @param  array<string, mixed>  $context  Additional context data
     */
    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param  string|\Stringable  $message  The log message
     * @param  array<string, mixed>  $context  Additional context data
     */
    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param  string|\Stringable  $message  The log message
     * @param  array<string, mixed>  $context  Additional context data
     */
    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param  string|\Stringable  $message  The log message
     * @param  array<string, mixed>  $context  Additional context data
     */
    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param  string|\Stringable  $message  The log message
     * @param  array<string, mixed>  $context  Additional context data
     */
    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param  string|\Stringable  $message  The log message
     * @param  array<string, mixed>  $context  Additional context data
     */
    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * Writes the log entry to all registered backends.
     *
     * If no backends are registered, falls back to default behavior:
     * error levels go to STDERR, informational levels go to STDOUT.
     *
     * @param  string  $level  The log level
     * @param  string  $message  The raw log message (will be interpolated by formatters)
     * @param  array<string, mixed>  $context  The context data
     */
    private function writeToBackends(string $level, string $message, array $context): void
    {
        // If no backends registered, use default behavior for backward compatibility
        if (empty($this->backends)) {
            $errorLevels = [LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::ERROR];
            $backend = \in_array($level, $errorLevels, true)
                ? StdioBackend::stderr()
                : StdioBackend::stdout();
            $backend->write($level, $message, $context);

            return;
        }

        // Write to all registered backends
        foreach ($this->backends as $backend) {
            try {
                $backend->write($level, $message, $context);
            } catch (\Throwable $e) {
                // Silently ignore backend errors to avoid interrupting application flow
                // In production, you might want to log this to error_log
                continue;
            }
        }
    }
}
