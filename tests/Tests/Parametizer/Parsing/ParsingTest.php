<?php declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\Parsing;

use MagicPush\CliToolkit\Tests\Tests\TestCaseAbstract;
use PHPUnit\Framework\Attributes\DataProvider;

use function PHPUnit\Framework\assertSame;

class ParsingTest extends TestCaseAbstract {
    #[DataProvider('provideParsingSuccess')]
    /**
     * Test successful values parsing for different parameters.
     *
     * @param mixed[] $expectedValues
     * @covers Parametizer::run()
     * @covers Parser::read()
     * @covers CliRequestProcessor::registerArgument()
     * @covers CliRequestProcessor::registerOption()
     * @covers CliRequestProcessor::setRequestParam()
     * @covers CliRequestProcessor::validate()
     */
    public function testParsingSuccess(string $parametersString, array $expectedValues): void {
        $result = static::assertNoErrorsOutput(__DIR__ . '/scripts/lots-of-params.php', $parametersString);

        $defaultValues  = [
            'opt-required'   => '___no-matter-because-required',
            'opt-default'    => 'opt_default_value',
            'opt-list'       => [],
            'opt-no-default' => null,
            'flag1'          => false,
            'flag2'          => false,
            'flag3'          => false,
            'arg-required'   => '___no-matter-because-required',
            'arg-optional'   => 'B',
            'arg-list'       => [],
        ];
        $expectedValues = array_merge($defaultValues, $expectedValues);

        $resultJsonString = $result->getStdOut();
        $actualValues     = json_decode($resultJsonString, true);

        assertSame($expectedValues, $actualValues);
    }

