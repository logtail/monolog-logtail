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

use Monolog\Formatter\FormatterInterface;

/**
 * Sends log to Logtail.
 */
class SynchronousLogtailHandler extends \Monolog\Handler\AbstractProcessingHandler {
    /**
     * @var LogtailClient $client
     */
    private $client;

    /**
     * @param string $sourceToken
     * @param int $level
     * @param bool $bubble
     * @param string $endpoint
     * @param int $connectionTimeout
     * @param int $timeout
     */
    public function __construct(
        $sourceToken,
        $level = \Monolog\Logger::DEBUG,
        $bubble = true,
        $endpoint = LogtailClient::URL,
        $connectionTimeout = LogtailClient::DEFAULT_CONNECTION_TIMEOUT_SECONDS,
        $timeout = LogtailClient::DEFAULT_TIMEOUT_SECONDS
    ) {
        parent::__construct($level, $bubble);

        $this->client = new LogtailClient($sourceToken, $endpoint, $connectionTimeout, $timeout);

        $this->pushProcessor(new \Monolog\Processor\IntrospectionProcessor($level, ['Logtail\\']));
        $this->pushProcessor(new \Monolog\Processor\WebProcessor);
        $this->pushProcessor(new \Monolog\Processor\ProcessIdProcessor);
        $this->pushProcessor(new \Monolog\Processor\HostnameProcessor);
    }

    /**
     * @param array $record
     */
    protected function write(array $record): void {
        $this->client->send($record["formatted"]);
    }

    /**
     * @param array $records
     * @return void
     */
    public function handleBatch(array $records): void
    {
        $formattedRecords = $this->getFormatter()->formatBatch($records);
        $this->client->send($formattedRecords);
    }

    /**
     * @return \Logtail\Monolog\LogtailFormatter
     */
    protected function getDefaultFormatter(): \Monolog\Formatter\FormatterInterface {
        return new \Logtail\Monolog\LogtailFormatter();
    }

    public function getFormatter(): FormatterInterface
    {
        return $this->getDefaultFormatter();
    }
}
