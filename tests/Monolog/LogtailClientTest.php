<?php

namespace Logtail\Monolog;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/IngestStub.php';

class RetryProbeLogtailClient extends LogtailClient
{
    public int $executeCalls = 0;
    /** @var \CurlHandle[] */
    public array $handles = [];
    private int $failuresToSimulate;

    public function __construct(int $failuresToSimulate)
    {
        parent::__construct("test-source-token");
        $this->failuresToSimulate = $failuresToSimulate;
    }

    protected function execute(): void
    {
        $this->executeCalls++;
        $this->handles[] = (new \ReflectionProperty(LogtailClient::class, 'handle'))->getValue($this);

        if ($this->executeCalls <= $this->failuresToSimulate) {
            throw new \RuntimeException('Curl error (code 16): simulated HTTP/2 framing error');
        }
    }
}

class LogtailClientTest extends TestCase
{
    private IngestStub $ingest;

    protected function tearDown(): void
    {
        if (isset($this->ingest)) {
            $this->ingest->stop();
        }
    }

    public function testSucceedsOnFirstAttemptWithoutRetry(): void
    {
        $client = new RetryProbeLogtailClient(0);

        $client->send('{"message":"hi"}');

        $this->assertSame(1, $client->executeCalls);
    }

    public function testRetriesTransientFailureOnAFreshConnectionThenSucceeds(): void
    {
        $client = new RetryProbeLogtailClient(LogtailClient::MAX_SEND_ATTEMPTS - 1);

        $client->send('{"message":"hi"}');

        $this->assertSame(LogtailClient::MAX_SEND_ATTEMPTS, $client->executeCalls);
        // every attempt must run on a rebuilt handle; reusing the poisoned HTTP/2
        // connection would just reproduce the same framing error. Holding the handle
        // refs keeps their object ids from being recycled after unset.
        $ids = \array_map('spl_object_id', $client->handles);
        $this->assertCount(LogtailClient::MAX_SEND_ATTEMPTS, \array_unique($ids));
    }

    public function testGivesUpAfterMaxAttempts(): void
    {
        $client = new RetryProbeLogtailClient(PHP_INT_MAX);

        try {
            $client->send('{"message":"hi"}');
            $this->fail('Expected RuntimeException after exhausting retries');
        } catch (\RuntimeException $exception) {
            $this->assertSame(LogtailClient::MAX_SEND_ATTEMPTS, $client->executeCalls);
        }
    }

    public function testReusesTheConnectionAcrossSends(): void
    {
        $client = new RetryProbeLogtailClient(0);

        $client->send('{"message":"hi"}');
        $client->send('{"message":"hi again"}');

        $this->assertCount(1, \array_unique(\array_map('spl_object_id', $client->handles)));
    }

    public function testCloseDropsTheConnectionSoTheNextSendOpensAFreshOne(): void
    {
        $client = new RetryProbeLogtailClient(0);

        $client->send('{"message":"hi"}');
        $client->close();
        $client->send('{"message":"hi again"}');

        $this->assertCount(2, \array_unique(\array_map('spl_object_id', $client->handles)));
    }

    public function testAcceptedLogsAreSentOnce(): void
    {
        $this->ingest = new IngestStub;
        $client = new LogtailClient("test-source-token", $this->ingest->endpointAnswering(202));

        $client->send('{"message":"hi"}');

        $this->assertSame(1, $this->ingest->requestCount());
    }

    public function testUnauthorizedResponseThrowsWithoutRetrying(): void
    {
        $this->ingest = new IngestStub;
        $endpoint = $this->ingest->endpointAnswering(401);
        $client = new LogtailClient("wrong-source-token", $endpoint);

        try {
            $client->send('{"message":"hi"}');
            $this->fail('Expected LogtailIngestException for HTTP 401');
        } catch (LogtailIngestException $exception) {
            $this->assertSame(
                "{$endpoint} answered HTTP 401: the source token or the ingesting host is wrong. Copy both from your source's settings in Better Stack.",
                $exception->getMessage()
            );
            $this->assertSame(401, $exception->getCode());
        }

        $this->assertSame(1, $this->ingest->requestCount());
    }

    public function testServerErrorThrowsWithTheStartOfTheResponseBody(): void
    {
        $this->ingest = new IngestStub;
        $endpoint = $this->ingest->endpointAnswering(503);
        $client = new LogtailClient("test-source-token", $endpoint);

        try {
            $client->send('{"message":"hi"}');
            $this->fail('Expected LogtailIngestException for HTTP 503');
        } catch (LogtailIngestException $exception) {
            $this->assertSame(
                "{$endpoint} answered HTTP 503: upstream connect error or disconnect/reset before headers. " . \str_repeat('x', 141) . '...',
                $exception->getMessage()
            );
            $this->assertSame(503, $exception->getCode());
        }

        $this->assertSame(1, $this->ingest->requestCount());
    }
}
