<?php

/*
 * This file is part of the logtail/monolog-logtail package.
 *
 * (c) Better Stack
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Logtail\Monolog;

/**
 * Format JSON records for Logtail
 */
class LogtailClient
{
    const URL = "https://in.logs.betterstack.com";

    const DEFAULT_CONNECTION_TIMEOUT_MILLISECONDS = 5000;
    const DEFAULT_TIMEOUT_MILLISECONDS = 5000;
    const MAX_SEND_ATTEMPTS = 3;
    const RETRY_BACKOFF_MILLISECONDS = 100;
    const RESPONSE_EXCERPT_BYTES = 200;

    private string $sourceToken;
    private string $endpoint;
    private \CurlHandle $handle;
    private int $connectionTimeoutMs;
    private int $timeoutMs;


    public function __construct(
        $sourceToken,
        $endpoint = self::URL,
        int $connectionTimeoutMs = self::DEFAULT_CONNECTION_TIMEOUT_MILLISECONDS,
        int $timeoutMs = self::DEFAULT_TIMEOUT_MILLISECONDS,
    ) {
        if (!\extension_loaded('curl')) {
            throw new \LogicException('The curl extension is needed to use the LogtailHandler');
        }

        $this->sourceToken = $sourceToken;
        $this->endpoint = $endpoint;
        $this->connectionTimeoutMs = $connectionTimeoutMs;
        $this->timeoutMs = $timeoutMs;
    }

    public function send($data): void
    {
        for ($attempt = 1; ; $attempt++) {
            if (!isset($this->handle)) {
                $this->initCurlHandle();
            }

            \curl_setopt($this->handle, CURLOPT_POSTFIELDS, $data);
            \curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, true);

            try {
                $this->execute();
                return;
            } catch (LogtailIngestException $exception) {
                // Better Stack answered, and it would answer the same request the same way.
                throw $exception;
            } catch (\RuntimeException $exception) {
                // Util::execute throws only on transport failures (not HTTP status), most often a
                // reused keep-alive connection the server closed. Reconnect on a fresh handle and retry.
                unset($this->handle);

                if ($attempt >= self::MAX_SEND_ATTEMPTS) {
                    throw $exception;
                }

                \usleep($attempt * self::RETRY_BACKOFF_MILLISECONDS * 1000);
            }
        }
    }

    /**
     * Drops the connection; the next send() opens a fresh one.
     */
    public function close(): void
    {
        unset($this->handle);
    }

    protected function execute(): void
    {
        $responseBody = \Monolog\Handler\Curl\Util::execute($this->handle, 1, false);
        $statusCode = \curl_getinfo($this->handle, CURLINFO_RESPONSE_CODE);

        if ($statusCode >= 200 && $statusCode < 300) {
            return;
        }

        $message = "{$this->endpoint} answered HTTP {$statusCode}";
        if ($statusCode === 401 || $statusCode === 403) {
            $message .= ": the source token or the ingesting host is wrong. Copy both from your source's settings in Better Stack.";
        } elseif ($responseBody !== '') {
            $message .= ': ' . \substr($responseBody, 0, self::RESPONSE_EXCERPT_BYTES);
            if (\strlen($responseBody) > self::RESPONSE_EXCERPT_BYTES) {
                $message .= '...';
            }
        }

        throw new LogtailIngestException($message, $statusCode);
    }

    private function initCurlHandle(): void
    {
        $this->handle = \curl_init();

        $headers = [
            'Content-Type: application/json',
            "Authorization: Bearer {$this->sourceToken}",
        ];

        \curl_setopt($this->handle, CURLOPT_URL, $this->endpoint);
        \curl_setopt($this->handle, CURLOPT_POST, true);
        \curl_setopt($this->handle, CURLOPT_HTTPHEADER, $headers);
        \curl_setopt($this->handle, CURLOPT_CONNECTTIMEOUT_MS, $this->connectionTimeoutMs);
        \curl_setopt($this->handle, CURLOPT_TIMEOUT_MS, $this->timeoutMs);

    }
}
