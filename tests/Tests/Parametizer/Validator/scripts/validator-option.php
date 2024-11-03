<?php declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

Parametizer::newConfig()
    ->newOption('--opt', '-o')
    ->validatorPattern('/^[a-z]+$/')

    ->run();
