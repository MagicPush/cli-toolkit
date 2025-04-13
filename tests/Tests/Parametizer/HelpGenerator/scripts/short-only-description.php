<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../init-console.php';

TestUtils::newConfig()
    ->newSubcommandSwitch('subcommand')
    ->newSubcommand(
        'multiline',
        TestUtils::newConfig()
            ->shortDescription('
                It is a multi-line description. It could be considered long enough to be shorten...
                But due to the fact that this description is set as the "short description",
                no shortage mechanism is applied.
            ')
    )

    ->run();
