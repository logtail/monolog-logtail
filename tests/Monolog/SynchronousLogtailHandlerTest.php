<?php

namespace Logtail\Monolog;

use Monolog\Logger;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/IngestStub.php';

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
    private IngestStub $ingest;

    protected function tearDown(): void
    {
        if (isset($this->ingest)) {
            $this->ingest->stop();
        }
    }

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

    public function testAcceptedLogsRaiseNoWarning(): void
    {
        $this->ingest = new IngestStub;
        $handler = new SynchronousLogtailHandler('sourceTokenXYZ', endpoint: $this->ingest->endpointAnswering(202));
        $logger = new Logger('test');
        $logger->pushHandler($handler);

        $logger->debug('test message');

        $this->assertSame(1, $this->ingest->requestCount());
    }

    public function testRejectedLogsAreAWarningByDefault(): void
    {
        $this->ingest = new IngestStub;
        $endpoint = $this->ingest->endpointAnswering(401);
        $handler = new SynchronousLogtailHandler('wrongSourceToken', endpoint: $endpoint);
        $logger = new Logger('test');
        $logger->pushHandler($handler);

        $this->expectException(\PHPUnit\Framework\Error\Warning::class);
        $this->expectExceptionMessage("Failed to send a single log record to Better Stack because of Logtail\\Monolog\\LogtailIngestException: {$endpoint} answered HTTP 401: the source token or the ingesting host is wrong.");

        $logger->debug('test message');
    }

    public function testRejectedLogsAreThrownWhenAskedTo(): void
    {
        $this->ingest = new IngestStub;
        $endpoint = $this->ingest->endpointAnswering(401);
        $handler = new SynchronousLogtailHandler('wrongSourceToken', endpoint: $endpoint, throwExceptions: true);
        $logger = new Logger('test');
        $logger->pushHandler($handler);

        $this->expectException(LogtailIngestException::class);
        $this->expectExceptionMessage("{$endpoint} answered HTTP 401: the source token or the ingesting host is wrong.");

        $logger->debug('test message');
    }

    public function testRejectedBatchIsAWarningByDefault(): void
    {
        $this->ingest = new IngestStub;
        $endpoint = $this->ingest->endpointAnswering(503);
        $handler = new SynchronousLogtailHandler('sourceTokenXYZ', endpoint: $endpoint);
        $logger = new Logger('test');
        $logger->pushHandler($handler);

        $this->expectException(\PHPUnit\Framework\Error\Warning::class);
        $this->expectExceptionMessage("Failed to send 2 log records to Better Stack because of Logtail\\Monolog\\LogtailIngestException: {$endpoint} answered HTTP 503: upstream connect error");

        $handler->handleBatch([
            new \Monolog\LogRecord(new \DateTimeImmutable, 'test', \Monolog\Level::Info, 'first'),
            new \Monolog\LogRecord(new \DateTimeImmutable, 'test', \Monolog\Level::Info, 'second'),
        ]);
    }
}
