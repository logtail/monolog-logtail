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
class SynchronousLogtailHandler extends \Monolog\Handler\AbstractProcessingHandler
{
    const DEFAULT_THROW_EXCEPTION = false;

    /**
     * @var LogtailClient $client
     */
    private $client;

    /**
     * @var bool $throwExceptions
     */
    private $throwExceptions;

    /**
     * @param string $sourceToken
     * @param int $level
     * @param bool $bubble
     * @param string $endpoint
     * @param int $connectionTimeoutMs
     * @param int $timeoutMs
     * @param bool throwExceptions
     */
    public function __construct(
        $sourceToken,
        $level = \Monolog\Logger::DEBUG,
        $bubble = LogtailHandler::DEFAULT_BUBBLE,
        $endpoint = LogtailClient::URL,
        $connectionTimeoutMs = LogtailClient::DEFAULT_CONNECTION_TIMEOUT_MILLISECONDS,
        $timeoutMs = LogtailClient::DEFAULT_TIMEOUT_MILLISECONDS,
        $throwExceptions = self::DEFAULT_THROW_EXCEPTION
    ) {
        parent::__construct($level, $bubble);

        $this->client = new LogtailClient($sourceToken, $endpoint, $connectionTimeoutMs, $timeoutMs);
        $this->throwExceptions = $throwExceptions;

        $this->pushProcessor(new \Monolog\Processor\IntrospectionProcessor($level, ['Logtail\\']));
        $this->pushProcessor(new \Monolog\Processor\WebProcessor);
        $this->pushProcessor(new \Monolog\Processor\ProcessIdProcessor);
        $this->pushProcessor(new \Monolog\Processor\HostnameProcessor);
    }

    /**
     * @param array $record
     */
    protected function write(array $record): void {
        try {
            $this->client->send($record["formatted"]);
        } catch (Throwable $throwable) {
            if ($this->throwExceptions) {
                throw $throwable;
            } else {
                 trigger_error("Failed to send a single log record to Better Stack because of " . $throwable, E_USER_WARNING);
             }
        }
    }

    /**
     * @param array $records
     * @return void
     */
    public function handleBatch(array $records): void
    {
        $formattedRecords = $this->getFormatter()->formatBatch($records);
        try {
            $this->client->send($formattedRecords);
        } catch (\Throwable $throwable) {
            if ($this->throwExceptions) {
                throw $throwable;
            } else {
                 trigger_error("Failed to send " . count($records) . " log records to Better Stack because of " . $throwable, E_USER_WARNING);
            }
        }
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
