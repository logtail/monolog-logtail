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
class LogtailFormatter extends \Monolog\Formatter\JsonFormatter {

    public function __construct($batchMode = self::BATCH_MODE_NEWLINES, $appendNewline = false) {
        parent::__construct($batchMode, $appendNewline);
    }

    public function format(array $record): string {
        return parent::format([
            'dt' => $record['datetime'],
            'message' => $record['message'],
            'level' => $record['level_name'],
            'monolog' => [
                'channel' => $record['channel'],
                'context' => $record['context'],
                'extra' => $record['extra'],
            ],
        ]);
    }
}
