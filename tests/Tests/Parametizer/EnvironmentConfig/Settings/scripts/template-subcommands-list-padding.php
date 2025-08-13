<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Parametizer\ScriptClass\BuiltinSubcommand\ListSubcommands;
use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../../init-console.php';

$envConfig = new EnvironmentConfig();

$envConfig->listPaddingLeftMain               = (int) $argv[1];
$envConfig->listPaddingLeftCommand            = (int) $argv[2];
$envConfig->listPaddingLeftCommandDescription = (int) $argv[3];
unset($_SERVER['argv'][1], $_SERVER['argv'][2], $_SERVER['argv'][3]);

echo 'BEGIN' . PHP_EOL;
TestUtils::newConfig($envConfig)
    ->newSubcommand('something', TestUtils::newConfig()->description('Some description'))
    ->newSubcommand(implode(ListSubcommands::NAME_SECTION_SEPARATOR, ['blue', 'flower', 'tea']), TestUtils::newConfig()->description('Some description'))
    ->newSubcommand(implode(ListSubcommands::NAME_SECTION_SEPARATOR, ['blue', 'suit']), TestUtils::newConfig()->description('Some description'))
    ->newSubcommand(implode(ListSubcommands::NAME_SECTION_SEPARATOR, ['something', 'with', 'very-long-name']), TestUtils::newConfig()->description('Some description'))
    ->run();
