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
    /**
     * @param string           $sourceToken     Logtail source token
     * @param int|string       $level           The minimum logging level at which this handler will be triggered
     * @param bool             $bubble          Whether the messages that are handled can bubble up the stack or not
     * @param                  $endpoint        Logtail ingesting endpoint
     * @param int              $bufferLimit     How many entries should be buffered at most, beyond that the oldest items are removed from the buffer.
     * @param bool             $flushOnOverflow If true, the buffer is flushed when the max size has been reached, by default oldest entries are discarded
     */
    public function __construct($sourceToken, $level = Logger::DEBUG, $bubble = true, $endpoint = LogtailClient::URL, $bufferLimit = 0, $flushOnOverflow = false)
    {
        parent::__construct(new SynchronousLogtailHandler($sourceToken, $level, $bubble, $endpoint), $bufferLimit, $level, $bubble, $flushOnOverflow);
    }

}
