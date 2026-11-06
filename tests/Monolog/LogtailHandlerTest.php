<?php

namespace Logtail\Monolog;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\BufferHandler;
use Monolog\LogRecord;

require_once __DIR__ . '/IngestStub.php';
require_once __DIR__ . '/Checkout.php';

require_once __DIR__ . '/IngestStub.php';

class MockLogtailClient extends LogtailClient {
    public $capturedData = NULL;

    public function __construct()
    {
        parent::__construct("test-source-token");
    }

    public function send($data): void
    {
        $this->capturedData = $data;
    }
}

class ConnectionProbeLogtailClient extends LogtailClient {
    public array $sent = [];

    public function __construct()
    {
        parent::__construct("test-source-token");
    }

    public function send($data): void
    {
        $this->sent[] = $data;
        parent::send($data);
    }

    protected function execute(): void
    {
    }

    public function hasConnection(): bool
    {
        return (new \ReflectionProperty(LogtailClient::class, 'handle'))->isInitialized($this);
    }
}

class RecordingLogtailClient extends LogtailClient {
    public array $sent = [];

    public function send($data): void
    {
        $this->sent[] = $data;
        parent::send($data);
    }
}

class LogtailHandlerTest extends \PHPUnit\Framework\TestCase {
    private IngestStub $ingest;

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

