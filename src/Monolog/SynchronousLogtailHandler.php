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
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Processor\HostnameProcessor;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\ProcessIdProcessor;
use Monolog\Processor\WebProcessor;

/**
 * Sends log to Logtail.
 */
class SynchronousLogtailHandler extends AbstractProcessingHandler {
    /**
     * @var LogtailClient $client
     */
    private $client;

    /**
     * @param string $sourceToken
     * @param int $level
     * @param bool $bubble
     * @param string $endpoint
     * @param int $connectionTimeoutMs
     * @param int $timeoutMs
     */
    public function __construct(
        $sourceToken,
        $level = Level::Debug,
        bool $bubble = true,
        string $endpoint = LogtailClient::URL,
        int $connectionTimeoutMs = LogtailClient::DEFAULT_CONNECTION_TIMEOUT_MILLISECONDS,
        int $timeoutMs = LogtailClient::DEFAULT_TIMEOUT_MILLISECONDS,
    ) {
        parent::__construct($level, $bubble);

        $this->client = new LogtailClient($sourceToken, $endpoint, $connectionTimeoutMs, $timeoutMs);

        $this->pushProcessor(new IntrospectionProcessor($level, ['Logtail\\']));
        $this->pushProcessor(new WebProcessor);
        $this->pushProcessor(new ProcessIdProcessor);
        $this->pushProcessor(new HostnameProcessor);
    }

    /**
     * @param LogRecord $record
     */
    protected function write(LogRecord $record): void {
        $this->client->send($record->formatted);
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
     * @return LogtailFormatter
     */
    protected function getDefaultFormatter(): FormatterInterface
    {
        return new LogtailFormatter();
    }

    public function getFormatter(): FormatterInterface
    {
        return $this->getDefaultFormatter();
    }
}
