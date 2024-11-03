<?php declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

Parametizer::newConfig()
    ->newSubcommandSwitch('switchme')
    ->newSubcommand(
        'test11',
        Parametizer::newConfig()
            ->newArgument('required-arg-l2')
            ->description('Subcommand required argument')

            ->newOption('--required-l2')
            ->description('Subcommand required option')
            ->required(),
    )
    ->newSubcommand('test12', Parametizer::newConfig())

    ->newOption('--required')
    ->description('Required option')
    ->required()

    ->run();
