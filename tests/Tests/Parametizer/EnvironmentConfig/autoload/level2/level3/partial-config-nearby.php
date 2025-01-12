<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../../../init-console.php';

$envConfig = Parametizer::newConfig(throwOnException: true)->getConfig()->getEnvConfig();
echo json_encode(
    $envConfig,
    JSON_THROW_ON_ERROR
    | JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
    | JSON_UNESCAPED_LINE_TERMINATORS
    | JSON_PRETTY_PRINT,
);
