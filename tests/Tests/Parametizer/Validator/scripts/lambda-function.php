<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../init-console.php';

TestUtils::newConfig()
    ->newArgument('arg')
    ->validatorCallback(function ($value) {
        if (!is_numeric($value)) {
            return false;
        }

        return ($value % 3) === 0;
    })
    ->run();
