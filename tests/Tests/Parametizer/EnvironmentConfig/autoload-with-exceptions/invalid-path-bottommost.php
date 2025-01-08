<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;

require_once __DIR__ . '/../../../init-console.php';

EnvironmentConfig::createFromConfigsBottomUpHierarchy('non-existing-directory', __DIR__, isset($argv[1]));
