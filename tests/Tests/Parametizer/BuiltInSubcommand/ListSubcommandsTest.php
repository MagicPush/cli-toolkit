<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\BuiltInSubcommand;

use MagicPush\CliToolkit\Parametizer\Config\HelpGenerator\HelpGenerator;
use MagicPush\CliToolkit\Parametizer\ScriptClass\BuiltinSubcommand\ListSubcommands;
use MagicPush\CliToolkit\Tests\Tests\TestCaseAbstract;
use PHPUnit\Framework\Attributes\DataProvider;

use function PHPUnit\Framework\assertSame;

final class ListSubcommandsTest extends TestCaseAbstract {
    /**
     * Tests natural sorting for registered subcommand names.
     *
     * @see ListSubcommands::outputNode()
     */
    public function testNamesNaturalSorting(): void {
        assertSame(
            <<<TEXT
             Built-in:
               help        Outputs a help page for a specified subcommand.
               list        Shows available subcommands.

             --
               script
               script1
               script2
               script10
               scripts

            TEXT,
            static::assertNoErrorsOutput(__DIR__ . '/scripts/subcommands-natural-sorting.php', ListSubcommands::getScriptName())
                ->getStdOut(),
        );
    }

    /**
     * Tests correct subcommand names sort with different name section levels.
     *
     * Also:
     *  * Subcommands under custom headers (like built-in subcommands) must be sorted separately and appear at the top.
     *  * Short descriptions must be aligned based on the longest subcommand name and it's "level".
     *
     * @see ListSubcommands::execute()
     * @see ListSubcommands::outputNode()
     */
    public function testNameSectionsSorting(): void {
        assertSame(
            <<<TEXT
             Built-in:
               help                                                Outputs a help page for a specified subcommand.
               list                                                Shows available subcommands.

             --
               avocado-is-one-of-popular-fruits-you-see-in-menu    Avocado is an edible fruit. Avocados are native to the Western
               red                                                 Avocado is an edible fruit. Avocados are native to the Western
               test                                                Avocado is an edible fruit. Avocados are native to the Western

             blue:
               blue:flower:
                 blue:flower:tea                                   Yes, such a flower does exists!

             green:
               green:house                                         Avocado is an edible fruit. Avocados are native to the Western

             red:
               red:book                                            Avocado is an edible fruit. Avocados are native to the Western
               red:flower                                          Avocado is an edible fruit. Avocados are native to the Western
               red:lever                                           Avocado is an edible fruit. Avocados are native to the Western

               red:flower:
                 red:flower:pot                                    Avocado is an edible fruit. Avocados are native to the Western

             yellow:
               yellow:banana                                       Avocado is an edible fruit. Avocados are native to the Western

               yellow:banana:
                 yellow:banana:ice-cream                           Avocado is an edible fruit. Avocados are native to the Western

            TEXT,
            static::assertNoErrorsOutput(__DIR__ . '/scripts/subcommands-with-name-sections.php', ListSubcommands::getScriptName())
                ->getStdOut(),
        );
    }

    /**
     * Tests output after subcommand name part filtering.
     *
     * Also:
     *  * Sections without subcommands should not be shown.
     *  * Short description padding should adapt according to the widest name column.
     *  * Built-in subcommands must always appear.
     *
     * @see ListSubcommands::execute()
     */
    #[DataProvider('provideSearchBySubcommandNamePart')]
    public function testSearchBySubcommandNamePart(string $subcommandNamePart, string $expectedOutput): void {
        assertSame(
            $expectedOutput,
            static::assertNoErrorsOutput(
                __DIR__ . '/scripts/subcommands-with-name-sections.php',
                ListSubcommands::getScriptName() . " {$subcommandNamePart}",
            )
                ->getStdOut(),
        );
    }

