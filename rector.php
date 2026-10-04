<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/bin'])
    ->withPreparedSets(deadCode: true, codeQuality: true)
    ->withPhpSets(php84: true)
    ->withImportNames(importShortClasses: false, importDocBlockNames: false)
    ->withSkip([
        Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector::class,
    ]);
