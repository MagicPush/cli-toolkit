<?php declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\HelpGenerator;

use MagicPush\CliToolkit\Parametizer\Config\HelpGenerator;
use MagicPush\CliToolkit\Tests\Tests\TestCaseAbstract;
use PHPUnit\Framework\Attributes\CoversClass;

use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertStringStartsWith;

#[CoversClass(HelpGenerator::class)]
class HelpGeneratorTest extends TestCaseAbstract {
    /**
     * Tests the help generator for the majority of cases (except subcommands; see dedicated tests below).
     *
     * Output is redirected, so no text styles here.
     *
     * @covers HelpGenerator::getFullHelp()
     */
    public function testScriptHelp(): void {
        assertSame(
            <<<HELP

          Here is a very very very very long description.
          So long that multiple lines are needed.

          And there is more than that! Here is a list with padding, which should be outputted the same way in a terminal:
              1. Start with this step.
              2. Proceed with doing this thing.
              3. Finally finish by doing this stuff.

                                              HERE IS SOME MORE PADDED TEXT

        USAGE

          lots-of-params.php [-fg] [--opt-default=… | -o …] [--opt-list=… | -l …] [--flag1] [--flag2] [--flag3] --opt-required=… <arg-required> [<arg-optional>] [<arg-list>]
          lots-of-params.php --opt-required=5 arg
          lots-of-params.php --opt-required=5 arg -fg C asd zxc

          Same usage, but with description:
          lots-of-params.php --opt-required=5 arg -fg C asd zxc

          Usage long example with description:
          lots-of-params.php argument --opt-required=pink --opt-default=weee --flag2 A --opt-list=250 --opt-list=500 -- arg_elem_1 arg_elem_2 --opt=not_option_but_arg_elem3

        OPTIONS

          --help                  Show full help page.

          --opt-required=…        Required option: pick one from the list
          (required)              Allowed values:
                                   - black A pile of books
                                   - pink  A heap of ponies
                                   - white
                                   - 5     Give me "five"!

          -o …, --opt-default=…   Non-required option with a default value
                                  Default: opt_default_value

          -l …, --opt-list=…      List of values
                                  Allowed values: 100, 150, 200, 250, 300, 350, 400, 450, 500, 550, 600, 650, 700, 750, 800
                                  (multiple values allowed)

          -f, --flag1             Some flag

          -g, --flag2

          --flag3                 Flag without short name

        ARGUMENTS

          <arg-required>   Required argument
          (required)

          <arg-optional>   Optional argument: pick one from the list
                           Allowed values: A, B, C
                           Default: B

          <arg-list>       (multiple values allowed)


        HELP,
            static::assertNoErrorsOutput(__DIR__ . '/scripts/lots-of-params.php', '--help')->getStdOut(),
        );
    }

    /**
     * Help for subcommands, top level. Also tests the short descriptions.
     *
     * @covers HelpGenerator::getFullHelp()
     */
    public function testSubcommandScriptHelpFirstLevel(): void {
        assertSame(
            <<<HELP

        USAGE

          deep-nesting.php <switchme>

        OPTIONS

          --help   Show full help page.

        ARGUMENTS

          <switchme>   Allowed values: test11, test12
          (required)   Subcommand help: <script_name> ... <subcommand value> --help

        COMMANDS

          deep-nesting.php test11 [--name-l2=…] <switchme-l2>

          deep-nesting.php test12


        HELP,
            static::assertNoErrorsOutput(__DIR__ . '/scripts/deep-nesting.php', '--help')->getStdOut(),
        );
    }

    /**
     * Help for a specific subcommand.
     *
     * @covers HelpGenerator::getFullHelp()
     * @covers HelpGenerator::getBaseScriptName()
     */
    public function testSubcommandScriptHelpDeepInSubcommand(): void {
        assertSame(
            <<<HELP

        USAGE

          deep-nesting.php test11 [--name-l2=…] test23 [--name-l3=…] <switchme-l3>
          deep-nesting.php test11 --name-l2=supername test23 test31

          Very deep call:
          deep-nesting.php test11 --name-l2=supername test23 --name-l3=nameLevelThree test32

        OPTIONS

          --help        Show full help page.

          --name-l3=…

        ARGUMENTS

          <switchme-l3>   Allowed values: test31, test32
          (required)      Subcommand help: <script_name> ... <subcommand value> --help

        COMMANDS

          deep-nesting.php test11 [--name-l2=…] test23 [--name-l3=…] test31

          deep-nesting.php test11 [--name-l2=…] test23 [--name-l3=…] test32


        HELP,
            static::assertNoErrorsOutput(__DIR__ . '/scripts/deep-nesting.php', 'test11 test23 --help')->getStdOut(),
        );
    }

    /**
     * Tests short descriptions output. The short descriptions are rendered when listing available subcommands.
     *
     * @covers HelpGenerator::getShortDescription()
     */
    public function testSubcommandHelpShortDescription(): void {
        assertSame(
            <<<HELP

            USAGE

              subcommands-long-description.php <switchme>

            OPTIONS

              --help   Show full help page.

            ARGUMENTS

              <switchme>   Allowed values: multistring, long-string, long-string-short-sentence, unbreakable-long-line
              (required)   Subcommand help: <script_name> ... <subcommand value> --help

            COMMANDS

              subcommands-long-description.php multistring                  Short description on the first line.

              subcommands-long-description.php long-string                  Here is a sort of... short description.

              subcommands-long-description.php long-string-short-sentence   Too short to stop here. So the description continues for some more

              subcommands-long-description.php unbreakable-long-line        Thatisareallylonglinebutthereisnowaytobreakitcorrectlysothelinewillbec


            HELP,
            static::assertNoErrorsOutput(__DIR__ . '/scripts/subcommands-long-description.php', '--help')->getStdOut(),
        );

        // ... And let's check the full descriptions to show the difference between short and long versions.

        assertStringStartsWith(
            <<<HELP

              Short description on the first line.
              The rest of long description is omitted while shown beside subcommand possible values.
            HELP
            ,
            static::assertNoErrorsOutput(__DIR__ . '/scripts/subcommands-long-description.php', 'multistring --help')
                ->getStdOut(),
        );
        assertStringStartsWith(
            <<<HELP

              Here is a sort of... short description. The long description continues on the same line and this line is too long, but it is still not enough so...
              Here is another line :)
            HELP
            ,
            static::assertNoErrorsOutput(__DIR__ . '/scripts/subcommands-long-description.php', 'long-string --help')
                ->getStdOut(),
        );
        assertStringStartsWith(
            <<<HELP

              Too short to stop here. So the description continues for some more words before the limit is reached.
            HELP
            ,
            static::assertNoErrorsOutput(
                __DIR__ . '/scripts/subcommands-long-description.php',
                'long-string-short-sentence --help',
            )
                ->getStdOut(),
        );
        assertStringStartsWith(
            <<<HELP

              Thatisareallylonglinebutthereisnowaytobreakitcorrectlysothelinewillbecutbrutallyafterthecharacterslimitisreached.
            HELP
            ,
            static::assertNoErrorsOutput(
                __DIR__ . '/scripts/subcommands-long-description.php',
                'unbreakable-long-line --help',
            )
                ->getStdOut(),
        );
    }
}
