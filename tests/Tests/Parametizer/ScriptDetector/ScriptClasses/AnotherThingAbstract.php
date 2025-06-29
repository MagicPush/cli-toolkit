<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses;

use MagicPush\CliToolkit\Parametizer\Config\Builder\ConfigBuilder;
use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Parametizer\Parametizer;

abstract class AnotherThingAbstract {
    protected static function newConfig(?EnvironmentConfig $envConfig, bool $throwOnException): ConfigBuilder {
        return Parametizer::newConfig($envConfig, $throwOnException);
    }

    public static function getFullName(): string {
        return 'you-should-not-see-this';
    }
}
