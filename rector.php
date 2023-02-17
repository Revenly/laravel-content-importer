<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/src',
        __DIR__.'/tests',
    ]);

    $rectorConfig->skip([
        \Rector\Php81\Rector\Array_\FirstClassCallableRector::class,
        \Rector\Php81\Rector\Class_\MyCLabsClassToEnumRector::class,
        \Rector\Php81\Rector\Class_\SpatieEnumClassToEnumRector::class,
        \Rector\Php81\Rector\ClassConst\FinalizePublicClassConstantRector::class,
    ]);

    // register a single rule
    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);

    // define sets of rules
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_82,
    ]);
};
