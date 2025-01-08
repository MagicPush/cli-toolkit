<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Parametizer\Parametizer;

require_once __DIR__ . '/../../../init-console.php';

$envConfig = new EnvironmentConfig();

$envConfig->helpGeneratorShortDescriptionCharsMinBeforeFullStop = (int) $argv[2];
$envConfig->helpGeneratorShortDescriptionCharsMax               = (int) $argv[3];

Parametizer::newConfig($envConfig)
    ->newSubcommandSwitch('switchme')
    ->newSubcommand(
        'conf-s1',
        Parametizer::newConfig()
            ->description('Just a very long single-line string that has lots of characters, thus should be trimmed gracefully near the farthest space character.'),
    )
    ->newSubcommand(
        'conf-s2',
        Parametizer::newConfig()
            ->description('Too short string. Another shorty. The rest adds much more characters, what makes the whole line too long.'),
    )
    ->run();
