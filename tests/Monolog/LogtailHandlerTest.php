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

        $this->assertEquals($decoded['message'], 'test message');
    }
}
