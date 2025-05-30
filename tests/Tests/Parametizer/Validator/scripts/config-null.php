<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

Parametizer::newConfig(throwOnException: true)
    ->newArgument('arg')
    ->validatorPattern('/^[a-z]+$/')
    ->validatorPattern(null) // Disables the validator - removes the validation above.

    ->run();
