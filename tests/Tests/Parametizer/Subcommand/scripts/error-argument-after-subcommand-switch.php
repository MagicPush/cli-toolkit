<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

Parametizer::newConfig(throwOnException: true)
    ->newSubcommandSwitch('switchme')
    ->newSubcommand('test1', Parametizer::newConfig(throwOnException: true))
    ->newSubcommand('test2', Parametizer::newConfig(throwOnException: true))

    ->newOption('--allowed')   // Options can be defined after subcommand switch,

    ->newArgument('forbidden') // ... but arguments can not.

    ->run();
