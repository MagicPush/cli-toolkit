<?php declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

// Suppressing warnings from preg_match due to bad pattern:
error_reporting(E_ERROR);

Parametizer::newConfig()
    ->newArgument('arg')
    ->validatorPattern('neither-callable-nor-pattern') // This thing cannot be a validator.

    ->run();
