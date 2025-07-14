<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../../init-console.php';

/** @noinspection PhpUnused */
/** @noinspection PhpMultipleClassDeclarationsInspection */
class SomethingContext {
    /** @noinspection PhpUnused */
    public string $someVar;
}

Parametizer::newConfig()->run();