    /**
     * @return array[]
     */
    public static function provideParsingSuccess(): array {
        return [
            'required-only' => [
                'parametersString' => '--opt-required=pink arg-req-value',
                'expectedValues'   => [
                    'opt-required' => 'pink',
                    'arg-required' => 'arg-req-value',
                ],
            ],
            'argument-optional' => [
                'parametersString' => '--opt-required=pink arg-req-value A',
                'expectedValues'   => [
                    'opt-required' => 'pink',
                    'arg-required' => 'arg-req-value',
                    'arg-optional' => 'A',
                ],
            ],
            // Unquoted spaces are ignored.
            'argument-array' => [
                'parametersString' => '--opt-required=pink arg-req-value A asd       " " 123',
                'expectedValues'   => [
                    'opt-required' => 'pink',
                    'arg-required' => 'arg-req-value',
                    'arg-optional' => 'A',
                    'arg-list'     => ['asd', ' ', '123'],
                ],
            ],

            // Double dash special parameter is treated as the divider between possible options and arguments:
            // after '--' every "word" is considered as an argument value.
            'nothing-happens-with-just-double-dash' => [
                'parametersString' => '--opt-required=pink arg-req-value --',
                'expectedValues'   => [
                    'opt-required' => 'pink',
                    'arg-required' => 'arg-req-value',
                ],
            ],
            'argument-after-double-dash' => [
                'parametersString' => '--opt-required=pink arg-req-value -- A',
                'expectedValues'   => [
                    'opt-required' => 'pink',
                    'arg-required' => 'arg-req-value',
                    'arg-optional' => 'A',
                ],
            ],
            // Note that '--' and '--opt-required=black' are treated as some arguments values.
            'options-and-stuff-after-double-dash' => [
                'parametersString' => '--opt-required=pink -- --opt-required=black A asd " " 123 --',
                'expectedValues'   => [
                    'opt-required' => 'pink',
                    'arg-required' => '--opt-required=black',
                    'arg-optional' => 'A',
                    'arg-list'     => ['asd', ' ', '123', '--'],
                ],
            ],

            'flag' => [
                'parametersString' => '--flag1 --opt-required=pink arg-req-value',
                'expectedValues'   => [
                    'opt-required' => 'pink',
                    'arg-required' => 'arg-req-value',
                    'flag1'        => true,
                ],
            ],
            'flag-short-name' => [
                'parametersString' => '-f --opt-required=pink arg-req-value',
                'expectedValues'   => [
                    'opt-required' => 'pink',
                    'arg-required' => 'arg-req-value',
                    'flag1'        => true,
                ],
            ],
            'flag-after-argument' => [
                'parametersString' => '--opt-required=pink arg-req-value --flag1',
                'expectedValues'   => [
                    'opt-required' => 'pink',
                    'arg-required' => 'arg-req-value',
                    'flag1'        => true,
                ],
            ],
            'flag-between-arguments' => [
                'parametersString' => '--opt-required=pink arg-req-value --flag1 C',
                'expectedValues'   => [
                    'opt-required' => 'pink',
                    'arg-required' => 'arg-req-value',
                    'flag1'        => true,
                    'arg-optional' => 'C',
                ],
            ],
            'flag-after-array-argument' => [
                'parametersString' => '--opt-required=pink arg-req-value C asd 123 --flag1',
                'expectedValues'   => [
                    'opt-required' => 'pink',
                    'arg-required' => 'arg-req-value',
                    'flag1'        => true,
                    'arg-optional' => 'C',
                    'arg-list'     => ['asd', '123'],
                ],
            ],
            'flag-between-array-argument-values' => [
                'parametersString' => '--opt-required=pink arg-req-value C asd --flag1 123',
                'expectedValues'   => [
                    'opt-required' => 'pink',
                    'arg-required' => 'arg-req-value',
                    'flag1'        => true,
                    'arg-optional' => 'C',
                    'arg-list'     => ['asd', '123'],
                ],
            ],

            'array-option-single' => [
                'parametersString' => '--opt-list=150 --opt-required=pink arg-req-value',
                'expectedValues'   => [
                    'opt-required' => 'pink',
                    'arg-required' => 'arg-req-value',
                    'opt-list'     => ['150'],
                ],
            ],
            'array-option-two-by-full-and-short' => [
                'parametersString' => '--opt-list=150 -l 200 --opt-required=pink arg-req-value',
                'expectedValues'   => [
                    'opt-required' => 'pink',
                    'arg-required' => 'arg-req-value',
                    'opt-list'     => ['150', '200'],
                ],
            ],
            'different-short-options' => [
                'parametersString' => '-l150 -o 200 --opt-required=pink arg-req-value',
                'expectedValues'   => [
                    'opt-required' => 'pink',
                    'arg-required' => 'arg-req-value',
                    'opt-list'     => ['150'],
                    'opt-default'  => '200',
                ],
            ],
            'option-after-argument' => [
                'parametersString' => '--opt-required=pink arg-req-value --opt-default=cool',
                'expectedValues'   => [
                    'opt-required' => 'pink',
                    'arg-required' => 'arg-req-value',
                    'opt-default'  => 'cool',
                ],
            ],
            'option-between-arguments' => [
                'parametersString' => '--opt-required=pink arg-req-value --opt-default=cool C',
                'expectedValues'   => [
                    'opt-required' => 'pink',
                    'arg-required' => 'arg-req-value',
                    'opt-default'  => 'cool',
                    'arg-optional' => 'C',
                ],
            ],
            'option-after-array-argument' => [
                'parametersString' => '--opt-required=pink arg-req-value C asd 123 --opt-default=cool',
                'expectedValues'   => [
                    'opt-required' => 'pink',
                    'arg-required' => 'arg-req-value',
                    'opt-default'  => 'cool',
                    'arg-optional' => 'C',
                    'arg-list'     => ['asd', '123'],
                ],
            ],
            'option-between-array-argument-values' => [
                'parametersString' => '--opt-required=pink arg-req-value C asd --opt-default=cool 123',
                'expectedValues'   => [
                    'opt-required' => 'pink',
                    'arg-required' => 'arg-req-value',
                    'opt-default'  => 'cool',
                    'arg-optional' => 'C',
                    'arg-list'     => ['asd', '123'],
                ],
            ],
            // With an option short name you may specify a value with or without a space between a name and a value:
            'array-option-mixed-with-array-argument-values' => [
                'parametersString' => '--opt-required=pink arg-req-value C asd -l150 123 -l 200 250',
                'expectedValues'   => [
                    'opt-required' => 'pink',
                    'arg-required' => 'arg-req-value',
                    'arg-optional' => 'C',
                    'arg-list'     => ['asd', '123', '250'],
                    'opt-list'     => ['150', '200'],
                ],
            ],
        ];
    }

