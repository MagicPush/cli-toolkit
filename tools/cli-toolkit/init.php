<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Tools\CliToolkit\Classes\AutoloadDetector;

require_once __DIR__ . '/Classes/AutoloadDetector.php';

AutoloadDetector::detectAndRequire();

mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'en_US.UTF-8');
