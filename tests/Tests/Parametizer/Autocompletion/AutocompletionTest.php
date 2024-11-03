<?php declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\Autocompletion;

use MagicPush\CliToolkit\Parametizer\Config\Completion\Completion;
use MagicPush\CliToolkit\Tests\Tests\TestCaseAbstract;
use PHPUnit\Framework\Attributes\DataProvider;

use function PHPUnit\Framework\assertSame;

class AutocompletionTest extends TestCaseAbstract {
    #[DataProvider('provideAutocompleteExecution')]
    /**
     * Tests autocomplete execution.
     *
     * @param string[] $expectedOutputLines
     * @covers Completion::executeAutocomplete()
     */
    public function testAutocompleteExecution(string $parametersString, array $expectedOutputLines): void {
        $completionLine = 'some-command-alias';

        if ($parametersString !== '') {
            $completionLine .= ' ' . $parametersString;
        }

        $result = static::assertNoErrorsOutput(
            __DIR__ . '/scripts/different-params.php',
            sprintf(
                '--parametizer-internal-autocomplete-execute %s %s %s',
                escapeshellarg($completionLine),
                escapeshellarg((string) mb_strlen($completionLine)),
                escapeshellarg(PHP_EOL . " \t\"'><=;|&(:"),
            ),
        );

        assertSame($expectedOutputLines, $result->getStdOutAsArray());
    }

    /**
     * @return array[]
     */
    public static function provideAutocompleteExecution(): array {
        return [
            'argument-values' => [
                'parametersString'    => '',
                'expectedOutputLines' => [
                    'super ',
                    'prefix ',
                    'premium ',
                ],
            ],
            'argument-values-after-double-dash' => [
                'parametersString'    => '-- ',
                'expectedOutputLines' => [
                    'super ',
                    'prefix ',
                    'premium ',
                ],
            ],
            'argument-part-value-few' => [
                'parametersString'    => 'pre',
                'expectedOutputLines' => [
                    'prefix ',
                    'premium ',
                ],
            ],
            'argument-part-value-single' => [
                'parametersString'    => 'prem',
                'expectedOutputLines' => [
                    'premium ',
                ],
            ],

            'argument-allowed-values' => [
                'parametersString'    => 'super a',
                'expectedOutputLines' => ['aaa '],
            ],
            'argument-allowed-values-no-completion' => [
                'parametersString'    => 'super aaa b',
                'expectedOutputLines' => [],
            ],

            'option-names' => [
                'parametersString'    => '--',
                'expectedOutputLines' => [
                    '--opt=',
                    '--any-value=',
                    '--flag ',
                    '--second-flag ',
                    '--help ',
                ],
            ],

            'flag-part-name' => [
                'parametersString'    => '--f',
                'expectedOutputLines' => [
                    '--flag ',
                ],
            ],
            'option-part-name' => [
                'parametersString'    => '--o',
                'expectedOutputLines' => [
                    '--opt=',
                ],
            ],

            'option-values' => [
                'parametersString'    => '--opt=',
                'expectedOutputLines' => [
                    '100 ',
                    '200 ',
                    '1000000 ',
                ],
            ],
            'option-part-value-few' => [
                'parametersString'    => '--opt=1',
                'expectedOutputLines' => [
                    '100 ',
                    '1000000 ',
                ],
            ],
            'option-part-value-single' => [
                'parametersString'    => '--opt=1000',
                'expectedOutputLines' => [
                    '1000000 ',
                ],
            ],

            'option-short-name-values' => [
                'parametersString'    => 'super aaa -o',
                'expectedOutputLines' => [
                    '100 ',
                    '200 ',
                    '1000000 ',
                ],
            ],
            'option-short-name-part-value' => [
                'parametersString'    => 'super aaa -o1',
                'expectedOutputLines' => [
                    '100 ',
                    '1000000 ',
                ],
            ],
            'option-short-name-part-value-spaced' => [
                'parametersString'    => 'super aaa -o 1',
                'expectedOutputLines' => [
                    '100 ',
                    '1000000 ',
                ],
            ],

            // If a double-dash is detected, options are not allowed from this moment,
            // so everything else is treated as argument values.
            // Here '-o1' is treated as 'arg' value (that is invalid, yes, but, firstly, not needing the completion).
            'option-short-name-after-double-dash' => [
                'parametersString'    => 'super aaa -- -o1',
                'expectedOutputLines' => [],
            ],
            // Here '-o' is ignored and '1' is treated as 'arg' value
            // (that is invalid, yes, but, firstly, not needing the completion).
            'option-short-name-after-double-dash-spaced' => [
                'parametersString'    => 'super aaa -- -o 1',
                'expectedOutputLines' => [],
            ],

            // Completion should work without errors even for options without particular allowed values,
            // thus also allowing the OS shell to autocomplete stuff like path.
            // In this case the framework completion will render nothing (and it is OK), but in a real console you would
            // see your OS shell completion lines like './', '../' and hidden directory names (if found on your path).
            'option-short-name-part-path' => [
                'parametersString'    => '-a .',
                'expectedOutputLines' => [],
            ],

            // If an option short name is detected, the second "token" should be treated strictly as the option's value,
            // even if that value looks like an another registered option short name.
            // Because the option '--any-value' itself does not have any particular allowed values,
            // the specified value does not look like a part of some path
            // and is not treated as '--opt' with a set of allowed values,
            // there should be no autocomplete suggestions and no errors as well.
            'option-short-name-value-like-option-short-name-forgotten-space' => [
                'parametersString'    => '-a-o',
                'expectedOutputLines' => [],
            ],
            'option-short-name-value-like-option-short-name-spaced' => [
                'parametersString'    => '-a -o',
                'expectedOutputLines' => [],
            ],

            // Ensuring no unexpected errors or autocompletion suggestions appear when detecting two flags short names.
            'option-short-name-two-flags' => [
                'parametersString'    => '-fs',
                'expectedOutputLines' => [],
            ],
            'option-short-name-two-flags-spaced' => [
                'parametersString'    => '-f -s',
                'expectedOutputLines' => [],
            ],
        ];
    }
}
