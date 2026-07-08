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

    protected function execute(): void
    {
        \Monolog\Handler\Curl\Util::execute($this->handle, 1, false);
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
