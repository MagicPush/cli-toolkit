<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../init-console.php';

$envConfigMain = new EnvironmentConfig();
$envConfigL2S2 = new EnvironmentConfig();
$envConfigL3S1 = new EnvironmentConfig();

$envConfigMain->optionHelpShortName = 'X';
$envConfigL2S2->optionHelpShortName = 'y';
$envConfigL3S1->optionHelpShortName = 'z';

TestUtils::newConfig($envConfigMain)
    ->newSubcommandSwitch('subcommand-name-l1')
    ->newSubcommand('conf-l2-s1', TestUtils::newConfig())
    ->newSubcommand(
        'conf-l2-s2',
        TestUtils::newConfig($envConfigL2S2)
            ->newSubcommandSwitch('subcommand-name-l2-s2')
            ->newSubcommand('conf-l3-s1', TestUtils::newConfig($envConfigL3S1))
            ->newSubcommand('conf-l3-s2', TestUtils::newConfig()),
    )
    ->run();
