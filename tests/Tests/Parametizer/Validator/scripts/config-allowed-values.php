<?php declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

Parametizer::newConfig()
    ->newOption('--one-of-values')
    ->allowedValues(['111', '222', '333'])

    ->newOption('--one-of-values-emptied')
    ->allowedValues(['111', '222', '333'])
    ->allowedValues([]) // Overwrite allowed values and ensure that there is no more allowed values validation.

    ->newOption('--validator-no-particular-allowed-values')
    ->validatorCallback('ctype_alpha')
    // Ensure that emptying allowed values (without setting those values in the first place)
    // does not nullify previously defined custom validator callback.
    ->allowedValues([])

    ->run();
