<?php

namespace Logtail\Monolog;

use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class LogtailHandlerBuilderTest extends TestCase
{
    // Nothing listens on the discard port, so every send fails with "connection refused".
    private const UNREACHABLE_ENDPOINT = 'http://127.0.0.1:9';

    private LogtailHandler $handler;

    protected function tearDown(): void
    {
        // The failed record stays buffered; drop it so the destructor does not try to send it again.
        $this->handler->clear();
    }

    public function testSendFailureIsAWarningByDefault(): void
    {
        $this->handler = $handler = LogtailHandlerBuilder::withSourceToken('sourceTokenXYZ')
            ->withEndpoint(self::UNREACHABLE_ENDPOINT)
            ->build();
        $logger = new Logger('test');
        $logger->pushHandler($handler);
        $logger->debug('test message');

        $this->expectException(\PHPUnit\Framework\Error\Warning::class);
        $this->expectExceptionMessage('Failed to send 1 log records to Better Stack');

        $handler->flush();
    }

    public function testSendFailureIsThrownWhenAskedTo(): void
    {
        $this->handler = $handler = LogtailHandlerBuilder::withSourceToken('sourceTokenXYZ')
            ->withEndpoint(self::UNREACHABLE_ENDPOINT)
            ->withExceptionThrowing(true)
            ->build();
        $logger = new Logger('test');
        $logger->pushHandler($handler);
        $logger->debug('test message');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Curl error');

        $handler->flush();
    }
}
