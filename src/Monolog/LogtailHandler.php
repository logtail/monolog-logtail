<?php declare(strict_types=1);

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
 * Sends log to Logtail.
 */
class LogtailHandler extends \Monolog\Handler\AbstractProcessingHandler {
    const URL = "https://in.logtail.com";

    /**
     * @var string $endpoint
     */
    private $endpoint;

    /**
     * @var string $sourceToken
     */
    private $sourceToken;

    /**
     * @var resource $handle
     */
    private $handle = NULL;

    /**
     * @param string $sourceToken
     * @param string $hostname
     * @param int $level
     * @param bool $bubble
     */
    public function __construct($sourceToken, $level = \Monolog\Logger::DEBUG, $bubble = true, $endpoint = self::URL) {
        parent::__construct($level, $bubble);

        if (!\extension_loaded('curl')) {
            throw new \LogicException('The curl extension is needed to use the LogtailHandler');
        }

        $this->sourceToken = $sourceToken;
        $this->endpoint = $endpoint;
    }

    /**
     * @param array $record
     */
    protected function write(array $record): void {
        if (is_null($this->handle)) {
            $this->initCurlHandle();
        }

        \curl_setopt($this->handle, CURLOPT_POSTFIELDS, $record["formatted"]);
        \curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, true);

        \Monolog\Handler\Curl\Util::execute($this->handle, 5, false);
    }

    /**
     * @return \Logtail\Monolog\LogtailFormatter
     */
    protected function getDefaultFormatter(): \Monolog\Formatter\FormatterInterface {
        return new \Logtail\Monolog\LogtailFormatter();
    }

    /**
     * @return void
     */
    private function initCurlHandle() {
        $this->handle = \curl_init();

        $headers = [
            'Content-Type: application/json',
            "Authorization: Bearer {$this->sourceToken}"
        ];

        \curl_setopt($this->handle, CURLOPT_URL, $this->endpoint);
        \curl_setopt($this->handle, CURLOPT_POST, true);
        \curl_setopt($this->handle, CURLOPT_HTTPHEADER, $headers);
    }
}
