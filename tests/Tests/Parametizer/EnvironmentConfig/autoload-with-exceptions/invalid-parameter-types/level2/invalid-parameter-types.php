<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;

require_once __DIR__ . '/../../../../../init-console.php';

$envConfig = EnvironmentConfig::createFromConfigsBottomUpHierarchy(__DIR__, dirname(__DIR__), isset($argv[1]));
echo json_encode(
    [
        $envConfig->optionHelpShortName,
        $envConfig->helpGeneratorShortDescriptionCharsMinBeforeFullStop,
        $envConfig->helpGeneratorShortDescriptionCharsMax,
    ],
    JSON_THROW_ON_ERROR
    | JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
    | JSON_UNESCAPED_LINE_TERMINATORS,
);
