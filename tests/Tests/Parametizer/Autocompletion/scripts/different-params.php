<?php declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

Parametizer::newConfig()
    ->newOption('--opt', '-o')
    ->allowedValuesDescribed([
        '100'     => 'One hundred',
        '200'     => 'Two hundred',
        '1000000' => 'One million',
    ])

    ->newOption('--any-value', '-a')

    ->newFlag('--flag', '-f')
    ->newFlag('--second-flag', '-s')

    ->newArgument('arg')
    ->allowedValues(['super', 'prefix', 'premium'])

    ->newArgument('arg-allowed-values')
    ->allowedValues(['aaa', 'bbb', 'ccc'])

    ->newArgument('arg-allowed-values-empty')
    ->allowedValues(['aaa', 'bbb', 'ccc'])
    ->allowedValues([]) // Overwrite allowed values and ensure that previous completion list is emptied.

    ->run();
