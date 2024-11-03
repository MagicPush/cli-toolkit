<?php declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

Parametizer::newConfig()
    ->newSubcommandSwitch('switchme')
    ->newSubcommand('test1', Parametizer::newConfig())
    ->newSubcommand('test2', Parametizer::newConfig())

    ->newOption('--name')

    ->newSubcommand('test1', Parametizer::newConfig()) // Duplicated value (subcommand name).

    ->run();
