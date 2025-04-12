<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\Autocompletion;

use MagicPush\CliToolkit\Parametizer\Config\Builder\VariableBuilderAbstract;
use MagicPush\CliToolkit\Parametizer\Config\Completion\Completion;
use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Parametizer\Config\Parameter\ParameterAbstract;
use MagicPush\CliToolkit\Tests\Tests\TestCaseAbstract;
use PHPUnit\Framework\Attributes\DataProvider;

use function PHPUnit\Framework\assertSame;

class AutocompletionTest extends TestCaseAbstract {
    private function testTemplateAutocomplete(
        string $scriptPath,
        string $parametersString,
        array $expectedOutputLines,
    ): void {
        $completionLine = 'some-command-alias';

        if ($parametersString !== '') {
            $completionLine .= ' ' . $parametersString;
        }

        $result = static::assertNoErrorsOutput(
            $scriptPath,
            sprintf(
                '--' . Config::OPTION_NAME_AUTOCOMPLETE_EXECUTE . ' %s %s %s',
                escapeshellarg($completionLine),
                escapeshellarg((string) mb_strlen($completionLine)),
                escapeshellarg(Completion::COMP_WORDBREAKS),
            ),
        );

        assertSame($expectedOutputLines, $result->getStdOutAsArray());
    }

    #[DataProvider('provideAutocompleteExecution')]
    /**
     * Tests autocomplete execution.
     *
     * @param string[] $expectedOutputLines
     * @see Completion::executeAutocomplete()
     * @see Completion::complete()
     */
    public function testAutocompleteExecution(string $parametersString, array $expectedOutputLines): void {
        $this->testTemplateAutocomplete(
            __DIR__ . '/scripts/different-params.php',
            $parametersString,
            $expectedOutputLines,
        );
    }

