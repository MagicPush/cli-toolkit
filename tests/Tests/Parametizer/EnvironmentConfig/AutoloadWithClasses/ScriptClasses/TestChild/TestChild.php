<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\EnvironmentConfig\AutoloadWithClasses\ScriptClasses\TestChild;

use MagicPush\CliToolkit\Parametizer\Config\Builder\BuilderInterface;
use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Tests\Tests\Parametizer\EnvironmentConfig\AutoloadWithClasses\ScriptClasses\TestSome\TestSome;

class TestChild extends TestSome {
    public static function getConfiguration(
        ?EnvironmentConfig $envConfig = null,
        bool $throwOnException = false,
    ): BuilderInterface {
        // This "useless" parent call without any additions is necessary for EnvironmentConfig file config detection.
        return parent::getConfiguration(envConfig: $envConfig, throwOnException: $throwOnException);
    }
}
