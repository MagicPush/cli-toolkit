<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../init-console.php';

TestUtils::newConfig()
    ->newArgument('arg')
    ->callback(function () {
        throw new Exception('Not reachable exception');
    })
    ->callback(null) // NULL disables callback.

    ->run();