    #[DataProvider('provideParsingManyFlagsSuccess')]
    /**
     * Successful execution of specific test scripts.
     *
     * @param mixed[] $expectedValues
     * @covers Parametizer::run()
     * @covers Parser::read()
     */
    public function testParsingManyFlagsSuccess(string $script, string $parametersString, array $expectedValues): void {
        $result = static::assertNoErrorsOutput($script, $parametersString);

        $resultJsonString = $result->getStdOut();
        $actualValues     = json_decode($resultJsonString, true);
        assertSame($expectedValues, array_filter($actualValues));
    }

    /**
     * @return array[]
     */
    public static function provideParsingManyFlagsSuccess(): array {
        return [
            /** Three short name flags at once {@see Parser::read()} */
            'many-short-flags-1' => [
                'script'           => __DIR__ . '/scripts/many-flags.php',
                'parametersString' => '-xyz',
                'expectedValues'   => ['flag-x' => true, 'flag-y' => true, 'flag-z' => true],
            ],
            'many-short-flags-2' => [
                'script'           => __DIR__ . '/scripts/many-flags.php',
                'parametersString' => '-xy -z',
                'expectedValues'   => ['flag-x' => true, 'flag-y' => true, 'flag-z' => true],
            ],

            /** Flags and options with short names {@see Parser::read()} */
            'option-with-short-name-and-value-1' => [
                'script'           => __DIR__ . '/scripts/many-flags.php',
                'parametersString' => '-xy -oz',
                'expectedValues'   => ['flag-x' => true, 'flag-y' => true, 'option' => 'z'],
            ],
            'option-with-short-name-and-value-2' => [
                'script'           => __DIR__ . '/scripts/many-flags.php',
                'parametersString' => '-xyoz',
                'expectedValues'   => ['flag-x' => true, 'flag-y' => true, 'option' => 'z'],
            ],
            'option-with-short-name-and-value-3' => [
                'script'           => __DIR__ . '/scripts/many-flags.php',
                'parametersString' => '-xozy',
                'expectedValues'   => ['flag-x' => true, 'option' => 'zy'],
            ],
        ];
    }

    #[DataProvider('provideParseErrorsWithHelp')]
    /**
     * Tests parameters parse errors.
     *
     * Also tests if:
     *  1) a script help parts are printed for all missing required parameters and parameters with invalid values;
     *  2) the `--help` parameter is always printed (so a user could know how to see a full help page).
     *
     * @param string[] $helpSubstrings
     * @covers HelpGenerator::getUsageForParseErrorException()
     * @covers Parametizer::run()
     * @covers CliRequestProcessor::registerArgument()
     * @covers CliRequestProcessor::registerOption()
     * @covers CliRequestProcessor::setRequestParam()
     * @covers CliRequestProcessor::validate()
     */
    public function testParseErrorsWithHelp(
        string $script,
        string $parametersString,
        string $errorOutput,
        array $helpSubstrings,
    ): void {
        static::assertParseErrorOutputWithHelp($script, $errorOutput, $parametersString, $helpSubstrings);
    }

