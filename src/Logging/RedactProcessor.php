<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\Attribute\AsMonologProcessor;
use Monolog\LogRecord;

/**
 * Removes personal data and secrets from every log record: values under
 * sensitive keys are replaced, and e-mail addresses / JWT-shaped strings are
 * masked wherever they appear in strings. Objects are left untouched.
 */
#[AsMonologProcessor]
final class RedactProcessor
{
    private const MASK = '[REDACTED]';

    // Matched against the lower-cased key with non-alphanumerics removed.
    private const SENSITIVE_KEY_PARTS = ['password', 'passwd', 'token', 'jwt', 'authorization', 'cookie', 'email', 'secret', 'apikey', 'refresh'];

    private const EMAIL_PATTERN = '/[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}/';
    private const JWT_PATTERN = '/\beyJ[A-Za-z0-9_\-]+\.[A-Za-z0-9_\-]+\.[A-Za-z0-9_\-]*/';

    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            message: $this->maskString($record->message),
            context: $this->redactArray($record->context),
            extra: $this->redactArray($record->extra),
        );
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return array<array-key, mixed>
     */
    private function redactArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && $this->isSensitiveKey($key)) {
                $data[$key] = self::MASK;
            } elseif (is_array($value)) {
                $data[$key] = $this->redactArray($value);
            } elseif (is_string($value)) {
                $data[$key] = $this->maskString($value);
            }
        }

        return $data;
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = preg_replace('/[^a-z0-9]/', '', strtolower($key)) ?? '';

        foreach (self::SENSITIVE_KEY_PARTS as $part) {
            if (str_contains($normalized, $part)) {
                return true;
            }
        }

        return false;
    }

    private function maskString(string $value): string
    {
        // JWT first: its segments could otherwise be partly matched as an address.
        $value = preg_replace(self::JWT_PATTERN, self::MASK, $value) ?? $value;

        return preg_replace(self::EMAIL_PATTERN, self::MASK, $value) ?? $value;
    }
}
