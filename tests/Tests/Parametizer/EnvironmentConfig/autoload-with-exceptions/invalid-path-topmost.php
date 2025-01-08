<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;

require_once __DIR__ . '/../../../init-console.php';

EnvironmentConfig::createFromConfigsBottomUpHierarchy(__DIR__, 'non-existing-directory', isset($argv[1]));
