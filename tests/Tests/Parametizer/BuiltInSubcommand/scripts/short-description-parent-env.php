<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../init-console.php';

$description = <<<TEXT
    Avocado is an edible fruit. Avocados are native to the Western Hemisphere from Mexico south to the Andean regions
    and are widely grown in warm climates.
TEXT;

$envConfigForSuperShortDescription = new EnvironmentConfig();

$envConfigForSuperShortDescription->helpGeneratorShortDescriptionCharsMinBeforeFullStop = 0;
$envConfigForSuperShortDescription->helpGeneratorShortDescriptionCharsMax               = 20;

$isCustomEnvSetForParent = boolval($_SERVER['argv'][2]);
unset($_SERVER['argv'][2]);
$isCustomEnvSetForSubcommand = !$isCustomEnvSetForParent;

TestUtils::newConfig($isCustomEnvSetForParent ? $envConfigForSuperShortDescription : null)
    ->newSubcommandSwitch('subcommand')
    ->newSubcommand(
        'avocado',
        TestUtils::newConfig($isCustomEnvSetForSubcommand ? $envConfigForSuperShortDescription : null)
            ->description($description),
    )

    ->run();
