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
                avocado-is-one-of-popular-fruits-you-see-in-menu    Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do
                red                                                 Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do
                test                                                Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do

             blue:
                blue:flower:
                    blue:flower:tea                                 Yes, such a flower does exists!

             green:
                green:house                                         Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do

             red:
                red:book                                            Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do
                red:flower                                          Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do
                red:lever                                           Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do

                red:flower:
                    red:flower:pot                                  Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do

             yellow:
                yellow:banana                                       Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do

                yellow:banana:
                    yellow:banana:ice-cream                         Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do

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
                    red                   Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do

                 red:
                    red:book              Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do
                    red:flower            Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do
                    red:lever             Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do

                    red:flower:
                        red:flower:pot    Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do

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
                    red:flower             Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do

                    red:flower:
                        red:flower:pot     Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do

                TEXT,
            ],

            'one-letter' => [
                'subcommandNamePart' => 'u',
                'expectedOutput'     => <<<TEXT
                 Built-in subcommands:
                    help                                                Outputs a help page for a specified subcommand.
                    list                                                Shows the sorted list of available subcommands with their short

                 --
                    avocado-is-one-of-popular-fruits-you-see-in-menu    Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do

                 blue:
                    blue:flower:
                        blue:flower:tea                                 Yes, such a flower does exists!

                 green:
                    green:house                                         Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do

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
            avocado-is-one-of-popular-fruits-you-see-in-menu    Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do
            blue:flower:tea                                     Yes, such a flower does exists!
            green:house                                         Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do
            red                                                 Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do
            red:book                                            Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do
            red:flower                                          Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do
            red:flower:pot                                      Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do
            red:lever                                           Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do
            test                                                Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do
            yellow:banana                                       Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do
            yellow:banana:ice-cream                             Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do

            TEXT,
            static::assertNoErrorsOutput(__DIR__ . '/scripts/subcommands-with-name-sections.php', 'list --slim')
                ->getStdOut(),
        );
    }
}
