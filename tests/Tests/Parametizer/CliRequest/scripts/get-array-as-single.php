<?php declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

$request = Parametizer::newConfig()
    ->newArrayOption('--option-array')

    ->run();

$request->getParamAsInt('option-array');
