<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../init-console.php';

$request = TestUtils::newConfig()
    ->newOption('--option1', '-f')
    ->description('First option')
    ->required()

    ->newOption('--option2')
    ->description('Second option')
    ->required()

    ->newOption('--option3', '-t')
    ->description('Third option')
    ->required()

    ->run();
