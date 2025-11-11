<?php

namespace App\Logging;

use App\Services\Logging\LogSanitizer;
use Monolog\Formatter\LineFormatter;
use Monolog\LogRecord;

/**
 * 🔒 GDPR-Compliant Log Formatter
 *
 * Automatically sanitizes PII from all log records before writing to storage.
 *
 * CONFIGURATION:
 * In config/logging.php, use this formatter:
 *
 * 'production' => [
 *     'driver' => 'daily',
 *     'path' => storage_path('logs/laravel.log'),
 *     'level' => 'info',
 *     'days' => 14,
 *     'formatter' => App\Logging\SanitizingFormatter::class,
 * ],
 */
class SanitizingFormatter extends LineFormatter
{
    /**
     * Format a log record with PII sanitization
     *
     * @param LogRecord $record
     * @return string
     */
    public function format(LogRecord $record): string
    {
        // Create a copy of the record with sanitized data
        $sanitizedRecord = new LogRecord(
            datetime: $record->datetime,
            channel: $record->channel,
            level: $record->level,
            message: LogSanitizer::sanitizeString($record->message),
            context: LogSanitizer::sanitize($record->context),
            extra: LogSanitizer::sanitize($record->extra),
            formatted: $record->formatted
        );

        // Use parent formatter for final formatting
        return parent::format($sanitizedRecord);
    }
}
