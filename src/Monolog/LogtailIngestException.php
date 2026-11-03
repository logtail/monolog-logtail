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
 * Better Stack answered with a non-2xx HTTP status, which is the exception code, so the logs were dropped.
 */
class LogtailIngestException extends \RuntimeException
{
}
