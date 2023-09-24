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

    /**
     * @var string $sourceToken
     */
    private $sourceToken;

    /**
     * @var string $endpoint
     */
    private $endpoint;

    /**
     * @var \CurlHandle $handle
     */
    private $handle = NULL;

    public function __construct($sourceToken, $endpoint = self::URL)
    {
        if (!\extension_loaded('curl')) {
            throw new \LogicException('The curl extension is needed to use the LogtailHandler');
        }

        $this->sourceToken = $sourceToken;
        $this->endpoint = $endpoint;
    }

    public function send($data)
    {
        if (is_null($this->handle)) {
            $this->initCurlHandle();
        }

        \curl_setopt($this->handle, CURLOPT_POSTFIELDS, $data);
        \curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, true);

        \Monolog\Handler\Curl\Util::execute($this->handle, 5, false);
    }

    /**
     * @return void
     */
    private function initCurlHandle()
    {
        $this->handle = \curl_init();

        $headers = [
            'Content-Type: application/json',
            "Authorization: Bearer {$this->sourceToken}",
        ];

        \curl_setopt($this->handle, CURLOPT_URL, $this->endpoint);
        \curl_setopt($this->handle, CURLOPT_POST, true);
        \curl_setopt($this->handle, CURLOPT_HTTPHEADER, $headers);
    }
}
