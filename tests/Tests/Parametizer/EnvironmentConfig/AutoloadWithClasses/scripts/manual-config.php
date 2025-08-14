<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../init-console.php';

use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Tests\Tests\Parametizer\EnvironmentConfig\AutoloadWithClasses\ScriptClasses\TestChild\TestChild;
use MagicPush\CliToolkit\Tests\Tests\Parametizer\EnvironmentConfig\AutoloadWithClasses\ScriptClasses\TestSome\TestSome;
use MagicPush\CliToolkit\Tests\Utils\TestUtils;

$subcommandsEnvConfig = new EnvironmentConfig();

$subcommandsEnvConfig->optionHelpShortName = 'C';

$request = TestUtils::newConfig()
    ->newSubcommand(
        TestSome::getScriptName(),
        TestSome::getConfigBuilder($subcommandsEnvConfig, throwOnException: true),
    )
    ->newSubcommand(
        TestChild::getScriptName(),
        TestChild::getConfigBuilder($subcommandsEnvConfig, throwOnException: true),
    )

    ->run();

$envConfig = $request->getSubcommandRequest()->config->getEnvConfig();
echo TestUtils::getEnvironmentConfigPartJson($envConfig);
