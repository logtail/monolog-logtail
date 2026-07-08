<?php

namespace Logtail\Monolog;

use PHPUnit\Framework\TestCase;

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
}
