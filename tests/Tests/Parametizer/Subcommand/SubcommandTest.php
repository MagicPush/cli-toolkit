<?php declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\Subcommand;

use MagicPush\CliToolkit\Parametizer\CliRequest\CliRequestProcessor;
use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Parametizer\Config\HelpGenerator;
use MagicPush\CliToolkit\Parametizer\Parametizer;
use MagicPush\CliToolkit\Tests\Tests\TestCaseAbstract;
use PHPUnit\Framework\Attributes\DataProvider;

use function PHPUnit\Framework\assertSame;

/**
 * Tests for subcommands.
 */
class SubcommandTest extends TestCaseAbstract {
    #[DataProvider('provideConfigSubcommandOks')]
    /**
     * Successful execution scenarios for subcommands.
     *
     * @covers Config::commitSubcommandSwitch()
     * @covers Config::registerArgument()
     */
    public function testConfigSubcommandOks(string $script, string $parametersString): void {
        $result = static::assertNoErrorsOutput($script, $parametersString);

        // Success = no output.
        assertSame('', $result->getStdOut());
    }

    /**
     * @return array[]
     */
    public static function provideConfigSubcommandOks(): array {
        return [
            'simple' => [
                'script'           => __DIR__ . '/scripts/simple.php',
                'parametersString' => 'test1',
            ],
            'deep-nesting' => [
                'script'           => __DIR__ . '/scripts/deep-nesting.php',
                'parametersString' => 'test11 --name-l2=supername test23 test31',
            ],
        ];
    }

    /**
     * Tests that options are subcommand (level) dependent
     * and have to be specified in the right order - before a subcommand.
     *
     * @covers Config::commitSubcommandSwitch()
     * @covers CliRequestProcessor::load()
     */
    public function testOptionStickToLevel(): void {
        $script = __DIR__ . '/scripts/deep-nesting.php';

        // Options should be specified within the call level.
        static::assertNoErrorsOutput($script, 'test11 --name-l2=supername test21');

        // You can not specify option from a higher level after a subcommand of a deeper level has been specified.
        static::assertParseErrorOutput(
            $script,
            "Unknown option '--name-l2'",
            'test11 test23 --name-l2=supername',
        );

        // Vice versa: you can not specify some deeper-level option before you specify a corresponding subcommand.
        static::assertParseErrorOutput(
            $script,
            "Unknown option '--name-l3'",
            'test11 --name-l3=supername test23 test31',
        );
    }

    #[DataProvider('provideConfigSubcommandErrors')]
    /**
     * Error execution scenarios for subcommands.
     *
     * @covers Config::commitSubcommandSwitch()
     * @covers Config::registerArgument()
     */
    public function testConfigSubcommandErrors(string $script, string $errorOutput): void {
        static::assertConfigExceptionOutput($script, $errorOutput);
    }

    /**
     * @return array[]
     */
    public static function provideConfigSubcommandErrors(): array {
        return [
            'double-commit' => [
                'script'      => __DIR__ . '/' . 'scripts/error-double-commit.php',
                'errorOutput' => "'switchme' >>> Config error: the subcommand switch was commited already.",
            ],
            'duplicate-value' => [
                'script'      => __DIR__ . '/' . 'scripts/error-duplicate-value.php',
                'errorOutput' => "'switchme' subcommand >>> Config error: duplicate value 'test1'.",
            ],
            'only-one-subcommand' => [
                'script'      => __DIR__ . '/' . 'scripts/error-only-one-subcommand.php',
                'errorOutput' => "'switchme' >>> Config error: you must specify at least 2 subcommand configs.",
            ],
            'argument-after-subcommand-switch' => [
                'script'      => __DIR__ . '/' . 'scripts/error-argument-after-subcommand-switch.php',
                'errorOutput' => "'forbidden' >>> Config error: extra arguments are not allowed on the same level AFTER"
                    . " a subcommand switch ('switchme') is registered;"
                    . " you should add arguments BEFORE 'switchme' or to subcommands.",
            ],
            'subcommand-switch-forgotten' => [
                'script'      => __DIR__ . '/' . 'scripts/error-subcommand-switch-forgotten.php',
                'errorOutput' => "subcommand value 'test1' >>> Config error: a subcommand switch must be specified first.",
            ],

            // Here we test the recursive handling of config branches.
            // Any kind of exception is ok here.
            'switch-final-commit-forgotten-in-subcommand' => [
                'script'      => __DIR__ . '/' . 'scripts/error-final-commit-forgotten-in-subcommand.php',
                'errorOutput' => "'switchme-l3' >>> Config error: you must specify at least 2 subcommand configs.",
            ],
        ];
    }

