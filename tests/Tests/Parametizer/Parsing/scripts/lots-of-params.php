<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

$request = Parametizer::newConfig(throwOnException: true)
    ->newOption('--opt-required')
    ->description('Required option: pick one from the list')
    ->required()
    ->allowedValuesDescribed([
        'black' => 'A pile of books',
        'pink'  => 'A heap of ponies',
        'white' => null, // No description,
        '5'     => 'Give me "five"!',
    ])

    ->newOption('--opt-default', '-o')
    ->description('Non-required option with a default value')
    ->default('opt_default_value')

    ->newArrayOption('--opt-list', '-l')
    ->description('List of values')
    ->allowedValues(range(100, 800, 50), true) // Values are hidden from help, but not from validation.

    ->newOption('--opt-no-default')

    ->newFlag('--flag1', '-f')
    ->description('Some flag')

    ->newFlag('--flag2', '-g')

    ->newFlag('--flag3')
    ->description('Flag without short name')

    ->newArgument('arg-required')
    ->description('Required argument')

    ->newArgument('arg-optional')
    ->description('Optional argument: pick one from the list')
    ->default('B')
    ->allowedValues(['A', 'B', 'C'])

    ->newArrayArgument('arg-list')
    ->required(false)

    ->run();

echo json_encode($request->getParams());
