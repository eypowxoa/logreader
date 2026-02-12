<?php

declare(strict_types=1);

use LogReader\CheckedException;
use LogReader\FileReaderRealFactory;
use LogReader\LogReaderConfig;
use LogReader\MultilogPeriod;
use LogReader\MultilogReader;
use LogReader\Record;

require __DIR__ . \DIRECTORY_SEPARATOR . 'vendor' . \DIRECTORY_SEPARATOR . 'autoload.php';

try {
    $configName = (pathinfo(__FILE__, \PATHINFO_FILENAME) . '.config.php');
    $config = @require __DIR__ . \DIRECTORY_SEPARATOR . $configName;
} catch (Throwable $throwable) {
    http_response_code(500);

    exit(sprintf("Failed to read %s. %s\n", $configName, $throwable->getMessage()));
}

if (!$config instanceof LogReaderConfig) {
    http_response_code(500);

    exit(sprintf("Not a %s in %s\n", LogReaderConfig::class, $configName));
}

if (($_SERVER['HTTP_AUTHORIZATION'] ?? '') !== $config->httpAuth) {
    http_response_code(401);

    header('WWW-Authenticate: Basic realm="Log Reader", charset="UTF-8"');

    exit('Not authorized');
}

/** @var iterable<Record> $recordList */
$recordList = [];

if (array_key_exists('p', $_GET)) {
    try {
        $period = match ($_GET['p'] ?? null) {
            'minute' => MultilogPeriod::MINUTE,
            'hour' => MultilogPeriod::HOUR,
            'day' => MultilogPeriod::DAY,
            'week' => MultilogPeriod::WEEK,
            'month' => MultilogPeriod::MONTH,
            default => throw new RuntimeException('Wrong period'),
        };
    } catch (Throwable) {
        http_response_code(400);

        exit("Wrong period\n");
    }

    try {
        $fileReaderRealFactory = new FileReaderRealFactory();
        $multilogReader = new MultilogReader($fileReaderRealFactory);
        foreach ($multilogReader->readConfigured($config, $period) as $record) {
            $recordList[] = $record;
        }
    } catch (CheckedException $checkedException) {
        http_response_code(500);

        exit($checkedException->getMessage());
    }
}
?>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Log Reader</title>
        <style>
            .records {
                font-family: sans-serif;
                list-style: none;
                padding: 10px 0;
                margin: 0;
            }
            .record {
                padding: 5px 0;
            }
            .date {
                color: #552222;
                font-weight: bold;
            }
            .message {
                color: #222222;
            }
        </style>
    </head>
    <body>
        <nav class="period">
            <a href="?p=minute">Minute</a>
            <a href="?p=hour">Hour</a>
            <a href="?p=day">Day</a>
            <a href="?p=week">Week</a>
            <a href="?p=month">Month</a>
        </nav>
        <main>
            <ul class="records">
                <?php foreach ($recordList as $record) { ?>
                    <li class="record">
                        <span class="date"><?= htmlspecialchars($record->date->format('Y-m-d H:i:s')); ?></span>
                        <span class="message"><?=  htmlspecialchars($record->record); ?></span>
                    </li>
                <?php } ?>
            </ul>
        </main>
    </body>
</html>
