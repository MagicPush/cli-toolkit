<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

Parametizer::newConfig()
    ->newFlag('--flag-one', '-f')
    ->callback(function () {
        die('"-f" stands for "--flag-one"');
    })

    ->newFlag('--flag-two', '-F')
    ->callback(function () {
        die('"-F" stands for "--flag-two"');
    })

    ->run();
