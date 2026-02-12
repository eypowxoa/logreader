<?php

declare(strict_types=1);

use LogParser\LogReaderConfig;
use LogParser\LogReaderConfigFile;
use LogParser\Record;

return new LogReaderConfig(
    date: '2026-02-11 12:49:07',
    timezone: 'UTC',
    login: 'example',
    password: 'elpmaxe',
    fileList: [
        new LogReaderConfigFile(
            filePath: 'example.log',
            datePattern: '~\[\S+ (?<month>\S+) (?<day>\d{2}) (?<hour>\d{2}):(?<minute>\d{2}):(?<second>\d{2}) (?<year>\d{4})]~',
            filterFunction: static fn(Record $record): bool => 1 !== preg_match('~Accepted|Closing~', $record->record),
        ),
    ],
);
