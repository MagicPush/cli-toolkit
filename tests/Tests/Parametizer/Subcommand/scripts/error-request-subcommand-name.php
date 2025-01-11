<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

$request = Parametizer::newConfig(throwOnException: true)
    ->newSubcommandSwitch('branch')
    ->newSubcommand(
        'branch-red',
        Parametizer::newConfig(throwOnException: true)
            ->newOption('--opt')
            ->default('opt-level-2-red'),
    )
    ->newSubcommand(
        'branch-blue',
        Parametizer::newConfig(throwOnException: true)
            ->newOption('--opt')
            ->default('opt-level-2-blue'),
    )

    ->run();

echo $request
        ->getCommandRequest('branch-green') // Let's try requesting an unknown subcommand here.
        ->getParam('opt');
