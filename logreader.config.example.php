<?php

declare(strict_types=1);

use LogParser\LogReaderConfig;
use LogParser\LogReaderConfigFile;
use LogParser\Record;

return new LogReaderConfig(
    '2026-02-11 12:49:07',
    'UTC',
    [
        new LogReaderConfigFile(
            'example.log',
            '~\[\S+ (?<month>\S+) (?<day>\d{2}) (?<hour>\d{2}):(?<minute>\d{2}):(?<second>\d{2}) (?<year>\d{4})]~',
            static function (Record $record) {
                return 1 !== preg_match('~Accepted|Closing~', $record->record);
            }
        )
    ]
);
