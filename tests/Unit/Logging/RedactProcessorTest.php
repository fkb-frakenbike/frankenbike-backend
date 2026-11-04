<?php

declare(strict_types=1);

namespace App\Tests\Unit\Logging;

use App\Logging\RedactProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RedactProcessorTest extends TestCase
{
    // Fake, non-secret JWT shape: header.payload.signature (base64url segments).
    private const FAKE_JWT = 'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxIn0.c2lnbmF0dXJlMTIz';

    private function record(string $message = 'msg', array $context = [], array $extra = []): LogRecord
    {
        return new LogRecord(
            new \DateTimeImmutable('2026-01-01T00:00:00+00:00'),
            'app',
            Level::Info,
            $message,
            $context,
            $extra,
        );
    }

    /** @return iterable<string, array{string}> */
    public static function sensitiveKeys(): iterable
    {
        foreach (['password', 'Password', 'plainPassword', 'token', 'refresh_token', 'refreshToken', 'jwt', 'Authorization', 'cookie', 'Set-Cookie', 'email', 'secret', 'api_key'] as $key) {
            yield $key => [$key];
        }
    }

    #[DataProvider('sensitiveKeys')]
    public function testSensitiveContextKeysAreMasked(string $key): void
    {
        $out = (new RedactProcessor())($this->record('msg', [$key => 'some-value']));

        $this->assertSame('[REDACTED]', $out->context[$key]);
    }

    public function testSensitiveKeysAreMaskedInNestedArraysAndExtra(): void
    {
        $out = (new RedactProcessor())($this->record(
            'msg',
            ['request' => ['headers' => ['authorization' => 'Bearer abc'], 'route' => 'api_login']],
            ['user' => ['email' => 'jane@example.com']],
        ));

        $this->assertSame('[REDACTED]', $out->context['request']['headers']['authorization']);
        $this->assertSame('api_login', $out->context['request']['route']);
        $this->assertSame('[REDACTED]', $out->extra['user']['email']);
    }

    public function testEmailAddressesAreMaskedInMessageAndStringValues(): void
    {
        $out = (new RedactProcessor())($this->record(
            'Login failed for jane.doe+tag@example.co.uk from the form',
            ['detail' => 'contact bob@example.com please'],
        ));

        $this->assertSame('Login failed for [REDACTED] from the form', $out->message);
        $this->assertSame('contact [REDACTED] please', $out->context['detail']);
    }

    public function testJwtLikeTokensAreMaskedInMessageAndStringValues(): void
    {
        $out = (new RedactProcessor())($this->record(
            'Invalid token '.self::FAKE_JWT.' rejected',
            ['header' => 'Bearer '.self::FAKE_JWT],
        ));

        $this->assertSame('Invalid token [REDACTED] rejected', $out->message);
        $this->assertSame('Bearer [REDACTED]', $out->context['header']);
    }

    public function testHarmlessDataAndTypesAreLeftUntouched(): void
    {
        $context = ['user_id' => 42, 'route' => 'api_me', 'ok' => true, 'ratio' => 0.5, 'none' => null, 'tags' => ['a', 'b']];

        $out = (new RedactProcessor())($this->record('User projects retrieved', $context));

        $this->assertSame('User projects retrieved', $out->message);
        $this->assertSame($context, $out->context);
    }

    public function testObjectsInContextArePassedThrough(): void
    {
        $exception = new \RuntimeException('boom');

        $out = (new RedactProcessor())($this->record('msg', ['exception' => $exception]));

        $this->assertSame($exception, $out->context['exception']);
    }

    public function testRecordMetadataIsPreserved(): void
    {
        $in = $this->record('hello');

        $out = (new RedactProcessor())($in);

        $this->assertSame($in->level, $out->level);
        $this->assertSame($in->channel, $out->channel);
        $this->assertSame($in->datetime, $out->datetime);
    }
}
