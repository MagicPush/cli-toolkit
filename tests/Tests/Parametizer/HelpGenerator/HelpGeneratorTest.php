<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\HelpGenerator;

use MagicPush\CliToolkit\Parametizer\Config\Builder\VariableBuilderAbstract;
use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Parametizer\Config\HelpGenerator;
use MagicPush\CliToolkit\Parametizer\Config\Parameter\ParameterAbstract;
use MagicPush\CliToolkit\Tests\Tests\TestCaseAbstract;
use PHPUnit\Framework\Attributes\CoversClass;

use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertStringStartsWith;

#[CoversClass(HelpGenerator::class)]
class HelpGeneratorTest extends TestCaseAbstract {
    /**
     * Tests the help generator for the majority of cases (except subcommands; see dedicated tests below).
     *
     * Also tests the correct options order in {@see HelpGenerator::getParamsBlock()}.
     *
     * Output is redirected, so no text styles here.
     *
     * @see HelpGenerator::getFullHelp()
     * @see HelpGenerator::getParamsBlock()
     * @see HelpGenerator::getDescriptionBlock()
     * @see ParameterAbstract::allowedValues()
     * @see ParameterAbstract::areAllowedValuesHiddenFromHelp()
     * @see VariableBuilderAbstract::allowedValues()
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
          lots-of-params.php argument --opt-required=pink --opt-default=cool --flag2 A --opt-list=250 --opt-list=500 -- arg_elem_1 arg_elem_2 --opt=not_option_but_arg_elem3

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
     * @see HelpGenerator::getFullHelp()
     */
    public function testSubcommandScriptHelpFirstLevel(): void {
        assertSame(
            <<<HELP

        USAGE

          deep-nesting.php [<subcommand-name>]

          Very deep call:
          deep-nesting.php test11 --name-l2=superName test23 --name-l3=nameLevelThree test32

        OPTIONS

          --help   Show full help page.

        ARGUMENTS

          <subcommand-name>   LEVEL 1
                              Allowed values: 4 subcommands available (see 'list' subcommand output)
                              Subcommand help: <subcommand-name> --help
                                       ... or: help <subcommand-name>
                              Default: list


        HELP,
            static::assertNoErrorsOutput(__DIR__ . '/scripts/deep-nesting.php', '--help')->getStdOut(),
        );
    }

    /**
     * Help for a specific subcommand.
     *
     * @see HelpGenerator::getFullHelp()
     * @see HelpGenerator::getUsagesBlock()
     */
    public function testSubcommandScriptHelpDeepInSubcommand(): void {
        assertSame(
            <<<HELP

        USAGE

          deep-nesting.php test11 [--name-l2=…] test23 [--name-l3=…] [<subcommand-name-l3>]
          deep-nesting.php test11 test23 test31

          Very deep call:
          deep-nesting.php test11 test23 --name-l3=nameLevelThree test32

        OPTIONS

          --help        Show full help page.

          --name-l3=…

        ARGUMENTS

          <subcommand-name-l3>   LEVEL 3
                                 Allowed values: 4 subcommands available (see 'list' subcommand output)
                                 Subcommand help: <subcommand-name-l3> --help
                                          ... or: help <subcommand-name-l3>
                                 Default: list


        HELP,
            static::assertNoErrorsOutput(__DIR__ . '/scripts/deep-nesting.php', 'test11 test23 --help')->getStdOut(),
        );
    }

    /**
     * Tests short descriptions output. The short descriptions are rendered when listing available subcommands.
     *
     * @see HelpGenerator::getScriptShortDescription()
     */
    public function testSubcommandHelpShortDescription(): void {
        assertSame(
            <<<HELP
             Built-in subcommands:
                help                          Outputs a help page for a specified subcommand.
                list                          Shows available subcommands.
            
             --
                long-string                   Here is a sort of... short description.
                long-string-short-sentence    Too short to stop here. So the description continues for some more
                multiline                     Short description on the first line.
                unbreakable-long-line         Thatisareallylonglinebutthereisnowaytobreakitcorrectlysothelinewillbec

            HELP,
            static::assertNoErrorsOutput(
                __DIR__ . '/scripts/subcommands-long-description.php',
                Config::PARAMETER_NAME_LIST,
            )->getStdOut(),
        );

        // ... And let's check the full descriptions to show the difference between short and long versions.

        assertStringStartsWith(
            <<<HELP

              Short description on the first line.
              The rest of long description is omitted while shown beside subcommand possible values.
            HELP
            ,
            static::assertNoErrorsOutput(
                __DIR__ . '/scripts/subcommands-long-description.php',
                'multiline --' . Config::OPTION_NAME_HELP,
            )
                ->getStdOut(),
        );
        assertStringStartsWith(
            <<<HELP

              Here is a sort of... short description. The long description continues on the same line and this line is too long, but it is still not enough so...
              Here is another line :)
            HELP
            ,
            static::assertNoErrorsOutput(
                __DIR__ . '/scripts/subcommands-long-description.php',
                'long-string --' . Config::OPTION_NAME_HELP,
            )
                ->getStdOut(),
        );
        assertStringStartsWith(
            <<<HELP

              Too short to stop here. So the description continues for some more words before the limit is reached.
            HELP
            ,
            static::assertNoErrorsOutput(
                __DIR__ . '/scripts/subcommands-long-description.php',
                'long-string-short-sentence --' . Config::OPTION_NAME_HELP,
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
                'unbreakable-long-line --' . Config::OPTION_NAME_HELP,
            )
                ->getStdOut(),
        );

        // There is a predefined short description for the 'list' command.
        assertStringStartsWith(
            <<<HELP

              Shows the sorted list of available subcommands with their short descriptions.
            HELP
            ,
            static::assertNoErrorsOutput(
                __DIR__ . '/scripts/subcommands-long-description.php',
                Config::PARAMETER_NAME_LIST . ' --' . Config::OPTION_NAME_HELP,
            )
                ->getStdOut(),
        );
    }

    /**
     * Tests that a short description is shown in the full help page, if no full description is set.
     *
     * Also tests that the short description is not processed by a shortener function and always outputted as is.
     *
     * @see HelpGenerator::getDescriptionBlock()
     * @see HelpGenerator::getScriptShortDescription()
     */
    public function testShortDescriptionReplacesFullInHelp(): void {
        assertSame(
            <<<HELP
             Built-in subcommands:
                help         Outputs a help page for a specified subcommand.
                list         Shows available subcommands.

             --
                multiline    It is a multi-line description. It could be considered long enough to be shorten...
                            But due to the fact that this description is set as the "short description",
                            no shortage mechanism is applied.

            HELP,
            static::assertNoErrorsOutput(__DIR__ . '/scripts/short-only-description.php', '')->getStdOut(),
        );

        assertStringStartsWith(
            <<<HELP

              It is a multi-line description. It could be considered long enough to be shorten...
              But due to the fact that this description is set as the "short description",
              no shortage mechanism is applied.
            HELP
            ,
            static::assertNoErrorsOutput(
                __DIR__ . '/scripts/short-only-description.php',
                'multiline --' . Config::OPTION_NAME_HELP,
            )
                ->getStdOut(),
        );
    }
}
