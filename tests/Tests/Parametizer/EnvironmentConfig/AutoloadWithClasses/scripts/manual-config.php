<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../init-console.php';

use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Parametizer\Parametizer;
use MagicPush\CliToolkit\Tests\Tests\Parametizer\EnvironmentConfig\AutoloadWithClasses\ScriptClasses\TestChild\TestChild;
use MagicPush\CliToolkit\Tests\Tests\Parametizer\EnvironmentConfig\AutoloadWithClasses\ScriptClasses\TestSome\TestSome;
use MagicPush\CliToolkit\Tests\Utils\TestUtils;

$subcommandsEnvConfig = new EnvironmentConfig();

$subcommandsEnvConfig->optionHelpShortName = 'C';

$request = Parametizer::newConfig(throwOnException: true)
    ->newSubcommand(
        TestSome::getFullName(),
        TestSome::getConfiguration($subcommandsEnvConfig, throwOnException: true),
    )
    ->newSubcommand(
        TestChild::getFullName(),
        TestChild::getConfiguration($subcommandsEnvConfig, throwOnException: true),
    )

    ->run();

$envConfig = $request->getSubcommandRequest()->config->getEnvConfig();
echo TestUtils::getEnvironmentConfigPartJson($envConfig);
