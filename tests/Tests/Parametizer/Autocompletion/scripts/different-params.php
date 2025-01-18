<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Tests\Tests\Parametizer\Autocompletion\Config\DifferentParams;

require_once __DIR__ . '/../../../init-console.php';

DifferentParams::getConfigBuilder()->run();
