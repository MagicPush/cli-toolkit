<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

Parametizer::newConfig(throwOnException: true)
    ->newSubcommandSwitch('switchme')
    ->newSubcommand('test1', Parametizer::newConfig(throwOnException: true))
    ->newSubcommand('test2', Parametizer::newConfig(throwOnException: true))

    ->newOption('--name')

    ->newSubcommand('test1', Parametizer::newConfig(throwOnException: true)) // Duplicated value (subcommand name).

    ->run();
