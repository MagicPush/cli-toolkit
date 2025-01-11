<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

// Suppressing warnings about passing values instead of references for the sake of testing:
error_reporting(E_ERROR);

$request = Parametizer::newConfig(throwOnException: true)
    ->newArgument('arg')
    ->callback(function (&$value) {
        echo "The parsed value is: '{$value}'";

        $value = ' ADDED SUBSTRING'; // Demonstrates that callbacks are not able to change (filter) values.
    })

    ->run();

echo ' | ' . $request->getParam('arg');
