<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

return new Config()
    ->setCacheFile(dirname(__DIR__) . \DIRECTORY_SEPARATOR . '.cache' . \DIRECTORY_SEPARATOR . 'php-cs-fixer.cache')
    ->setParallelConfig(ParallelConfigFactory::detect()) // @TODO 4.0 no need to call this manually
    ->setRiskyAllowed(true)
    ->setRules([
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        '@auto' => true,
        'echo_tag_syntax' => ['format' => 'short'],
        'ordered_class_elements' => ['sort_algorithm' => 'alpha'],
        'php_unit_test_case_static_method_calls' => ['call_type' => 'this', 'target' => 'newest'],
        'php_unit_test_class_requires_covers' => false,
    ])
    // 💡 by default, Fixer looks for `*.php` files excluding `./vendor/` - here, you can groom this config
    ->setFinder(
        new Finder()
            // 💡 root folder to check
            ->in(dirname(__DIR__))
            // 💡 additional files, eg bin entry file
            // ->append([__DIR__.'/bin-entry-file'])
            // 💡 folders to exclude, if any
            // ->exclude([/* ... */])
            // 💡 path patterns to exclude, if any
            // ->notPath([/* ... */])
            // 💡 extra configs
            ->ignoreDotFiles(true)
        // ->ignoreVCS(true) // true by default
    )
;
