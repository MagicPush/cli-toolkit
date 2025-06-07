<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptClass\ScriptClasses\LocalNames;

use MagicPush\CliToolkit\Parametizer\Config\Builder\BuilderInterface;
use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Parametizer\Script\ScriptAbstract;

class SomeABBRWord extends ScriptAbstract {
    public static function getConfiguration(
        ?EnvironmentConfig $envConfig = null,
        bool $throwOnException = false,
    ): BuilderInterface {
        return static::newConfig($envConfig, $throwOnException);
    }

    public function execute(): void { }
}
