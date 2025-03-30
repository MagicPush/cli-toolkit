<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Script\ScriptAbstract;
use MagicPush\CliToolkit\Tests\utils\TestUtils;

require_once __DIR__ . '/../../../init-console.php';

$description = <<<TEXT
    Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna
    aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.
    Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur
    sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
TEXT;

TestUtils::newConfig()
    ->newSubcommandSwitch('switchme')

    // Let's add subcommands with names in a "random" order. The sorting logic should fix the order.
    ->newSubcommand(implode(ScriptAbstract::NAME_SECTION_SEPARATOR, ['yellow', 'banana', 'ice-cream']), TestUtils::newConfig()->description($description))
    ->newSubcommand(implode(ScriptAbstract::NAME_SECTION_SEPARATOR, ['test']), TestUtils::newConfig()->description($description))
    ->newSubcommand(implode(ScriptAbstract::NAME_SECTION_SEPARATOR, ['red']), TestUtils::newConfig()->description($description))
    ->newSubcommand(implode(ScriptAbstract::NAME_SECTION_SEPARATOR, ['red', 'flower', 'pot']), TestUtils::newConfig()->description($description))
    ->newSubcommand(implode(ScriptAbstract::NAME_SECTION_SEPARATOR, ['yellow', 'banana']), TestUtils::newConfig()->description($description))
    ->newSubcommand(
        implode(ScriptAbstract::NAME_SECTION_SEPARATOR, ['blue', 'flower', 'tea']),
        TestUtils::newConfig()
            ->description('Yes, such a flower does exists!')
            ->newFlag('--godmode')
            ->description('IDDQD'),
    )
    ->newSubcommand(implode(ScriptAbstract::NAME_SECTION_SEPARATOR, ['red', 'lever']), TestUtils::newConfig()->description($description))
    ->newSubcommand(implode(ScriptAbstract::NAME_SECTION_SEPARATOR, ['red', 'book']), TestUtils::newConfig()->description($description))
    ->newSubcommand(implode(ScriptAbstract::NAME_SECTION_SEPARATOR, ['green', 'house']), TestUtils::newConfig()->description($description))
    ->newSubcommand(implode(ScriptAbstract::NAME_SECTION_SEPARATOR, ['red', 'flower']), TestUtils::newConfig()->description($description))
    ->newSubcommand(implode(ScriptAbstract::NAME_SECTION_SEPARATOR, ['avocado-is-one-of-popular-fruits-you-see-in-menu']), TestUtils::newConfig()->description($description))

    ->run();
