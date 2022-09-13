<?php

namespace Logtail\Monolog;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\BufferHandler;

class MockLogtailClient {
    public $capturedData = NULL;

    public function send($data) {
        $this->capturedData = $data;
    }
}

class LogtailHandlerTest extends \PHPUnit\Framework\TestCase {
    protected function setUp(): void
    {
        // set global $_SERVER data
        global $_SERVER;
        $_SERVER = array_merge($_SERVER, [
            'REQUEST_URI' => '',
            'REMOTE_ADDR' => '',
            'REQUEST_METHOD' => '',
            'SERVER_NAME' => '',
            'HTTP_REFERER' => '',
        ]);
    }


    public function testHandlerWrite() {
        $handler = new \Logtail\Monolog\SynchronousLogtailHandler('sourceTokenXYZ');

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


    public function testHandlerWriteWithLineFormatter() {
        $handler = new \Logtail\Monolog\SynchronousLogtailHandler('sourceTokenXYZ');

        // test a scenario when the formatter has been set, so the default formatter is not used
        // this is the case with e.g. Laravel
        $handler->setFormatter(new LineFormatter());

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

        $this->assertEquals(0, json_last_error(), "The formatted data is not valid JSON");
    }

    public function testHandlerWriteWithBatchWrite() {
        $synchronousHandler = new \Logtail\Monolog\SynchronousLogtailHandler('sourceTokenXYZ');
        $handler = new LogtailHandler('sourceTokenXYZ');

        // hack: replace the private client object
        $mockClient = new MockLogtailClient;
        $setMockClient = function() use ($mockClient) {
            $this->client = $mockClient;
        };
        $setMockHandler = function() use ($synchronousHandler) {
            $this->handler = $synchronousHandler;
        };

        $setMockClient->call($synchronousHandler);
        $setMockHandler->call($handler);



        $logger = new \Monolog\Logger('test');
        $logger->pushHandler($handler);
        $logger->debug('test message');
        $logger->debug('test message2');
        $handler->flush();

        $decoded = \json_decode($mockClient->capturedData, true);

        $this->assertEquals(0, json_last_error(), "The formatted data is not valid JSON");
        $this->assertTrue(is_array($decoded), "Expected array of logs");
        $this->assertCount(2, $decoded, "Expected two logs");
    }
}
