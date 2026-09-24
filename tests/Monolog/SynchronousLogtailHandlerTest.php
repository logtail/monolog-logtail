<?php

namespace Logtail\Monolog;

use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class FailingLogtailClient extends LogtailClient
{
    public function __construct()
    {
        parent::__construct("test-source-token");
    }

    public function send($data): void
    {
        throw new \RuntimeException('Curl error (code 28): Operation timed out after 5000 milliseconds with 0 bytes received');
    }
}

class SynchronousLogtailHandlerTest extends TestCase
{
    public function testSendFailureIsAWarningByDefault(): void
    {
        $handler = new SynchronousLogtailHandler('sourceTokenXYZ');
        (function () { $this->client = new FailingLogtailClient; })->call($handler);
        $logger = new Logger('test');
        $logger->pushHandler($handler);

        $this->expectException(\PHPUnit\Framework\Error\Warning::class);
        $this->expectExceptionMessage('Failed to send a single log record to Better Stack because of RuntimeException: Curl error (code 28)');

        $logger->debug('test message');
    }

    public function testSendFailureIsThrownWhenAskedTo(): void
    {
        $handler = new SynchronousLogtailHandler('sourceTokenXYZ', throwExceptions: true);
        (function () { $this->client = new FailingLogtailClient; })->call($handler);
        $logger = new Logger('test');
        $logger->pushHandler($handler);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Curl error (code 28)');

        $logger->debug('test message');
    }
}