    /**
     * @return array[]
     */
    public static function provideSearchBySubcommandNamePart(): array {
        return [
            'nothing-found' => [
                'subcommandNamePart' => 'subcommand-that-does-not-exist',
                'expectedOutput'     => <<<TEXT
                 Built-in:
                   help    Outputs a help page for a specified subcommand.
                   list    Shows available subcommands.


                TEXT,
            ],

            'starts-with' => [
                'subcommandNamePart' => 'red',
                'expectedOutput'     => <<<TEXT
                 Built-in:
                   help                Outputs a help page for a specified subcommand.
                   list                Shows available subcommands.

                 --
                   red                 Avocado is an edible fruit. Avocados are native to the Western

                 red:
                   red:book            Avocado is an edible fruit. Avocados are native to the Western
                   red:flower          Avocado is an edible fruit. Avocados are native to the Western
                   red:lever           Avocado is an edible fruit. Avocados are native to the Western

                   red:flower:
                     red:flower:pot    Avocado is an edible fruit. Avocados are native to the Western

                TEXT,
            ],

            'substring-in-middle' => [
                'subcommandNamePart' => 'flower',
                'expectedOutput'     => <<<TEXT
                 Built-in:
                   help                 Outputs a help page for a specified subcommand.
                   list                 Shows available subcommands.

                 blue:
                   blue:flower:
                     blue:flower:tea    Yes, such a flower does exists!

                 red:
                   red:flower           Avocado is an edible fruit. Avocados are native to the Western

                   red:flower:
                     red:flower:pot     Avocado is an edible fruit. Avocados are native to the Western

                TEXT,
            ],

            'one-letter' => [
                'subcommandNamePart' => 'u',
                'expectedOutput'     => <<<TEXT
                 Built-in:
                   help                                                Outputs a help page for a specified subcommand.
                   list                                                Shows available subcommands.

                 --
                   avocado-is-one-of-popular-fruits-you-see-in-menu    Avocado is an edible fruit. Avocados are native to the Western

                 blue:
                   blue:flower:
                     blue:flower:tea                                   Yes, such a flower does exists!

                 green:
                   green:house                                         Avocado is an edible fruit. Avocados are native to the Western

                TEXT,
            ],
        ];
    }

    /**
     * Tests output slim format.
     *
     * Affects:
     *  * sections: no section headers in output;
     *  * sort: all subcommands names are sorted within a single list (because of no section headers);
     *          however, subcommands under custom headers are still sorted separately (within each custom header)
     *          and put above the rest of subcommands in a custom predefined order.
     *
     * @see ListSubcommands::outputNode()
     */
    public function testSlim(): void {
        assertSame(
            <<<TEXT
            help                                                Outputs a help page for a specified subcommand.
            list                                                Shows available subcommands.
            avocado-is-one-of-popular-fruits-you-see-in-menu    Avocado is an edible fruit. Avocados are native to the Western
            blue:flower:tea                                     Yes, such a flower does exists!
            green:house                                         Avocado is an edible fruit. Avocados are native to the Western
            red                                                 Avocado is an edible fruit. Avocados are native to the Western
            red:book                                            Avocado is an edible fruit. Avocados are native to the Western
            red:flower                                          Avocado is an edible fruit. Avocados are native to the Western
            red:flower:pot                                      Avocado is an edible fruit. Avocados are native to the Western
            red:lever                                           Avocado is an edible fruit. Avocados are native to the Western
            test                                                Avocado is an edible fruit. Avocados are native to the Western
            yellow:banana                                       Avocado is an edible fruit. Avocados are native to the Western
            yellow:banana:ice-cream                             Avocado is an edible fruit. Avocados are native to the Western

            TEXT,
            static::assertNoErrorsOutput(__DIR__ . '/scripts/subcommands-with-name-sections.php', ListSubcommands::getScriptName() . ' --slim')
                ->getStdOut(),
        );
    }

    /**
     * Tests that {@see ListSubcommands} considers parent env config for generating short descriptions.
     *
     * @see ListSubcommands::outputNode()
     * @see HelpGenerator::getScriptShortDescription()
     */
    #[DataProvider('provideShortDescriptionSettingsFromParentEnvConfig')]
    public function testShortDescriptionSettingsFromParentEnvConfig(
        bool $isCustomEnvConfigForParent,
        string $expectedOutput,
    ): void {
        assertSame(
            $expectedOutput,
            static::assertNoErrorsOutput(
                __DIR__ . '/scripts/short-description-parent-env.php',
                ListSubcommands::getScriptName() . ' ' . (int) $isCustomEnvConfigForParent,
            )
                ->getStdOut(),
        );
    }

    /**
     * @return array[]
     */
    public static function provideShortDescriptionSettingsFromParentEnvConfig(): array {
        return [
            /*
             * The default env config settings allow optimal short description size.
             * In this case the parent env config is based on scripts global json,
             * while a subcommand utilizes a custom env config.
             * The custom env config does not affect output because it is not set for a parent script config.
             */
            'default' => [
                'isCustomEnvConfigForParent' => false,
                'expectedOutput'             => <<<TEXT
                     Built-in:
                       help       Outputs a help page for a specified subcommand.
                       list       Shows available subcommands.

                     --
                       avocado    Avocado is an edible fruit. Avocados are native to the Western

                    TEXT,
            ],

            // Now we replace parent config with a custom one that makes short descriptions extremely short.
            'custom' => [
                'isCustomEnvConfigForParent' => true,
                'expectedOutput'             => <<<TEXT
                     Built-in:
                       help       Outputs a help page
                       list       Shows available subcommands.

                     --
                       avocado    Avocado is an edible

                    TEXT,
            ],
        ];
    }
}
