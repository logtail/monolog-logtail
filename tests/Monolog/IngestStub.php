<?php

namespace Logtail\Monolog;

/**
 * A local HTTP server (PHP's built-in one) that answers every request with the status in its path.
 */
class IngestStub
{
    private $process;
    private string $requestLog;
    private int $port;

    public function __construct()
    {
        $socket = \stream_socket_server('tcp://127.0.0.1:0');
        $this->port = (int) \substr(\strrchr(\stream_socket_get_name($socket, false), ':'), 1);
        \fclose($socket);

        $this->requestLog = \tempnam(\sys_get_temp_dir(), 'ingest-stub-');
        $this->process = \proc_open(
            [PHP_BINARY, '-S', "127.0.0.1:{$this->port}", __DIR__ . '/ingest-stub-router.php'],
            [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes,
            null,
            ['INGEST_STUB_REQUEST_LOG' => $this->requestLog],
        );

        for ($i = 0; $i < 100; $i++) {
            $connection = @\fsockopen('127.0.0.1', $this->port);
            if ($connection !== false) {
                \fclose($connection);
                return;
            }
            \usleep(50_000);
        }

        throw new \RuntimeException("The ingest stub did not start listening on port {$this->port}");
    }

    /**
     * @param int|string $answer a status, or one of the named answers in ingest-stub-router.php
     */
    public function endpointAnswering(int|string $answer): string
    {
        return "http://127.0.0.1:{$this->port}/{$answer}";
    }

    public function requestCount(): int
    {
        return \substr_count(\file_get_contents($this->requestLog), "\n");
    }

    public function stop(): void
    {
        \proc_terminate($this->process);
        \proc_close($this->process);
        \unlink($this->requestLog);
    }
}
