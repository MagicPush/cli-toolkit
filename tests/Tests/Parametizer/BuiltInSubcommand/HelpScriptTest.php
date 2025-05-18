<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\BuiltInSubcommand;

use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Parametizer\Script\BuiltinSubcommand\HelpScript;
use MagicPush\CliToolkit\Tests\Tests\TestCaseAbstract;
use PHPUnit\Framework\Attributes\DataProvider;

use function PHPUnit\Framework\assertSame;

class HelpScriptTest extends TestCaseAbstract {
    #[DataProvider('provideShowHelpForSubcommand')]
    /**
     * Tests help output for different parameters (subcommand names).
     *
     * @see HelpScript::getConfiguration()
     * @see HelpScript::execute()
     * @see Config::commitSubcommandSwitch()
     */
    public function testShowHelpForSubcommand(string $parametersString, string $expectedOutput): void {
        assertSame(
            $expectedOutput,
            static::assertNoErrorsOutput(
                __DIR__ . '/scripts/subcommands-with-name-sections.php',
                Config::OPTION_NAME_HELP . ' ' . $parametersString,
            )
                ->getStdOut(),
        );
    }

    /**
     * @return array[]
     */
    public static function provideShowHelpForSubcommand(): array {
        return [
            'no-value' => [
                'parametersString' => '',
                'expectedOutput'   => <<<TEXT

                  Outputs a help page for a specified subcommand.

                USAGE

                  subcommands-with-name-sections.php help [<subcommand-name>]

                OPTIONS

                  --help   Show full help page.

                ARGUMENTS

                  <subcommand-name>   Name of any registered subcommand.
                                      See 'list' subcommand for the list of possible values.
                                      Default: help


                TEXT,
            ],
            'help-yourself' => [
                'parametersString' => 'help',
                'expectedOutput'   => <<<TEXT

                  Outputs a help page for a specified subcommand.

                USAGE

                  subcommands-with-name-sections.php help [<subcommand-name>]

                OPTIONS

                  --help   Show full help page.

                ARGUMENTS

                  <subcommand-name>   Name of any registered subcommand.
                                      See 'list' subcommand for the list of possible values.
                                      Default: help


                TEXT,
            ],
            'some-subcommand' => [
                'parametersString' => 'avocado-is-one-of-popular-fruits-you-see-in-menu',
                'expectedOutput'   => <<<TEXT

                  Avocado is an edible fruit. Avocados are native to the Western Hemisphere from Mexico south to the Andean regions
                  and are widely grown in warm climates. Avocado fruits have greenish or yellowish flesh with a buttery consistency
                  and a rich nutty flavour. They are often eaten in salads, and in many parts of the world they are eaten
                  as a dessert. Mashed avocado is the principal ingredient of guacamole, a characteristic sauce-like condiment
                  in Mexican cuisine. Avocados provide thiamine, riboflavin, and vitamin A, and in some varieties the flesh contains
                  as much as 25 percent unsaturated oil.

                USAGE

                  subcommands-with-name-sections.php avocado-is-one-of-popular-fruits-you-see-in-menu

                OPTIONS

                  --help   Show full help page.


                TEXT,
            ],
            'another-subcommand' => [
                'parametersString' => 'blue:flower:tea',
                'expectedOutput'   => <<<TEXT

                  Yes, such a flower does exists!

                USAGE

                  subcommands-with-name-sections.php blue:flower:tea [--god-mode]

                OPTIONS

                  --help       Show full help page.

                  --god-mode   I-D-D-Q-D


                TEXT,
            ],
        ];
    }

    /**
     * Tests error output if an invalid subcommand name is specified.
     *
     * @see HelpScript::getConfiguration()
     * @see HelpScript::execute()
     * @see Config::commitSubcommandSwitch()
     */
    public function testHelpInvalidSubcommand(): void {
        static::assertParseErrorOutput(
            __DIR__ . '/scripts/subcommands-with-name-sections.php',
            <<<TEXT
            Incorrect value 'black:flower:tea' for argument <subcommand-name>


              --help              Show full help page.
            
              <subcommand-name>   Name of any registered subcommand.
                                  See 'list' subcommand for the list of possible values.
                                  Default: help

            TEXT,
            Config::OPTION_NAME_HELP . ' black:flower:tea',
        );
    }
}
