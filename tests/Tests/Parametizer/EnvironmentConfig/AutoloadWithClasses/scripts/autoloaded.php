<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../init-console.php';

use MagicPush\CliToolkit\Tests\Tests\Parametizer\EnvironmentConfig\AutoloadWithClasses\ScriptClasses\TestChild\TestChild;
use MagicPush\CliToolkit\Tests\Tests\Parametizer\EnvironmentConfig\AutoloadWithClasses\ScriptClasses\TestSome\TestSome;
use MagicPush\CliToolkit\Tests\Utils\TestUtils;

$request = TestUtils::newConfig()
    ->newSubcommandSwitch('subcommand')
    ->newSubcommand(TestSome::getFullName(), TestSome::getConfiguration(throwOnException: true))
    ->newSubcommand(TestChild::getFullName(), TestChild::getConfiguration(throwOnException: true))

    ->run();

$envConfig = $request->getSubcommandRequest()->config->getEnvConfig();
echo TestUtils::getEnvironmentConfigPartJson($envConfig);
