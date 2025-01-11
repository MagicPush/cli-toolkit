<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

Parametizer::newConfig(throwOnException: true)
    ->newArgument('name')
    ->allowedValues(['1', '2', '3'])
    ->completionList(['1', '2', '3'])
    ->run();
