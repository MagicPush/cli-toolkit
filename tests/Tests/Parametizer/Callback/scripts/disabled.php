<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

Parametizer::newConfig(throwOnException: true)
    ->newArgument('arg')
    ->callback(function () {
        throw new Exception('Not reachable exception');
    })
    ->callback(null) // NULL disables callback.

    ->run();
