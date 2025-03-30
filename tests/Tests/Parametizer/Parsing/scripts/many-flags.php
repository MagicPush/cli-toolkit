<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../init-console.php';

$request = TestUtils::newConfig()
    ->newFlag('--flag-x', '-x')
    ->newFlag('--flag-y', '-y')
    ->newFlag('--flag-z', '-z')
    ->newOption('--option', '-o')
    ->run();

echo json_encode($request->getParams());
