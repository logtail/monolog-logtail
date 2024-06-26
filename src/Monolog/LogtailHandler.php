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

use Monolog\Handler\BufferHandler;
use Monolog\Logger;

/**
 * Sends buffered logs to Logtail.
 */
class LogtailHandler extends BufferHandler
{
    const DEFAULT_BUBBLE = true;
    const DEFAULT_BUFFER_LIMIT = 1000;
    const DEFAULT_FLUSH_ON_OVERFLOW = true;
    const DEFAULT_ALWAYS_FLUSH_AFTER_MILLISECONDS = 1000;

    /**
     * @var int|null $alwaysFlushAfterMs
     */
    private $alwaysFlushAfterMs;

    /**
     * @var int|float|null highResolutionTimeOfNextFlush
     */
    private $highResolutionTimeOfNextFlush;

    /**
     * @param string        $sourceToken            Logtail source token
     * @param int|string    $level                  The minimum logging level at which this handler will be triggered
     * @param bool          $bubble                 Whether the messages that are handled can bubble up the stack or not
     * @param string        $endpoint               Logtail ingesting endpoint
     * @param int           $bufferLimit            How many entries should be buffered at most, beyond that the oldest items are removed from the buffer.
     * @param bool          $flushOnOverflow        If true, the buffer is flushed when the max size has been reached, by default oldest entries are discarded
     * @param int           $connectionTimeoutMs    The maximum time in milliseconds that you allow the connection phase to the server to take
     * @param int           $timeoutMs              The maximum time in milliseconds that you allow a transfer operation to take
     * @param int|null      alwaysFlushAfterMs      The time in milliseconds after which next log record will trigger flushing all logs. Null to disable.
     */
    public function __construct(
        $sourceToken,
        $level = Logger::DEBUG,
        $bubble = self::DEFAULT_BUBBLE,
        $endpoint = LogtailClient::URL,
        $bufferLimit = self::DEFAULT_BUFFER_LIMIT,
        $flushOnOverflow = self::DEFAULT_FLUSH_ON_OVERFLOW,
        $connectionTimeoutMs = LogtailClient::DEFAULT_CONNECTION_TIMEOUT_MILLISECONDS,
        $timeoutMs = LogtailClient::DEFAULT_TIMEOUT_MILLISECONDS,
        $alwaysFlushAfterMs = self::DEFAULT_ALWAYS_FLUSH_AFTER_MILLISECONDS
    ) {
        parent::__construct(new SynchronousLogtailHandler($sourceToken, $level, $bubble, $endpoint, $connectionTimeoutMs, $timeoutMs), $bufferLimit, $level, $bubble, $flushOnOverflow);
        $this->alwaysFlushAfterMs = $alwaysFlushAfterMs;
        $this->setHighResolutionTimeOfLastFlush();
    }

    /**
     * @inheritDoc
     */
    public function handle(array $record): bool
    {
        $return = parent::handle($record);

        if ($this->highResolutionTimeOfNextFlush !== null && $this->highResolutionTimeOfNextFlush <= hrtime(true)) {
            $this->flush();
            $this->setHighResolutionTimeOfLastFlush();
        }

        return $return;
    }

    /**
     * @inheritDoc
     */
    public function flush(): void
    {
        parent::flush();
        $this->setHighResolutionTimeOfLastFlush();
    }

    private function setHighResolutionTimeOfLastFlush(): void
    {
        $currentHighResolutionTime = hrtime(true);
        if ($this->alwaysFlushAfterMs === null || $currentHighResolutionTime === false) {
            $this->highResolutionTimeOfNextFlush = null;

            return;
        }

        // hrtime(true) returns nanoseconds, converting alwaysFlushAfterMs from milliseconds to nanoseconds
        $this->highResolutionTimeOfNextFlush = $currentHighResolutionTime + $this->alwaysFlushAfterMs * 1e+6;
    }
}