    #[DataProvider('provideAutocompleteExecution')]
    /**
     * Tests the same autocomplete execution as in {@see testAutocompleteExecution()},
     * but the tested config is a subcommand.
     *
     * @param string[] $expectedOutputLines
     * @see Completion::executeAutocomplete()
     * @see Completion::complete()
     */
    public function testAutocompleteExecutionSubcommand(string $parametersString, array $expectedOutputLines): void {
        $this->testTemplateAutocomplete(
            __DIR__ . '/scripts/subcommands.php',
            'different-params ' . $parametersString,
            $expectedOutputLines,
        );
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

            'argument-allowed-values-emptied-no-completion' => [
                'parametersString'    => 'super b',
                'expectedOutputLines' => [],
            ],

            'option-names' => [
                'parametersString'    => '--',
                'expectedOutputLines' => [
                    '--opt=',
                    '--any-value=',
                    '--flag ',
                    '--second-flag ',
                    '--' . Config::OPTION_NAME_HELP . ' ',
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
                'parametersString'    => 'super -o',
                'expectedOutputLines' => [
                    '100 ',
                    '200 ',
                    '1000000 ',
                ],
            ],
            'option-short-name-part-value' => [
                'parametersString'    => 'super -o1',
                'expectedOutputLines' => [
                    '100 ',
                    '1000000 ',
                ],
            ],
            'option-short-name-part-value-spaced' => [
                'parametersString'    => 'super -o 1',
                'expectedOutputLines' => [
                    '100 ',
                    '1000000 ',
                ],
            ],

            // If a double-dash is detected, options are not allowed from this moment,
            // so everything else is treated as argument values.
            // Here '-o1' is treated as 'arg' value (that is invalid, yes, but, firstly, not needing the completion).
            'option-short-name-after-double-dash' => [
                'parametersString'    => 'super -- -o1',
                'expectedOutputLines' => [],
            ],
            // Here '-o' and '1' are treated as 'arg' values
            // (that is invalid, yes, but, firstly, not needing the completion).
            'option-short-name-after-double-dash-spaced' => [
                'parametersString'    => 'super -- -o 1',
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

    #[DataProvider('provideSmartAutocomplete')]
    /**
     * Tests "smart" (no duplicate) autocomplete execution.
     *
     * @param string[] $expectedOutputLines
     * @see Completion::executeAutocomplete()
     * @see Completion::complete()
     * @see Completion::completeOptions()
     * @see Completion::completeParamValue()
     * @see ParameterAbstract::allowedValues()
     * @see VariableBuilderAbstract::allowedValues()
     */
    public function testSmartAutocomplete(string $parametersString, array $expectedOutputLines): void {
        $this->testTemplateAutocomplete(
            __DIR__ . '/scripts/smart-autocomplete.php',
            $parametersString,
            $expectedOutputLines,
        );
    }

    #[DataProvider('provideSmartAutocomplete')]
    /**
     * Tests the same "smart" (no duplicate) autocomplete execution as in {@see testSmartAutocomplete()},
     * but the tested config is a subcommand.
     *
     * @param string[] $expectedOutputLines
     * @see Completion::executeAutocomplete()
     * @see Completion::complete()
     * @see Completion::completeOptions()
     * @see Completion::completeParamValue()
     * @see ParameterAbstract::allowedValues()
     * @see VariableBuilderAbstract::allowedValues()
     */
    public function testSmartAutocompleteSubcommand(string $parametersString, array $expectedOutputLines): void {
        $this->testTemplateAutocomplete(
            __DIR__ . '/scripts/subcommands.php',
            'smart-autocomplete ' . $parametersString,
            $expectedOutputLines,
        );
    }

    /**
     * @return array[]
     */
    public static function provideSmartAutocomplete(): array {
        return [
            // Completion suggests only the allowed values that haven't been passed yet:
            'argument-array-complete-not-used-only' => [
                'parametersString'    => 'qwe ',
                'expectedOutputLines' => [
                    'asd ',
                    'zxc ',
                ],
            ],
            // Completion suggests nothing after all allowed values have been specified:
            'argument-array-no-completion-if-all-used' => [
                'parametersString'    => 'qwe asd zxc ',
                'expectedOutputLines' => [],
            ],

            // The flag is not suggested if already specified:
            'flag' => [
                'parametersString'    => '--flag --',
                'expectedOutputLines' => [
                    '--opt=',
                    '--opt-arr=',
                    '--' . Config::OPTION_NAME_HELP . ' ',
                ],
            ],
            // No completion even if a flag is specified by a short name:
            'flag-short' => [
                'parametersString'    => '-f --f',
                'expectedOutputLines' => [],
            ],

            'option-value-completion-if-used' => [
                'parametersString'    => '--opt=200 -o 1',
                'expectedOutputLines' => [],
            ],
            'option-value-completion-if-used-2' => [
                'parametersString'    => '-o200 --opt=',
                'expectedOutputLines' => [],
            ],
            'option-no-same-option-suggestion-if-used' => [
                'parametersString'    => '-o 200 --o',
                'expectedOutputLines' => [
                    '--opt-arr=',
                ],
            ],

            'option-array-complete-only-not-used' => [
                'parametersString'    => '-a 443 -a',
                'expectedOutputLines' => [
                    '80 ',
                ],
            ],
            'option-array-no-completion-if-all-used' => [
                'parametersString'    => '--opt-arr=443 --opt-arr=80 --opt-arr=',
                'expectedOutputLines' => [],
            ],
            'option-array-no-completion-if-all-used-short' => [
                'parametersString'    => '-a443 -a80 -a',
                'expectedOutputLines' => [],
            ],
            'option-array-no-same-option-suggestion-if-all-values-used' => [
                'parametersString'    => '-a 443 -a 80 --o',
                'expectedOutputLines' => [
                    '--opt=',
                ],
            ],
        ];
    }

    #[DataProvider('provideSubcommandSwitch')]
    /**
     * Tests subcommand switch completion.
     *
     * @param string[] $expectedOutputLines
     * @see Completion::executeAutocomplete()
     * @see Completion::complete()
     * @see Completion::completeParamValue()
     */
    public function testSubcommandSwitch(string $parametersString, array $expectedOutputLines): void {
        $this->testTemplateAutocomplete(__DIR__ . '/scripts/subcommands.php', $parametersString, $expectedOutputLines);
    }

    /**
     * @return array[]
     */
    public static function provideSubcommandSwitch(): array {
        return [
            'empty' => [
                'parametersString'    => '',
                'expectedOutputLines' => [
                    Config::PARAMETER_NAME_LIST . ' ',
                    Config::OPTION_NAME_HELP . ' ',
                    'different-params ',
                    'smart-autocomplete ',
                ],
            ],
            'partial' => [
                'parametersString'    => 's',
                'expectedOutputLines' => [
                    'smart-autocomplete '
                ],
            ],
            'after-double-dash' => [
                'parametersString'    => '-- d',
                'expectedOutputLines' => [
                    'different-params '
                ],
            ],
        ];
    }

    #[DataProvider('provideSubcommandSwitchDefaultValueDeep')]
    /**
     * Tests completion affected by default subcommand switch value deeply in configs tree.
     *
     * For now the only possible default subcommand is {@see Config::PARAMETER_NAME_LIST}, it is hardcoded.
     * However if different default values are possible, we may test here any default value.
     *
     * @param string[] $expectedOutputLines
     * @see Completion::executeAutocomplete()
     * @see Completion::complete()
     * @see Completion::completeParamValue()
     */
    public function testSubcommandSwitchDefaultValueDeep(string $parametersString, array $expectedOutputLines): void {
        $this->testTemplateAutocomplete(
            __DIR__ . '/scripts/deep-default-subcommand.php',
            "level-2 {$parametersString}",
            $expectedOutputLines,
        );
    }

    /**
     * @return array[]
     */
    public static function provideSubcommandSwitchDefaultValueDeep(): array {
        return [
            // If no subcommand is specified explicitly, the default subcommand is ignored.
            // So the completion should render available subcommands.
            'default' => [
                'parametersString'    => '',
                'expectedOutputLines' => [
                    'list ',
                    'help ',
                    'level-3 ',
                ],
            ],

            // If we explicitly specify a subcommand, the completion mechanism should suggest possible values
            // for the next subcommand argument (if present).
            // The same should happen if the specified subcommand is identical to the default one.
            'explicit-default' => [
                'parametersString'    => Config::PARAMETER_NAME_LIST . ' ',
                'expectedOutputLines' => [],
            ],
            // Here the subcommand supports an argument with a list of allowed values.
            'subcommand-with-argument' => [
                'parametersString'    => 'level-3 ',
                'expectedOutputLines' => [
                    'asd ',
                    'zxc ',
                    'qwe ',
                ],
            ],
        ];
    }
}
