<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

Parametizer::newConfig(throwOnException: true)
    ->newArgument('arg')
    ->validatorCallback(
        function ($value) {
            if (!is_numeric($value)) {
                throw new Exception('Numeric values only');
            }

            return ($value % 3) === 0;
        },
        'Only values that can be divided by 3 without a remainder'
    )
    ->run();
