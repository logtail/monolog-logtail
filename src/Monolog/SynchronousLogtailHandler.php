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
class SynchronousLogtailHandler extends AbstractProcessingHandler
{
    const DEFAULT_THROW_EXCEPTION = false;

    private LogtailClient $client;
    private bool $throwExceptions;

    /**
     * @param string $sourceToken
     * @param int|string|Level $level
     * @param bool $bubble
     * @param string $endpoint
     * @param int $connectionTimeoutMs
     * @param int $timeoutMs
     * @param bool throwExceptions
     * @param bool $withDefaultProcessors Whether to push the {@see self::defaultProcessors()}
     */
    public function __construct(
        string $sourceToken,
        int|string|Level $level = Level::Debug,
        bool $bubble = LogtailHandler::DEFAULT_BUBBLE,
        string $endpoint = LogtailClient::URL,
        int $connectionTimeoutMs = LogtailClient::DEFAULT_CONNECTION_TIMEOUT_MILLISECONDS,
        int $timeoutMs = LogtailClient::DEFAULT_TIMEOUT_MILLISECONDS,
        bool $throwExceptions = self::DEFAULT_THROW_EXCEPTION,
        bool $withDefaultProcessors = true
    ) {
        parent::__construct($level, $bubble);

        $this->client = new LogtailClient($sourceToken, $endpoint, $connectionTimeoutMs, $timeoutMs);
        $this->throwExceptions = $throwExceptions;

        if ($withDefaultProcessors) {
            foreach (self::defaultProcessors($level) as $processor) {
                $this->pushProcessor($processor);
            }
        }
    }

    /**
     * Processors that add the file and line of the log call, the web request, the process ID and the hostname to
     * every record's extra.
     *
     * @param int|string|Level $level The minimum logging level at which the file and line are added
     * @return callable[]
     */
    public static function defaultProcessors(int|string|Level $level): array
    {
        return [
            new IntrospectionProcessor($level, ['Logtail\\']),
            new WebProcessor,
            new ProcessIdProcessor,
            new HostnameProcessor,
        ];
    }

    /**
     * @param LogRecord $record
     */
    protected function write(LogRecord $record): void {
        try {
            $this->client->send($record->formatted);
        } catch (\Throwable $throwable) {
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
        foreach ($records as $index => $record) {
            $records[$index] = $this->processRecord($record);
        }
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
     * Long-running workers (Laravel Octane calls Logger::reset() before every request) must not
     * carry the connection across requests: one that idled through the previous boundary may
     * already be dead on the other side, and a write into it stalls until the timeout.
     */
    public function reset(): void
    {
        parent::reset();
        $this->client->close();
    }

    public function close(): void
    {
        $this->client->close();
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
