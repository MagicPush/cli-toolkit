<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red;

use MagicPush\CliToolkit\Parametizer\Config\Builder\BuilderInterface;
use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;

class Something1 extends RedBase {
    public static function getConfiguration(
        ?EnvironmentConfig $envConfig = null,
        bool $throwOnException = false,
    ): BuilderInterface {
        return static::newConfig($envConfig, $throwOnException);
    }

    public function execute(): void { }
}