    #[DataProvider('provideParseErrorsInSubcommandsWithHelp')]
    /**
     * Tests if a script help parts are printed for all missing required options (current subcommand level and higher)
     * and all missing required arguments (current subcommand level only).
     *
     * @param string[] $helpSubstrings
     * @covers HelpGenerator::getUsageForParseErrorException()
     * @covers Parametizer::run()
     * @covers CliRequestProcessor::registerArgument()
     * @covers CliRequestProcessor::registerOption()
     * @covers CliRequestProcessor::setRequestParam()
     * @covers CliRequestProcessor::validate()
     */
    public function testParseErrorsInSubcommandsWithHelp(
        string $script,
        string $parameters,
        string $errorOutput,
        array $helpSubstrings,
    ): void {
        static::assertParseErrorOutputWithHelp($script, $errorOutput, $parameters, $helpSubstrings);
    }

    /**
     * @return array[]
     */
    public static function provideParseErrorsInSubcommandsWithHelp(): array {
        return [
            'required-subcommand-level-argument-and-both-levels-options' => [
                'script'           => __DIR__ . '/' . 'scripts/required-options-different-levels.php',
                'parameters'       => 'test11',
                'errorOutput'      => "Need more parameters",
                'helpSubstrings'   => [
                    '--required=…        Required option',
                    '--required-l2=…     Subcommand required option',
                    '<required-arg-l2>   Subcommand required argument',
                ],
            ],

            'required-subcommand-and-main-levels-options' => [
                'script'           => __DIR__ . '/' . 'scripts/required-options-different-levels.php',
                'parameters'       => 'test11 argValue',
                'errorOutput'      => "Need values for --required, --required-l2",
                'helpSubstrings'   => [
                    '--required=…      Required option',
                    '--required-l2=…   Subcommand required option',
                ],
            ],

            'required-subcommand-level-argument-and-main-level-option' => [
                'script'           => __DIR__ . '/' . 'scripts/required-options-different-levels.php',
                'parameters'       => 'test11 --required-l2=value',
                'errorOutput'      => "Need more parameters",
                'helpSubstrings'   => [
                    '--required=…        Required option',
                    '<required-arg-l2>   Subcommand required argument',
                ],
            ],

            'required-main-level-option' => [
                'script'           => __DIR__ . '/' . 'scripts/required-options-different-levels.php',
                'parameters'       => 'test11 --required-l2=value argValue',
                'errorOutput'      => "Need a value for --required",
                'helpSubstrings'   => [
                    '--required=…   Required option',
                ],
            ],

            'required-subcommand-level-option' => [
                'script'           => __DIR__ . '/' . 'scripts/required-options-different-levels.php',
                'parameters'       => '--required=value test11 argValue',
                'errorOutput'      => "Need a value for --required-l2",
                'helpSubstrings'   => [
                    '--required-l2=…   Subcommand required option',
                ],
            ],
        ];
    }

    /**
     * Test adding default config settings to branches of subcommands.
     *
     * @covers Config::addDefaultOptions()
     */
    public function testAddingDefaultOptions(): void {
        $result = static::assertNoErrorsOutput(
            __DIR__ . '/scripts/deep-nesting.php',
            '--print-option-names',
        );

        assertSame(
            [
                0 => 'print-option-names',

                // Top level default settings
                1 => 'parametizer-internal-autocomplete-generate',
                2 => 'parametizer-internal-autocomplete-execute',
                3 => 'help',

                'BRANCHES' => [
                    'test11' => [
                        0 => 'name-l2',

                        1 => 'help',

                        'BRANCHES' => [
                            'test21' => [
                                0 => 'help',
                            ],
                            'test22' => [
                                0 => 'help',
                            ],
                            'test23' => [
                                0 => 'name-l3',

                                1 => 'help',

                                'BRANCHES' => [
                                    'test31' => [
                                        0 => 'help',
                                    ],
                                    'test32' => [
                                        0 => 'help',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'test12' => [
                        0 => 'help',
                    ],
                ],
            ],
            json_decode($result->getStdOut(), true),
        );
    }
}
