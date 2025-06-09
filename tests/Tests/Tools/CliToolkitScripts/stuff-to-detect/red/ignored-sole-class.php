<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Parametizer;

class SomeScript {
    public static function execute(): void {
        Parametizer::newConfig(throwOnException: true)->run();
    }
}
