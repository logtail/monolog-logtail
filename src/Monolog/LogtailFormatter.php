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

use Monolog\Formatter\JsonFormatter;
use Monolog\LogRecord;

/**
 * Format JSON records for Logtail
 */
class LogtailFormatter extends JsonFormatter {

    public function __construct() {
        parent::__construct(self::BATCH_MODE_JSON, false);
        $this->setMaxNormalizeItemCount(PHP_INT_MAX);
    }

    public function format(LogRecord $record): string {
        #return parent::format(self::formatRecord($record));
        $normalized = $this->normalize(self::formatRecord($record));

        return $this->toJson($normalized, true);
    }

    public function formatBatch(array $records): string
    {
        $normalized = array_values($this->normalize(array_map(self::formatRecord(...), $records)));
        return $this->toJson($normalized, true);
    }

    protected static function formatRecord(LogRecord $record): array
    {
        return [
            'dt' => $record->datetime,
            'message' => $record->message,
            'level' => $record->level,
            'monolog' => [
                'channel' => $record->channel,
                'context' => $record->context,
                'extra' => $record->extra,
            ],
        ];
    }
}
