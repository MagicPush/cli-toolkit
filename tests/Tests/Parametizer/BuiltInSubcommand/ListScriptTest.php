<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\BuiltInSubcommand;

use MagicPush\CliToolkit\Parametizer\Script\BuiltInSubcommand\ListScript;
use MagicPush\CliToolkit\Tests\Tests\TestCaseAbstract;
use PHPUnit\Framework\Attributes\DataProvider;

use function PHPUnit\Framework\assertSame;

class ListScriptTest extends TestCaseAbstract {
    /**
     * Tests correct subcommand names sort with different name section levels.
     *
     * Also:
     *  * Built-in subcommands must be sorted separately and appear at the top.
     *  * Short descriptions must be aligned based on the longest subcommand name and it's "level".
     *
     * @see ListScript::execute()
     * @see ListScript::outputNode()
     */
    public function testNameSectionsSorting(): void {
        assertSame(
            <<<TEXT
             Built-in subcommands:
                help                                                Outputs a help page for a specified subcommand.
                list                                                Shows the sorted list of available subcommands with their short

             --
                avocado-is-one-of-popular-fruits-you-see-in-menu    Avocado is an edible fruit. Avocados are native to the Western
                red                                                 Avocado is an edible fruit. Avocados are native to the Western
                test                                                Avocado is an edible fruit. Avocados are native to the Western

             blue:
                blue:flower:
                    blue:flower:tea                                 Yes, such a flower does exists!

             green:
                green:house                                         Avocado is an edible fruit. Avocados are native to the Western

             red:
                red:book                                            Avocado is an edible fruit. Avocados are native to the Western
                red:flower                                          Avocado is an edible fruit. Avocados are native to the Western
                red:lever                                           Avocado is an edible fruit. Avocados are native to the Western

                red:flower:
                    red:flower:pot                                  Avocado is an edible fruit. Avocados are native to the Western

             yellow:
                yellow:banana                                       Avocado is an edible fruit. Avocados are native to the Western

                yellow:banana:
                    yellow:banana:ice-cream                         Avocado is an edible fruit. Avocados are native to the Western

            TEXT,
            static::assertNoErrorsOutput(__DIR__ . '/scripts/subcommands-with-name-sections.php', 'list')
                ->getStdOut(),
        );
    }

    #[DataProvider('provideSearchBySubcommandNamePart')]
    /**
     * Tests output after subcommand name part filtering.
     *
     * Also:
     *  * Sections without subcommands should not be shown.
     *  * Short description padding should adapt according to the widest name column.
     *  * Built-in subcommands must always appear.
     *
     * @see ListScript::execute()
     */
    public function testSearchBySubcommandNamePart(string $subcommandNamePart, string $expectedOutput): void {
        assertSame(
            $expectedOutput,
            static::assertNoErrorsOutput(
                __DIR__ . '/scripts/subcommands-with-name-sections.php',
                "list {$subcommandNamePart}",
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
                 Built-in subcommands:
                    help    Outputs a help page for a specified subcommand.
                    list    Shows the sorted list of available subcommands with their short


                TEXT,
            ],

            'starts-with' => [
                'subcommandNamePart' => 'red',
                'expectedOutput'     => <<<TEXT
                 Built-in subcommands:
                    help                  Outputs a help page for a specified subcommand.
                    list                  Shows the sorted list of available subcommands with their short

                 --
                    red                   Avocado is an edible fruit. Avocados are native to the Western

                 red:
                    red:book              Avocado is an edible fruit. Avocados are native to the Western
                    red:flower            Avocado is an edible fruit. Avocados are native to the Western
                    red:lever             Avocado is an edible fruit. Avocados are native to the Western

                    red:flower:
                        red:flower:pot    Avocado is an edible fruit. Avocados are native to the Western

                TEXT,
            ],

            'substring-in-middle' => [
                'subcommandNamePart' => 'flower',
                'expectedOutput'     => <<<TEXT
                 Built-in subcommands:
                    help                   Outputs a help page for a specified subcommand.
                    list                   Shows the sorted list of available subcommands with their short

                 blue:
                    blue:flower:
                        blue:flower:tea    Yes, such a flower does exists!

                 red:
                    red:flower             Avocado is an edible fruit. Avocados are native to the Western

                    red:flower:
                        red:flower:pot     Avocado is an edible fruit. Avocados are native to the Western

                TEXT,
            ],

            'one-letter' => [
                'subcommandNamePart' => 'u',
                'expectedOutput'     => <<<TEXT
                 Built-in subcommands:
                    help                                                Outputs a help page for a specified subcommand.
                    list                                                Shows the sorted list of available subcommands with their short

                 --
                    avocado-is-one-of-popular-fruits-you-see-in-menu    Avocado is an edible fruit. Avocados are native to the Western

                 blue:
                    blue:flower:
                        blue:flower:tea                                 Yes, such a flower does exists!

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
     *          however, built-in subcommands are still sorted separately and put at the top of the outputted list.
     *
     * @see ListScript::outputNode()
     */
    public function testSlim(): void {
        assertSame(
            <<<TEXT
            help                                                Outputs a help page for a specified subcommand.
            list                                                Shows the sorted list of available subcommands with their short
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
            static::assertNoErrorsOutput(__DIR__ . '/scripts/subcommands-with-name-sections.php', 'list --slim')
                ->getStdOut(),
        );
    }
}
