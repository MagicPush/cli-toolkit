<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;

require_once __DIR__ . '/../../../../../init-console.php';

$envConfig = EnvironmentConfig::createFromConfigsBottomUpHierarchy(__DIR__, __DIR__, true);
echo json_encode(
    $envConfig,
    JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
    | JSON_UNESCAPED_LINE_TERMINATORS
    | JSON_THROW_ON_ERROR
    | JSON_PRETTY_PRINT,
);
