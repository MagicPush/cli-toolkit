<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight;

use MagicPush\CliToolkit\Parametizer\Config\Builder\BuilderInterface;
use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedBase;

class Something7 extends RedBase {
    public static function getConfiguration(
        ?EnvironmentConfig $envConfig = null,
        bool $throwOnException = false,
    ): BuilderInterface {
        return static::newConfig($envConfig, $throwOnException);
    }

    public function execute(): void { }
}
