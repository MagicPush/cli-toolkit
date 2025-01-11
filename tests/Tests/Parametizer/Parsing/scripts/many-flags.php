<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

$request = Parametizer::newConfig(throwOnException: true)
    ->newFlag('--flag-x', '-x')
    ->newFlag('--flag-y', '-y')
    ->newFlag('--flag-z', '-z')
    ->newOption('--option', '-o')
    ->run();

echo json_encode($request->getParams());
