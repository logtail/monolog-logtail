<?php

namespace Logtail\Monolog;

class MockLogtailClient {
    public $capturedData = NULL;

    public function send(array $data) {
        $this->capturedData = $data;
    }
}

class LogtailHandlerTest extends \PHPUnit\Framework\TestCase {
    public function testHandlerWrite() {
        $handler = new \Logtail\Monolog\LogtailHandler("sourceTokenXYZ");

        $logger = new \Monolog\Logger('test');
        $logger->pushHandler($handler);

        // hack: replace the private client object
        $mockClient = new MockLogtailClient;
        $setMockClient = function() {
            $this->client = $mockClient;
        };
        $setMockClient->call($handler);

        $logger->debug('test message');

        $this->assertEquals($mockClient->capturedData["extra"], NULL);
    }
}
