<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

Parametizer::newConfig(throwOnException: true)
    ->newSubcommandSwitch('switchme')
    ->newSubcommand('test1', Parametizer::newConfig(throwOnException: true))
    // ... and no second subcommand here.
    ->run();
