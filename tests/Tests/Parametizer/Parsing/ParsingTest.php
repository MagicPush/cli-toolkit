<?php

declare(strict_types=1);

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
     * @see Parametizer::run()
     * @see Parser::read()
     * @see CliRequestProcessor::registerArgument()
     * @see CliRequestProcessor::registerOption()
     * @see CliRequestProcessor::setRequestParam()
     * @see CliRequestProcessor::validate()
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
        $actualValues   = json_decode($result->getStdOut(), true);

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
     * @see Parametizer::run()
     * @see Parser::read()
     */
    public function testParsingManyFlagsSuccess(
        string $scriptPath,
        string $parametersString,
        array $expectedValues,
    ): void {
        $result = static::assertNoErrorsOutput($scriptPath, $parametersString);

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
                'scriptPath'       => __DIR__ . '/scripts/many-flags.php',
                'parametersString' => '-xyz',
                'expectedValues'   => ['flag-x' => true, 'flag-y' => true, 'flag-z' => true],
            ],
            'many-short-flags-2' => [
                'scriptPath'       => __DIR__ . '/scripts/many-flags.php',
                'parametersString' => '-xy -z',
                'expectedValues'   => ['flag-x' => true, 'flag-y' => true, 'flag-z' => true],
            ],

            /** Flags and options with short names {@see Parser::read()} */
            'option-with-short-name-and-value-1' => [
                'scriptPath'       => __DIR__ . '/scripts/many-flags.php',
                'parametersString' => '-xy -oz',
                'expectedValues'   => ['flag-x' => true, 'flag-y' => true, 'option' => 'z'],
            ],
            'option-with-short-name-and-value-2' => [
                'scriptPath'       => __DIR__ . '/scripts/many-flags.php',
                'parametersString' => '-xyoz',
                'expectedValues'   => ['flag-x' => true, 'flag-y' => true, 'option' => 'z'],
            ],
            'option-with-short-name-and-value-3' => [
                'scriptPath'       => __DIR__ . '/scripts/many-flags.php',
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
     * @see HelpGenerator::getUsageForParseErrorException()
     * @see Parametizer::run()
     * @see CliRequestProcessor::registerArgument()
     * @see CliRequestProcessor::registerOption()
     * @see CliRequestProcessor::setRequestParam()
     * @see CliRequestProcessor::validate()
     */
    public function testParseErrorsWithHelp(
        string $scriptPath,
        string $parametersString,
        string $expectedErrorOutput,
    ): void {
        static::assertFullErrorOutput($scriptPath, $expectedErrorOutput, $parametersString);
    }

    /**
     * @return array[]
     */
    public static function provideParseErrorsWithHelp(): array {
        return [
            'required-params-missed' => [
                'scriptPath'          => __DIR__ . '/scripts/lots-of-params.php',
                'parametersString'    => '',
                'expectedErrorOutput' => <<<STDERR_OUTPUT
Need more parameters


  --help             Show full help page.

  --opt-required=…   Required option: pick one from the list
  (required)         Allowed values:
                      - black A pile of books
                      - pink  A heap of ponies
                      - white
                      - 5     Give me "five"!

  <arg-required>     Required argument
  (required)

STDERR_OUTPUT,
            ],
            'required-option-missed' => [
                'scriptPath'          => __DIR__ . '/scripts/lots-of-params.php',
                'parametersString'    => 'arg',
                'expectedErrorOutput' => <<<STDERR_OUTPUT
Need a value for --opt-required


  --help             Show full help page.

  --opt-required=…   Required option: pick one from the list
  (required)         Allowed values:
                      - black A pile of books
                      - pink  A heap of ponies
                      - white
                      - 5     Give me "five"!

STDERR_OUTPUT,
            ],
            'required-argument-missed' => [
                'scriptPath'          => __DIR__ . '/scripts/lots-of-params.php',
                'parametersString'    => '--opt-required=5',
                'expectedErrorOutput' => <<<STDERR_OUTPUT
Need more parameters


  --help           Show full help page.

  <arg-required>   Required argument
  (required)

STDERR_OUTPUT,
            ],
            'required-several-options-missed' => [
                'scriptPath'          => __DIR__ . '/scripts/several-required-options.php',
                'parametersString'    => '',
                'expectedErrorOutput' => <<<STDERR_OUTPUT
Need values for --option1 (-f), --option2, --option3 (-t)


  --help              Show full help page.

  -f …, --option1=…   First option
  (required)

  --option2=…         Second option
  (required)

  -t …, --option3=…   Third option
  (required)

STDERR_OUTPUT,
            ],

            'argument-invalid-value' => [
                'scriptPath'          => __DIR__ . '/scripts/lots-of-params.php',
                'parametersString'    => '--opt-required=5 arg D',
                'expectedErrorOutput' => <<<STDERR_OUTPUT
Incorrect value 'D' for argument <arg-optional>


  --help           Show full help page.

  <arg-optional>   Optional argument: pick one from the list
                   Allowed values: A, B, C
                   Default: B

STDERR_OUTPUT,
            ],

            'option-without-value-1' => [
                'scriptPath'          => __DIR__ . '/scripts/lots-of-params.php',
                'parametersString'    => '--opt-required arg',
                'expectedErrorOutput' => <<<STDERR_OUTPUT
No value for option --opt-required


  --help             Show full help page.

  --opt-required=…   Required option: pick one from the list
  (required)         Allowed values:
                      - black A pile of books
                      - pink  A heap of ponies
                      - white
                      - 5     Give me "five"!

STDERR_OUTPUT,
            ],
            'option-without-value-2' => [
                'scriptPath'          => __DIR__ . '/scripts/lots-of-params.php',
                'parametersString'    => '--opt-required= arg',
                'expectedErrorOutput' => <<<STDERR_OUTPUT
No value for option --opt-required


  --help             Show full help page.

  --opt-required=…   Required option: pick one from the list
  (required)         Allowed values:
                      - black A pile of books
                      - pink  A heap of ponies
                      - white
                      - 5     Give me "five"!

STDERR_OUTPUT,
            ],

            'option-duplicate-value' => [
                'scriptPath'          => __DIR__ . '/scripts/lots-of-params.php',
                'parametersString'    => '--opt-required=white arg --opt-required=pink',
                'expectedErrorOutput' => <<<STDERR_OUTPUT
Duplicate option --opt-required (with value 'pink'); already registered value: 'white'


  --help             Show full help page.

  --opt-required=…   Required option: pick one from the list
  (required)         Allowed values:
                      - black A pile of books
                      - pink  A heap of ponies
                      - white
                      - 5     Give me "five"!

STDERR_OUTPUT,
            ],
            'array-option-duplicate-value' => [
                'scriptPath'          => __DIR__ . '/scripts/lots-of-params.php',
                'parametersString'    => '--opt-required=5 arg -l100 -l800 -l100',
                'expectedErrorOutput' => <<<STDERR_OUTPUT
Duplicate value '100' for option --opt-list (-l); already registered values: '100', '800'


  --help               Show full help page.

  -l …, --opt-list=…   List of values
                       Allowed values: 100, 150, 200, 250, 300, 350, 400, 450, 500, 550, 600, 650, 700, 750, 800
                       (multiple values allowed)

STDERR_OUTPUT,
            ],

            'option-invalid-value' => [
                'scriptPath'          => __DIR__ . '/scripts/lots-of-params.php',
                'parametersString'    => '--opt-required=blue arg',
                'expectedErrorOutput' => <<<STDERR_OUTPUT
Incorrect value 'blue' for option --opt-required


  --help             Show full help page.

  --opt-required=…   Required option: pick one from the list
  (required)         Allowed values:
                      - black A pile of books
                      - pink  A heap of ponies
                      - white
                      - 5     Give me "five"!

STDERR_OUTPUT,
            ],

            'flag-with-value' => [
                'scriptPath'          => __DIR__ . '/scripts/lots-of-params.php',
                'parametersString'    => '--opt-required=5 arg --flag1=test',
                'expectedErrorOutput' => <<<STDERR_OUTPUT
The flag --flag1 (-f) can not have a value


  --help        Show full help page.

  -f, --flag1   Some flag

STDERR_OUTPUT,
            ],

            'non-configured-extra-argument' => [
                'scriptPath'          => __DIR__ . '/scripts/two-arguments.php',
                'parametersString'    => 'asd fgh unknown1 unknown2',
                'expectedErrorOutput' => <<<STDERR_OUTPUT
Too many arguments, starting with 'unknown1'


  --help           Show full help page.

  <argument-one>   First argument
  (required)

  <argument-two>   Second argument
  (required)

STDERR_OUTPUT,
            ],
            'non-configured-extra-argument-after-double-dash' => [
                'scriptPath'          => __DIR__ . '/scripts/two-arguments.php',
                'parametersString'    => 'asd -- fgh unknown1 unknown2',
                'expectedErrorOutput' => <<<STDERR_OUTPUT
Too many arguments, starting with 'unknown1'


  --help           Show full help page.

  <argument-one>   First argument
  (required)

  <argument-two>   Second argument
  (required)

STDERR_OUTPUT,
            ],
            'non-configured-option-name' => [
                'scriptPath'          => __DIR__ . '/scripts/two-arguments.php',
                'parametersString'    => 'asd fgh --unknown=value',
                'expectedErrorOutput' => <<<STDERR_OUTPUT
Unknown option '--unknown'


  --help   Show full help page.

STDERR_OUTPUT,
            ],
            'non-configured-option-short-name' => [
                'scriptPath'          => __DIR__ . '/scripts/two-arguments.php',
                'parametersString'    => 'asd fgh -u value',
                'expectedErrorOutput' => <<<STDERR_OUTPUT
Unknown option '-u'


  --help   Show full help page.

STDERR_OUTPUT,
            ],
        ];
    }
}
