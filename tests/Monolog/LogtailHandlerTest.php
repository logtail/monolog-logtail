<?php

namespace Logtail\Monolog;

class MockLogtailClient {
    public $capturedData = NULL;

    public function send($data) {
        $this->capturedData = $data;
    }
}

class LogtailHandlerTest extends \PHPUnit\Framework\TestCase {
    public function testHandlerWrite() {
        $handler = new \Logtail\Monolog\LogtailHandler('sourceTokenXYZ');
        // hack: replace the private client object
        $mockClient = new MockLogtailClient;
        $setMockClient = function() use ($mockClient) {
            $this->client = $mockClient;
        };
        $setMockClient->call($handler);

        $logger = new \Monolog\Logger('test');
        $logger->pushHandler($handler);
        $logger->debug('test message');

        $decoded = \json_decode($mockClient->capturedData, true);

        $this->assertArrayHasKey('extra', $decoded);

        // the introspection processor
        $this->assertArrayHasKey('file', $decoded['extra']);
        $this->assertArrayHasKey('line', $decoded['extra']);
        $this->assertArrayHasKey('class', $decoded['extra']);
        $this->assertArrayHasKey('function', $decoded['extra']);

        // the web processor
        $this->assertArrayHasKey('url', $decoded['extra']);
        $this->assertArrayHasKey('ip', $decoded['extra']);
        $this->assertArrayHasKey('http_method', $decoded['extra']);
        $this->assertArrayHasKey('server', $decoded['extra']);
        $this->assertArrayHasKey('referrer', $decoded['extra']);

        // the process ID processor
        $this->assertArrayHasKey('process_id', $decoded['extra']);

        // the hostname processor
        $this->assertArrayHasKey('hostname', $decoded['extra']);
    }
}
