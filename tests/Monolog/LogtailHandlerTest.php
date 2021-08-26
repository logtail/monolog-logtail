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

        // set global $_SERVER data
        global $_SERVER;
        $_SERVER = array_merge($_SERVER, [
            'REQUEST_URI' => '',
            'REMOTE_ADDR' => '',
            'REQUEST_METHOD' => '',
            'SERVER_NAME' => '',
            'HTTP_REFERER' => '',
        ]);

        $logger = new \Monolog\Logger('test');
        $logger->pushHandler($handler);
        $logger->debug('test message');

        $decoded = \json_decode($mockClient->capturedData, true);

        $this->assertArrayHasKey('monolog', $decoded);
        $this->assertArrayHasKey('extra', $decoded['monolog']);

        // the introspection processor
        $this->assertArrayHasKey('file', $decoded['monolog']['extra']);
        $this->assertArrayHasKey('line', $decoded['monolog']['extra']);
        $this->assertArrayHasKey('class', $decoded['monolog']['extra']);
        $this->assertArrayHasKey('function', $decoded['monolog']['extra']);

        // the web processor
        $this->assertArrayHasKey('url', $decoded['monolog']['extra']);
        $this->assertArrayHasKey('ip', $decoded['monolog']['extra']);
        $this->assertArrayHasKey('http_method', $decoded['monolog']['extra']);
        $this->assertArrayHasKey('server', $decoded['monolog']['extra']);
        $this->assertArrayHasKey('referrer', $decoded['monolog']['extra']);

        // the process ID processor
        $this->assertArrayHasKey('process_id', $decoded['monolog']['extra']);

        // the hostname processor
        $this->assertArrayHasKey('hostname', $decoded['monolog']['extra']);
    }
}
