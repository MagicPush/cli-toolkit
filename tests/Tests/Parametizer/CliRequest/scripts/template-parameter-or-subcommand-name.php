<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\CliRequest\CliRequest;
use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../init-console.php';

$parameterType = $_SERVER['argv'][1];
unset($_SERVER['argv'][1]);

$configBuilder = TestUtils::newConfig();
$parameterName = CliRequest::SUBCOMMAND_PREFIX . 'something';

switch ($parameterType) {
    case 'argument':
        $configBuilder->newArgument($parameterName);
        break;

    case 'option':
        $configBuilder->newOption("--{$parameterName}");
        break;

    case 'subcommand':
        $configBuilder
            ->newSubcommandSwitch('subcommand')
            ->newSubcommand($parameterName, TestUtils::newConfig());
        break;

    default:
        $parameterTypeExport = var_export($parameterType, true);
        throw new Exception("Unexpected parameter type {$parameterTypeExport}");
}

$configBuilder->run();
