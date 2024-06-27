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

use Monolog\Level;

final class LogtailHandlerBuilder
{
    private string $sourceToken;
    private Level $level = Level::Debug;
    private bool $bubble = LogtailHandler::DEFAULT_BUBBLE;
    private string $endpoint = LogtailClient::URL;
    private int $bufferLimit = LogtailHandler::DEFAULT_BUFFER_LIMIT;
    private bool $flushOnOverflow = LogtailHandler::DEFAULT_FLUSH_ON_OVERFLOW;
    private int $connectionTimeoutMs = LogtailClient::DEFAULT_CONNECTION_TIMEOUT_MILLISECONDS;
    private int $timeoutMs = LogtailClient::DEFAULT_TIMEOUT_MILLISECONDS;
    private ?int $flushIntervalMs = LogtailHandler::DEFAULT_FLUSH_INTERVAL_MILLISECONDS;
    private bool $throwExceptions = SynchronousLogtailHandler::DEFAULT_THROW_EXCEPTION;

    /**
     * @internal use {@see self::withSourceToken()} instead
     */
    private function __construct($sourceToken)
    {
        $this->sourceToken = $sourceToken;
    }

    /**
     * Builder for comfortable creation of {@see LogtailHandler}.
     *
     * @var    string $sourceToken Your Better Stack source token.
     * @see    https://logs.betterstack.com/team/0/sources
     * @return self   Always returns new immutable instance
     */
    public static function withSourceToken(string $sourceToken): self
    {
        return new self($sourceToken);
    }

    /**
     * Sets the minimum logging level at which this handler will be triggered.
     *
     * @param  Level $level
     * @return self  Always returns new immutable instance
     */
    public function withLevel(Level $level): self
    {
        $clone = clone $this;
        $clone->level = $level;
        
        return $clone;
    }

    /**
     * Sets whether the messages that are handled can bubble up the stack or not.
     *
     * @param  bool $bubble
     * @return self Always returns new immutable instance
     */
    public function withLogBubbling(bool $bubble): self
    {
        $clone = clone $this;
        $clone->bubble = $bubble;
        
        return $clone;
    }

    /**
     * Sets how many entries should be buffered at most, beyond that the oldest items are flushed or removed from the buffer.
     *
     * @param  int  $bufferLimit
     * @return self Always returns new immutable instance
     */
    public function withBufferLimit(int $bufferLimit): self
    {
        $clone = clone $this;
        $clone->bufferLimit = $bufferLimit;
        
        return $clone;
    }

    /**
     * Sets whether the buffer is flushed (true) or discarded (false) when the max size has been reached.
     *
     * @param  bool $flushOnOverflow
     * @return self Always returns new immutable instance
     */
    public function withFlushOnOverflow(bool $flushOnOverflow): self
    {
        $clone = clone $this;
        $clone->flushOnOverflow = $flushOnOverflow;
        
        return $clone;
    }

    /**
     * Sets the maximum time in milliseconds that you allow the connection phase to the server to take.
     *
     * @param  int  $connectionTimeoutMs
     * @return self Always returns new immutable instance
     */
    public function withConnectionTimeoutMilliseconds(int $connectionTimeoutMs): self
    {
        $clone = clone $this;
        $clone->connectionTimeoutMs = $connectionTimeoutMs;
        
        return $clone;
    }

    /**
     * Sets the maximum time in milliseconds that you allow a transfer operation to take.
     *
     * @param  int  $timeoutMs
     * @return self Always returns new immutable instance
     */
    public function withTimeoutMilliseconds(int $timeoutMs): self
    {
        $clone = clone $this;
        $clone->timeoutMs = $timeoutMs;
        
        return $clone;
    }

    /**
     * Set the time in milliseconds after which next log record will trigger flushing all logs. Null to disable.
     *
     * @param  int|null $flushIntervalMs
     * @return self     Always returns new immutable instance
     */
    public function withFlushIntervalMilliseconds(?int $flushIntervalMs): self
    {
        $clone = clone $this;
        $clone->flushIntervalMs = $flushIntervalMs;
        
        return $clone;
    }

    /**
     * Sets whether to throw exceptions when sending logs fails.
     *
     * @param  bool $throwExceptions
     * @return self Always returns new immutable instance
     */
    public function withExceptionThrowing(bool $throwExceptions): self
    {
        $clone = clone $this;
        $clone->throwExceptions = $throwExceptions;

        return $clone;
    }

    /**
     * Builds the {@see LogtailHandler} instance based on the setting.
     *
     * @return LogtailHandler
     */
    public function build(): LogtailHandler
    {
        return new LogtailHandler(
            $this->sourceToken,
            $this->level,
            $this->bubble,
            $this->endpoint,
            $this->bufferLimit,
            $this->flushOnOverflow,
            $this->connectionTimeoutMs,
            $this->timeoutMs,
            $this->flushIntervalMs
        );
    }
}
