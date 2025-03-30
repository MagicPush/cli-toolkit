<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../init-console.php';

TestUtils::newConfig()
    ->newArgument('arg')
    ->validatorPattern('/^[a-z]+$/')

    ->run();