    protected function tearDown(): void
    {
        if (isset($this->ingest)) {
            $this->ingest->stop();
        }
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

    public function testSynchronousHandlerResetDropsTheConnection() {
        $handler = new \Logtail\Monolog\SynchronousLogtailHandler('sourceTokenXYZ');
        $client = new ConnectionProbeLogtailClient;
        (function() use ($client) { $this->client = $client; })->call($handler);

        $logger = new \Monolog\Logger('test');
        $logger->pushHandler($handler);
        $logger->debug('test message');
        $this->assertTrue($client->hasConnection());

        // what Laravel Octane does to every logger before each request
        $logger->reset();

        $this->assertFalse($client->hasConnection());
    }

    public function testSynchronousHandlerCloseDropsTheConnection() {
        $handler = new \Logtail\Monolog\SynchronousLogtailHandler('sourceTokenXYZ');
        $client = new ConnectionProbeLogtailClient;
        (function() use ($client) { $this->client = $client; })->call($handler);

        $logger = new \Monolog\Logger('test');
        $logger->pushHandler($handler);
        $logger->debug('test message');
        $this->assertTrue($client->hasConnection());

        $logger->close();

        $this->assertFalse($client->hasConnection());
    }

    public function testBufferedHandlerResetFlushesThenDropsTheConnection() {
        $synchronousHandler = new \Logtail\Monolog\SynchronousLogtailHandler('sourceTokenXYZ');
        $handler = new LogtailHandler('sourceTokenXYZ');
        $client = new ConnectionProbeLogtailClient;
        (function() use ($client) { $this->client = $client; })->call($synchronousHandler);
        (function() use ($synchronousHandler) { $this->handler = $synchronousHandler; })->call($handler);

        $logger = new \Monolog\Logger('test');
        $logger->pushHandler($handler);
        $logger->debug('test message');
        $this->assertSame([], $client->sent);

        $logger->reset();

        $this->assertCount(1, $client->sent);
        $this->assertFalse($client->hasConnection());
    }

    public function testAcceptedBatchIsSentOnce() {
        $this->ingest = new IngestStub;
        $synchronousHandler = new SynchronousLogtailHandler('sourceTokenXYZ');
        $handler = new LogtailHandler('sourceTokenXYZ', flushIntervalMs: null);
        $client = new RecordingLogtailClient('sourceTokenXYZ', $this->ingest->endpointAnswering(202));
        (function() use ($client) { $this->client = $client; })->call($synchronousHandler);
        (function() use ($synchronousHandler) { $this->handler = $synchronousHandler; })->call($handler);

        $logger = new \Monolog\Logger('test');
        $logger->pushHandler($handler);
        $logger->debug('first message');
        $logger->debug('second message');

        $handler->flush();
        $handler->flush();
        $handler->close();

        $this->assertSame(1, $this->ingest->requestCount());
        $this->assertSame(
            [['first message', 'second message']],
            array_map(fn(string $batch) => array_column(\json_decode($batch, true), 'message'), $client->sent)
        );
    }

    public function testRejectedBatchIsThrownOnceAndNotSentAgain() {
        $this->ingest = new IngestStub;
        $endpoint = $this->ingest->endpointAnswering(401);
        $handler = new LogtailHandler('wrongSourceToken', endpoint: $endpoint, flushIntervalMs: null, throwExceptions: true);
        $logger = new \Monolog\Logger('test');
        $logger->pushHandler($handler);
        $logger->debug('test message');

        // close() is also what BufferHandler's shutdown function runs at the end of the script
        $thrown = $this->messagesThrownBy(
            LogtailIngestException::class,
            fn() => $handler->flush(),
            fn() => $handler->flush(),
            fn() => $handler->close(),
        );

        $this->assertSame(
            ["{$endpoint} answered HTTP 401: {\"error\": \"Unauthorized\"}. Check that the source token and the ingesting host match your source's settings in Better Stack."],
            $thrown
        );
        $this->assertSame(1, $this->ingest->requestCount());
    }

    public function testRejectedBatchIsOneWarningAndNotSentAgain() {
        $this->ingest = new IngestStub;
        $endpoint = $this->ingest->endpointAnswering(401);
        $handler = new LogtailHandler('wrongSourceToken', endpoint: $endpoint, flushIntervalMs: null);
        $logger = new \Monolog\Logger('test');
        $logger->pushHandler($handler);
        $logger->debug('test message');

        // what PHP does without an error handler: report the warning and carry on
        $warnings = [];
        set_error_handler(function(int $level, string $message) use (&$warnings) {
            $warnings[] = $message;
            return true;
        }, E_USER_WARNING);
        try {
            $handler->flush();
            $handler->flush();
            $handler->close();
        } finally {
            restore_error_handler();
        }

        $this->assertCount(1, $warnings);
        $this->assertStringStartsWith("Failed to send 1 log records to Better Stack because of Logtail\\Monolog\\LogtailIngestException: {$endpoint} answered HTTP 401", $warnings[0]);
        $this->assertSame(1, $this->ingest->requestCount());
    }

    public function testRejectedBatchIsNotSentAgainWhenTheWarningIsThrown() {
        $this->ingest = new IngestStub;
        $endpoint = $this->ingest->endpointAnswering(401);
        $handler = new LogtailHandler('wrongSourceToken', endpoint: $endpoint, flushIntervalMs: null);
        $logger = new \Monolog\Logger('test');
        $logger->pushHandler($handler);
        $logger->debug('test message');

        // PHPUnit turns the warning into an exception, as Laravel's error handler does
        $warnings = $this->messagesThrownBy(
            \PHPUnit\Framework\Error\Warning::class,
            fn() => $handler->flush(),
            fn() => $handler->flush(),
            fn() => $handler->close(),
        );

        $this->assertCount(1, $warnings);
        $this->assertStringStartsWith("Failed to send 1 log records to Better Stack because of Logtail\\Monolog\\LogtailIngestException: {$endpoint} answered HTTP 401", $warnings[0]);
        $this->assertSame(1, $this->ingest->requestCount());
    }

    private function messagesThrownBy(string $class, callable ...$steps): array {
        $messages = [];
        foreach ($steps as $step) {
            try {
                $step();
            } catch (\Throwable $throwable) {
                if (!$throwable instanceof $class) {
                    throw $throwable;
                }
                $messages[] = $throwable->getMessage();
            }
        }

        return $messages;
    }

    public function testBufferedRecordsCarryTheExtraOfTheCodeThatLoggedThem() {
        $this->ingest = new IngestStub;
        $handler = new LogtailHandler('sourceTokenXYZ', endpoint: $this->ingest->endpointAnswering(202));
        $logger = new \Monolog\Logger('test');
        $logger->pushHandler($handler);

        (new \App\Checkout)->placeOrder($logger);
        $logger->close();

        $extra = \json_decode($this->ingest->requestBodies()[0], true)[0]['monolog']['extra'];
        $this->assertSame(\gethostname(), $extra['hostname'] ?? null);
        $this->assertSame(\getmypid(), $extra['process_id'] ?? null);
        $this->assertSame(__DIR__ . '/Checkout.php', $extra['file'] ?? null);
        $this->assertSame(15, $extra['line'] ?? null);
        $this->assertSame('App\\Checkout', $extra['class'] ?? null);
        $this->assertSame('placeOrder', $extra['function'] ?? null);
    }

    public function testBufferedRecordsAreProcessedOnce() {
        $this->ingest = new IngestStub;
        $handler = new LogtailHandler('sourceTokenXYZ', endpoint: $this->ingest->endpointAnswering(202));
        $handler->pushProcessor(function (LogRecord $record): LogRecord {
            $record->extra['processed'] = ($record->extra['processed'] ?? 0) + 1;
            return $record;
        });
        $logger = new \Monolog\Logger('test');
        $logger->pushHandler($handler);

        $logger->info('first');
        $logger->info('second');
        $logger->close();

        $batch = \json_decode($this->ingest->requestBodies()[0], true);
        $this->assertSame([1, 1], \array_map(fn (array $record) => $record['monolog']['extra']['processed'], $batch));
    }
}
