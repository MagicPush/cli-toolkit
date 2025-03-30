<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../init-console.php';

TestUtils::newConfig()
    ->newArgument('arg')
    ->callback(function ($value) {
        echo "<arg>: '{$value}'" . PHP_EOL;
    })
    ->validatorCallback('ctype_digit', 'Only digits are allowed for <arg>')

    ->newOption('--opt')
    ->callback(function ($value) {
        echo "--opt: '{$value}'" . PHP_EOL;
    })
    ->validatorCallback('ctype_digit', 'Only digits are allowed for --opt')

    ->run();