    /**
     * @return array[]
     */
    public static function provideParseErrorsWithHelp(): array {
        return [
            'required-params-missed' => [
                'script'           => __DIR__ . '/scripts/lots-of-params.php',
                'parametersString' => '',
                'errorOutput'      => 'Need more parameters',
                'helpSubstrings'   => [
                    '--opt-required=…   Required option',
                    '<arg-required>     Required argument',
                ],
            ],
            'required-option-missed' => [
                'script'           => __DIR__ . '/scripts/lots-of-params.php',
                'parametersString' => 'arg',
                'errorOutput'      => 'Need a value for --opt-required',
                'helpSubstrings'   => [
                    '--opt-required=…   Required option',
                ],
            ],
            'required-argument-missed' => [
                'script'           => __DIR__ . '/scripts/lots-of-params.php',
                'parametersString' => '--opt-required=5',
                'errorOutput'      => 'Need more parameters',
                'helpSubstrings'   => [
                    '<arg-required>   Required argument',
                ],
            ],
            'required-several-options-missed' => [
                'script'           => __DIR__ . '/scripts/several-required-options.php',
                'parametersString' => '',
                'errorOutput'      => 'Need values for --option1 (-f), --option2, --option3 (-t)',
                'helpSubstrings'   => [
                    '-f …, --option1=…   First option',
                    '--option2=…         Second option',
                    '-t …, --option3=…   Third option',
                ],
            ],

            'argument-invalid-value' => [
                'script'           => __DIR__ . '/scripts/lots-of-params.php',
                'parametersString' => '--opt-required=5 arg D',
                'errorOutput'      => "Incorrect value 'D' for argument <arg-optional>",
                'helpSubstrings'   => [
                    '<arg-optional>   Optional argument: pick one from the list',
                ],
            ],

            'option-without-value-1' => [
                'script'           => __DIR__ . '/scripts/lots-of-params.php',
                'parametersString' => '--opt-required arg',
                'errorOutput'      => 'No value for option --opt-required',
                'helpSubstrings'   => [
                    '--opt-required=…   Required option',
                ],
            ],
            'option-without-value-2' => [
                'script'           => __DIR__ . '/scripts/lots-of-params.php',
                'parametersString' => '--opt-required= arg',
                'errorOutput'      => 'No value for option --opt-required',
                'helpSubstrings'   => [
                    '--opt-required=…   Required option',
                ],
            ],

            'option-duplicate-value' => [
                'script'           => __DIR__ . '/scripts/lots-of-params.php',
                'parametersString' => '--opt-required=white arg --opt-required=pink',
                'errorOutput'      => "Duplicate option --opt-required (with value 'pink'); already registered value: 'white'",
                'helpSubstrings'   => [
                    '--opt-required=…   Required option',
                ],
            ],
            'array-option-duplicate-value' => [
                'script'           => __DIR__ . '/scripts/lots-of-params.php',
                'parametersString' => '--opt-required=5 arg -l100 -l800 -l100',
                'errorOutput'      => "Duplicate value '100' for option --opt-list (-l); already registered values: '100', '800'",
                'helpSubstrings'   => [
                    '-l …, --opt-list=…   List of values',
                ],
            ],

            'option-invalid-value' => [
                'script'           => __DIR__ . '/scripts/lots-of-params.php',
                'parametersString' => '--opt-required=blue arg',
                'errorOutput'      => "Incorrect value 'blue' for option --opt-required",
                'helpSubstrings'   => [
                    '--opt-required=…   Required option',
                ],
            ],

            'flag-with-value' => [
                'script'           => __DIR__ . '/scripts/lots-of-params.php',
                'parametersString' => '--opt-required=5 arg --flag1=test',
                'errorOutput'      => 'The flag --flag1 (-f) can not have a value',
                'helpSubstrings'   => [
                    '-f, --flag1   Some flag',
                ],
            ],

            'non-configured-extra-argument' => [
                'script'           => __DIR__ . '/scripts/two-arguments.php',
                'parametersString' => 'asd fgh unknown1 unknown2',
                'errorOutput'      => "Too many arguments, starting with 'unknown1'",
                'helpSubstrings'   => [
                    '<argument-one>   First argument',
                    '<argument-two>   Second argument'
                ],
            ],
            'non-configured-extra-argument-after-double-dash' => [
                'script'           => __DIR__ . '/scripts/two-arguments.php',
                'parametersString' => 'asd -- fgh unknown1 unknown2',
                'errorOutput'      => "Too many arguments, starting with 'unknown1'",
                'helpSubstrings'   => [
                    '<argument-one>   First argument',
                    '<argument-two>   Second argument'
                ],
            ],
            'non-configured-option-name' => [
                'script'           => __DIR__ . '/scripts/two-arguments.php',
                'parametersString' => 'asd fgh --unknown=value',
                'errorOutput'      => "Unknown option '--unknown'",
                'helpSubstrings'   => [],
            ],
            'non-configured-option-short-name' => [
                'script'           => __DIR__ . '/scripts/two-arguments.php',
                'parametersString' => 'asd fgh -u value',
                'errorOutput'      => "Unknown option '-u'",
                'helpSubstrings'   => [],
            ],
        ];
    }
}
