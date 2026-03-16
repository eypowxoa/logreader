<?php

declare(strict_types=1);

use LogReader\CheckedException;
use LogReader\FileReaderRealFactory;
use LogReader\FilterVariant;
use LogReader\LogReaderConfig;
use LogReader\MultilogPeriod;
use LogReader\MultilogReader;
use LogReader\Record;

require __DIR__ . \DIRECTORY_SEPARATOR . 'vendor' . \DIRECTORY_SEPARATOR . 'autoload.php';

try {
    $configName = (pathinfo(__FILE__, \PATHINFO_FILENAME) . '.config.php');
    $configPath = (__DIR__ . \DIRECTORY_SEPARATOR . $configName);
    if (!file_exists($configPath)) {
        $exampleName = (pathinfo(__FILE__, \PATHINFO_FILENAME) . '.config.example.php');
        $examplePath = (__DIR__ . \DIRECTORY_SEPARATOR . $exampleName);
        if (!file_exists($examplePath)) {
            throw new RuntimeException(sprintf('No config file %s', $configName));
        }
        $configName = $exampleName;
        $configPath = $examplePath;
    }
    $config = @require $configPath;
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

$period = null;

try {
    $variant = match ($_GET['v'] ?? '') {
        '' => FilterVariant::VARIANT_1,
        '1' => FilterVariant::VARIANT_1,
        '2' => FilterVariant::VARIANT_2,
        '3' => FilterVariant::VARIANT_3,
        '4' => FilterVariant::VARIANT_4,
        '5' => FilterVariant::VARIANT_5,
        '6' => FilterVariant::VARIANT_6,
        '7' => FilterVariant::VARIANT_7,
        '8' => FilterVariant::VARIANT_8,
        '9' => FilterVariant::VARIANT_9,
    };
} catch (Throwable) {
    http_response_code(400);

    exit("Wrong variant\n");
}

if (array_key_exists('p', $_GET)) {
    try {
        $period = match ($_GET['p']) {
            'minute' => MultilogPeriod::MINUTE,
            'hour' => MultilogPeriod::HOUR,
            'day' => MultilogPeriod::DAY,
            'week' => MultilogPeriod::WEEK,
            'month' => MultilogPeriod::MONTH,
        };
    } catch (Throwable) {
        http_response_code(400);

        exit("Wrong period\n");
    }

    try {
        $fileReaderRealFactory = new FileReaderRealFactory();
        $multilogReader = new MultilogReader($fileReaderRealFactory);
        foreach ($multilogReader->readConfigured(
            $config,
            $period,
            $variant,
        ) as $record) {
            $recordList[] = $record;
        }
    } catch (CheckedException $checkedException) {
        http_response_code(500);

        exit($checkedException->getMessage());
    }
}

$url = static function (?MultilogPeriod $multilogPeriod, ?FilterVariant $filterVariant): string {
    $data = [];

    if ($multilogPeriod instanceof MultilogPeriod) {
        $data['p'] = match ($multilogPeriod) {
            MultilogPeriod::DAY => 'day',
            MultilogPeriod::HOUR => 'hour',
            MultilogPeriod::MINUTE => 'minute',
            MultilogPeriod::MONTH => 'month',
            MultilogPeriod::WEEK => 'week',
        };
    }

    if ($filterVariant instanceof FilterVariant) {
        $data['v'] = match ($filterVariant) {
            FilterVariant::VARIANT_1 => '1',
            FilterVariant::VARIANT_2 => '2',
            FilterVariant::VARIANT_3 => '3',
            FilterVariant::VARIANT_4 => '4',
            FilterVariant::VARIANT_5 => '5',
            FilterVariant::VARIANT_6 => '6',
            FilterVariant::VARIANT_7 => '7',
            FilterVariant::VARIANT_8 => '8',
            FilterVariant::VARIANT_9 => '9',
        };
    }

    if ([] === $data) {
        return '';
    }

    return '?' . http_build_query($data);
};

$renderPeriod = static function (MultilogPeriod $multilogPeriod, string $label) use ($period, $url, $variant): void {
    if ($multilogPeriod === $period) {
        ?>
        <span class="selected"><?= htmlspecialchars($label); ?></span>
        <?php
    } else {
        ?>
            <a href="<?= htmlspecialchars($url($multilogPeriod, $variant)); ?>">
                <?= htmlspecialchars($label); ?>
            </a>
        <?php
    }
};

$renderVariant = static function (FilterVariant $filterVariant, string $label) use ($period, $url, $variant): void {
    if ($filterVariant === $variant) {
        ?>
        <span class="selected"><?= htmlspecialchars($label); ?></span>
        <?php
    } else {
        ?>
            <a href="<?= htmlspecialchars($url($period, $filterVariant)); ?>">
                <?= htmlspecialchars($label); ?>
            </a>
        <?php
    }
};
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
            .source {
                color: #227799;
                font-weight: bold;
            }
            .date {
                color: #552222;
                font-weight: bold;
            }
            .message {
                color: #222222;
            }
            .selected {
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <nav class="period">
            <?php $renderPeriod(MultilogPeriod::MINUTE, 'Minute'); ?>
            <?php $renderPeriod(MultilogPeriod::HOUR, 'Hour'); ?>
            <?php $renderPeriod(MultilogPeriod::DAY, 'Day'); ?>
            <?php $renderPeriod(MultilogPeriod::WEEK, 'Week'); ?>
            <?php $renderPeriod(MultilogPeriod::MONTH, 'Month'); ?>
        </nav>
        <nav class="variant">
            <?php $renderVariant(FilterVariant::VARIANT_1, '1'); ?>
            <?php $renderVariant(FilterVariant::VARIANT_2, '2'); ?>
            <?php $renderVariant(FilterVariant::VARIANT_3, '3'); ?>
            <?php $renderVariant(FilterVariant::VARIANT_4, '4'); ?>
            <?php $renderVariant(FilterVariant::VARIANT_5, '5'); ?>
            <?php $renderVariant(FilterVariant::VARIANT_6, '6'); ?>
            <?php $renderVariant(FilterVariant::VARIANT_7, '7'); ?>
            <?php $renderVariant(FilterVariant::VARIANT_8, '8'); ?>
            <?php $renderVariant(FilterVariant::VARIANT_9, '9'); ?>
        <main>
            <ul class="records">
                <?php foreach ($recordList as $record) { ?>
                    <li class="record">
                        <span class="source"><?= htmlspecialchars($record->source); ?></span>
                        <span class="date"><?= htmlspecialchars($record->date->format('Y-m-d H:i:s')); ?></span>
                        <span class="message"><?=  htmlspecialchars($record->record); ?></span>
                    </li>
                <?php } ?>
            </ul>
        </main>
    </body>
</html>
