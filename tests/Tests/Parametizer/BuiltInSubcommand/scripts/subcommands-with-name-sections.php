<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Script\ScriptAbstract;
use MagicPush\CliToolkit\Tests\Utils\TestUtils;

require_once __DIR__ . '/../../../init-console.php';

$description = <<<TEXT
    Avocado is an edible fruit. Avocados are native to the Western Hemisphere from Mexico south to the Andean regions
    and are widely grown in warm climates. Avocado fruits have greenish or yellowish flesh with a buttery consistency
    and a rich nutty flavour. They are often eaten in salads, and in many parts of the world they are eaten
    as a dessert. Mashed avocado is the principal ingredient of guacamole, a characteristic sauce-like condiment
    in Mexican cuisine. Avocados provide thiamine, riboflavin, and vitamin A, and in some varieties the flesh contains
    as much as 25 percent unsaturated oil.
TEXT;

TestUtils::newConfig()
    ->newSubcommandSwitch('subcommand-name')

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

            ->newFlag('--god-mode')
            ->description('I-D-D-Q-D'),
    )
    ->newSubcommand(implode(ScriptAbstract::NAME_SECTION_SEPARATOR, ['red', 'lever']), TestUtils::newConfig()->description($description))
    ->newSubcommand(implode(ScriptAbstract::NAME_SECTION_SEPARATOR, ['red', 'book']), TestUtils::newConfig()->description($description))
    ->newSubcommand(implode(ScriptAbstract::NAME_SECTION_SEPARATOR, ['green', 'house']), TestUtils::newConfig()->description($description))
    ->newSubcommand(implode(ScriptAbstract::NAME_SECTION_SEPARATOR, ['red', 'flower']), TestUtils::newConfig()->description($description))
    ->newSubcommand(implode(ScriptAbstract::NAME_SECTION_SEPARATOR, ['avocado-is-one-of-popular-fruits-you-see-in-menu']), TestUtils::newConfig()->description($description))

    ->run();
