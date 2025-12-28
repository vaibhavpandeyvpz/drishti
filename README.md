# Drishti

[![Latest Version](https://img.shields.io/packagist/v/vaibhavpandeyvpz/drishti.svg?style=flat-square)](https://packagist.org/packages/vaibhavpandeyvpz/drishti)
[![Downloads](https://img.shields.io/packagist/dt/vaibhavpandeyvpz/drishti.svg?style=flat-square)](https://packagist.org/packages/vaibhavpandeyvpz/drishti)
[![PHP Version](https://img.shields.io/packagist/php-v/vaibhavpandeyvpz/drishti.svg?style=flat-square)](https://packagist.org/packages/vaibhavpandeyvpz/drishti)
[![License](https://img.shields.io/packagist/l/vaibhavpandeyvpz/drishti.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/github/actions/workflow/status/vaibhavpandeyvpz/drishti/tests.yml?branch=master&style=flat-square)](https://github.com/vaibhavpandeyvpz/drishti/actions)

A lightweight, flexible [PSR-3](https://www.php-fig.org/psr/psr-3/) compliant logging library with configurable backends, [PSR-20](https://www.php-fig.org/psr/psr-20/) Clock support, and minimal dependencies.

> **Drishti** (`दृष्टि`) - Sanskrit for "Vision" or "Sight", representing the ability to see and record what happens in your application.

## Features

- ✅ **PSR-3 Compliant** - Full implementation of the PSR-3 LoggerInterface specification
- ✅ **PSR-20 Clock Support** - Uses PSR-20 ClockInterface for time operations (testable and flexible)
- ✅ **Minimal Dependencies** - Only requires PSR-3 interface (psr/log) and PSR-20 Clock (psr/clock)
- ✅ **Configurable Backends** - Contract-based backend system for flexible log destinations
- ✅ **Multiple Backends** - Send logs to multiple destinations simultaneously
- ✅ **First-Party Backends** - Built-in support for stdout, stderr, files, and daily rotating files
- ✅ **First-Party Formatters** - Built-in SimpleLogEntryFormatter and JsonLogEntryFormatter
- ✅ **Message Interpolation** - Automatic placeholder replacement with context data
- ✅ **Exception Handling** - Automatic exception information in log entries
- ✅ **All Log Levels** - Supports all 8 PSR-3 log levels
- ✅ **Modern PHP** - Built for PHP 8.2+ with modern language features
- ✅ **100% Test Coverage** - Fully tested with comprehensive test suite

## Installation

Install via Composer:

```bash
composer require vaibhavpandeyvpz/drishti
```

## Quick Start

```php
<?php

use Drishti\Logger;

// Create a new logger instance
$logger = new Logger();

// Log messages at different levels
$logger->info('User logged in');
$logger->warning('Disk space is running low');
$logger->error('Failed to connect to database');

// Use context for message interpolation
$logger->info('User {username} logged in from {ip}', [
    'username' => 'john',
    'ip' => '192.168.1.1',
]);

// For structured logging (JSON format)
use Drishti\JsonLogEntryFormatter;
$jsonLogger = new Logger(new FileBackend('/var/log/app.json', new JsonLogEntryFormatter()));
$jsonLogger->info('User {username} logged in from {ip}', [
    'username' => 'john',
    'ip' => '192.168.1.1',
]);

// Log exceptions
try {
    // Some code that might throw
} catch (\Exception $e) {
    $logger->error('An error occurred', [
        'exception' => $e,
    ]);
}
```

## Usage

### Basic Logging

The `Logger` class implements the PSR-3 `LoggerInterface` and provides methods for all standard log levels:

```php
use Drishti\Logger;

$logger = new Logger();

// Emergency: System is unusable
$logger->emergency('System is down');

// Alert: Action must be taken immediately
$logger->alert('Database connection lost');

// Critical: Critical conditions
$logger->critical('Application component unavailable');

// Error: Runtime errors
$logger->error('Failed to process request');

// Warning: Exceptional occurrences
$logger->warning('Deprecated API used');

// Notice: Normal but significant events
$logger->notice('User profile updated');

// Info: Interesting events
$logger->info('User logged in');

// Debug: Detailed debug information
$logger->debug('Processing request with ID: 12345');
```

### Message Interpolation

Drishti supports PSR-3 message interpolation using placeholders in the form `{key}`:

```php
$logger->info('User {username} performed {action} on {resource}', [
    'username' => 'admin',
    'action' => 'delete',
    'resource' => 'file.txt',
]);
// Output: User admin performed delete on file.txt
```

### Context Data Types

The logger handles various context data types:

```php
// Strings and numbers
$logger->info('Processed {count} items', ['count' => 42]);

// Booleans
$logger->info('Feature enabled: {enabled}', ['enabled' => true]);

// Arrays (JSON encoded)
$logger->info('User roles: {roles}', ['roles' => ['admin', 'user']]);

// Objects (class name or __toString() result)
$logger->info('Object: {obj}', ['obj' => $myObject]);

// Null values
$logger->info('User: {user}', ['user' => null]);
```

### Exception Logging

When an exception is included in the context with the key `exception`, Drishti automatically appends exception details:

```php
try {
    // Some code that might throw
    throw new \RuntimeException('Something went wrong', 500);
} catch (\Exception $e) {
    $logger->error('An error occurred', [
        'exception' => $e,
    ]);
}
// Output includes: [Exception: Something went wrong in /path/to/file.php:123]
```

### Using the log() Method

You can also use the generic `log()` method with any valid PSR-3 log level:

```php
use Psr\Log\LogLevel;

$logger->log(LogLevel::INFO, 'Custom log message');
$logger->log(LogLevel::ERROR, 'Error occurred', ['error_code' => 500]);
```

### Stringable Messages

Messages can be strings or objects implementing `\Stringable`:

```php
$message = new class implements \Stringable {
    public function __toString(): string
    {
        return 'Dynamic message';
    }
};

$logger->info($message);
```

### Backend System

Drishti uses a contract-based backend system that allows you to configure where logs are written. You can use one or more backends simultaneously.

#### Default Behavior

If no backends are configured, Drishti uses default behavior:

- **Error levels** (emergency, alert, critical, error): Written to `STDERR`
- **Informational levels** (warning, notice, info, debug): Written to `STDOUT`

#### Using Backends

Configure backends when creating the logger:

```php
use Drishti\Logger;
use Drishti\StdioBackend;
use Drishti\FileBackend;
use Drishti\DailyFileBackend;

// Single backend
$logger = new Logger(StdioBackend::stdout());

// Multiple backends
$logger = new Logger([
    StdioBackend::stdout(),
    new FileBackend('/var/log/app.log'),
]);

// Or add backends after creation
$logger = new Logger();
$logger->addBackend(StdioBackend::stdout())
       ->addBackend(new FileBackend('/var/log/app.log'));
```

#### Available Backends

##### StdioBackend

Writes logs to standard I/O streams (STDOUT or STDERR). Use static factory methods to create instances:

```php
use Drishti\Logger;
use Drishti\StdioBackend;

// Write to STDOUT (ideal for containerized applications)
$logger = new Logger(StdioBackend::stdout());
$logger->info('This goes to STDOUT');

// Write to STDERR (useful for separating logs from application output)
$logger = new Logger(StdioBackend::stderr());
$logger->error('This goes to STDERR');

// With custom formatter
use Drishti\JsonLogEntryFormatter;
$formatter = new JsonLogEntryFormatter();
$logger = new Logger(StdioBackend::stdout($formatter));

// With custom clock (for testing or timezone control)
use Psr\Clock\ClockInterface;
use Samay\FrozenClock;
$clock = new FrozenClock(new \DateTimeImmutable('2024-01-15 10:00:00'));
$formatter = new JsonLogEntryFormatter($clock);
$logger = new Logger(StdioBackend::stdout($formatter));
```

##### FileBackend

Writes logs to a single file. The directory will be created automatically if it doesn't exist:

```php
use Drishti\Logger;
use Drishti\FileBackend;

$logger = new Logger(new FileBackend('/var/log/app.log'));
$logger->info('This is written to the file');

// With custom clock
use Psr\Clock\ClockInterface;
$clock = new MyCustomClock();
$logger = new Logger(new FileBackend('/var/log/app.log', null, $clock));
```

##### DailyFileBackend

Writes logs to daily rotating files (logrotate style). Files are named with the date suffix:

```php
use Drishti\Logger;
use Drishti\DailyFileBackend;

$logger = new Logger(new DailyFileBackend('/var/log/app'));
// Creates files like: /var/log/app-2024-01-15.log
// Automatically rotates to new file each day
$logger->info('This goes to today\'s log file');

// With custom clock (useful for testing date rotation)
use Psr\Clock\ClockInterface;
$clock = new MyCustomClock();
$logger = new Logger(new DailyFileBackend('/var/log/app', null, $clock));
```

#### Custom Backends

Create your own backend by implementing `BackendInterface`:

```php
use Drishti\BackendInterface;
use Drishti\LogEntryFormatterInterface;

class DatabaseBackend implements BackendInterface
{
    public function __construct(
        private readonly \PDO $database,
        private readonly ?LogEntryFormatterInterface $formatter = null
    ) {}

    public function write(string $level, string $message, array $context): void
    {
        // Format the message using formatter (or your own logic)
        $formatted = $this->formatter?->format($level, $message, $context)
            ?? "[$level] $message";

        // Write to database, external service, etc.
        $this->database->prepare('INSERT INTO logs (level, message, context, created_at) VALUES (?, ?, ?, ?)')
            ->execute([
                $level,
                $message,
                json_encode($context),
                (new \DateTimeImmutable)->format('Y-m-d H:i:s'),
            ]);
    }
}

$logger = new Logger(new DatabaseBackend($pdo));
```

#### Multiple Backends

Send logs to multiple destinations simultaneously:

```php
use Drishti\Logger;
use Drishti\StdioBackend;
use Drishti\FileBackend;
use Drishti\DailyFileBackend;

$logger = new Logger([
    StdioBackend::stdout(),                  // Console output
    new FileBackend('/var/log/app.log'),      // Single file
    new DailyFileBackend('/var/log/daily'),   // Daily rotating files
]);

$logger->info('This message goes to all three backends');
```

### Formatters

Drishti includes two first-party formatters and supports custom formatters.

#### SimpleLogEntryFormatter (Default)

The default formatter produces human-readable log entries:

```php
use Drishti\Logger;
use Drishti\FileBackend;
use Drishti\SimpleLogEntryFormatter;

// SimpleLogEntryFormatter is used by default
$logger = new Logger(new FileBackend('/var/log/app.log'));

// Or explicitly specify it
$formatter = new SimpleLogEntryFormatter();
$logger = new Logger(new FileBackend('/var/log/app.log', $formatter));
```

Output format: `[2024-01-15 10:30:45] INFO: User logged in`

#### JsonLogEntryFormatter

For structured logging, use the JSON formatter:

```php
use Drishti\Logger;
use Drishti\FileBackend;
use Drishti\JsonLogEntryFormatter;

$formatter = new JsonLogEntryFormatter();
$logger = new Logger(new FileBackend('/var/log/app.json', $formatter));
$logger->info('User {username} logged in', ['username' => 'john', 'ip' => '192.168.1.1']);
```

Output format:

```json
{
    "timestamp": "2024-01-15T10:30:45+00:00",
    "level": "INFO",
    "message": "User john logged in",
    "context": { "username": "john", "ip": "192.168.1.1" }
}
```

The JSON formatter includes:

- ISO 8601 timestamp
- Uppercased log level
- Interpolated message
- Exception details (if present)
- Remaining context data

#### Custom Formatters

You can create custom formatters by implementing `LogEntryFormatterInterface`:

```php
use Drishti\LogEntryFormatterInterface;
use Psr\Clock\ClockInterface;

class CustomFormatter implements LogEntryFormatterInterface
{
    public function __construct(
        private readonly ?ClockInterface $clock = null
    ) {
        $this->clock = $clock ?? new class implements ClockInterface {
            public function now(): \DateTimeImmutable {
                return new \DateTimeImmutable;
            }
        };
    }

    public function format(string $level, string $message, array $context): string
    {
        // Your custom formatting logic
        // Interpolate message, handle exceptions, format output
        return $formatted;
    }
}

// Use custom formatter with any backend
$formatter = new CustomFormatter();
$logger = new Logger(new FileBackend('/var/log/app.log', $formatter));
```

### PSR-20 Clock Support

Drishti uses [PSR-20 ClockInterface](https://www.php-fig.org/psr/psr-20/) for all time operations, making it testable and flexible. All backends and formatters accept an optional `ClockInterface` instance.

For testing, you can use a frozen clock like [samay](https://github.com/vaibhavpandeyvpz/samay):

```php
use Drishti\Logger;
use Drishti\FileBackend;
use Samay\FrozenClock;

// In tests, use a frozen clock for predictable timestamps
$fixedTime = new \DateTimeImmutable('2024-01-15 10:30:45');
$clock = new FrozenClock($fixedTime);
$logger = new Logger(new FileBackend('/var/log/test.log', null, $clock));

$logger->info('Test message');
// Timestamp will always be 2024-01-15 10:30:45
```

### Output Format

All log entries include a timestamp in the format `[YYYY-MM-DD HH:MM:SS]`.

Example output:

```
[2024-01-15 10:30:45] INFO: User john logged in from 192.168.1.1
[2024-01-15 10:30:46] ERROR: Database connection failed [Exception: Connection timeout in /app/Database.php:42]
```

## Requirements

- PHP 8.2 or higher
- PSR-3 interface (psr/log)
- PSR-20 Clock interface (psr/clock)

For testing, we recommend [samay](https://github.com/vaibhavpandeyvpz/samay) for frozen clocks:

```bash
composer require --dev vaibhavpandeyvpz/samay
```

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

For issues, questions, or contributions, please visit the [GitHub repository](https://github.com/vaibhavpandeyvpz/drishti).
