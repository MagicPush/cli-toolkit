<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Tests\Tests\Parametizer\Completion\Config\DifferentParams;

require_once __DIR__ . '/../../../init-console.php';

DifferentParams::getConfigBuilder()->run();
