<?php declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\Config;

use MagicPush\CliToolkit\Tests\Tests\TestCaseAbstract;
use PHPUnit\Framework\Attributes\DataProvider;

class ConfigTest extends TestCaseAbstract {
    #[DataProvider('provideConfigLogicExceptions')]
    /**
     * Test various {@see LogicException}s when setting up a config.
     *
     * @covers Config::ensureNoDuplicateName()
     * @covers Config::ensureNoDuplicateShortName()
     * @covers VariableBuilderAbstract::ensureNotAllowedValuesSetWithValidatorOrCompletionSimultaneously()
     * @covers VariableBuilderAbstract::ensureNotRequiredAndHasDefaultSimultaneously()
     */
    public function testConfigLogicExceptions(string $script, string $errorOutput): void {
        static::assertConfigExceptionOutput($script, $errorOutput);
    }

    /**
     * @return array[]
     */
    public static function provideConfigLogicExceptions(): array {
        return [
            'allowed-values-with-completion' => [
                'script'      => __DIR__ . '/' . 'scripts/error-allowed-values-with-completion.php',
                'errorOutput' => "'name' >>> Config error: do not set allowed values and completion simultaneously.",
            ],
            'allowed-values-with-validator' => [
                'script'      => __DIR__ . '/' . 'scripts/error-allowed-values-with-validator.php',
                'errorOutput' => "'name' >>> Config error: do not set allowed values and validation simultaneously.",
            ],

            'argument-duplicate-name' => [
                'script'      => __DIR__ . '/' . 'scripts/error-argument-duplicate-name.php',
                'errorOutput' => "Duplicate argument <name> declaration: argument <name> already exists.",
            ],

            'option-duplicate-name' => [
                'script'      => __DIR__ . '/' . 'scripts/error-option-duplicate-name.php',
                'errorOutput' => "Duplicate option 'name' (--name (-o)) declaration: option '--name (-n)' already exists.",
            ],
            'option-duplicate-short-name' => [
                'script'      => __DIR__ . '/' . 'scripts/error-option-duplicate-short-name.php',
                'errorOutput' => "Duplicate option short name '-n' (--test (-n)) declaration: already used for the option '--name (-n)'.",
            ],

            'argument-and-option-same-names' => [
                'script'      => __DIR__ . '/' . 'scripts/error-argument-and-option-same-names.php',
                'errorOutput' => "Duplicate option 'name' (--name) declaration: argument <name> already exists.",
            ],
            'option-and-argument-same-names' => [
                'script'      => __DIR__ . '/' . 'scripts/error-option-and-argument-same-names.php',
                'errorOutput' => "Duplicate argument <name> declaration: option '--name' already exists.",
            ],

            'argument-after-array-argument' => [
                'script'      => __DIR__ . '/' . 'scripts/error-argument-after-array-argument.php',
                'errorOutput' => "'singlearg' >>> Config error: extra arguments are not allowed after already registered array"
                    . " argument ('multiarg') due to ambiguous parsing. Register 'multiarg' argument as the last one.",
            ],

            'required-argument-with-default-value' => [
                'script'      => __DIR__ . '/' . 'scripts/error-required-argument-with-default-value.php',
                'errorOutput' => "'name' >>> Config error: a parameter can't be required and have a default simultaneously.",
            ],
            'required-option-with-default-value' => [
                'script'      => __DIR__ . '/' . 'scripts/error-required-option-with-default-value.php',
                'errorOutput' => "'name' >>> Config error: a parameter can't be required and have a default simultaneously.",
            ],
        ];
    }

    #[DataProvider('provideNameConfigs')]
    /**
     * Test names and short names for parameters.
     *
     * @covers ParameterAbstract::__construct()
     * @covers Option::__construct()
     * @covers Option::shortName()
     * @covers BuilderAbstract::getValidatedOptionName()
     * @covers BuilderAbstract::getValidatedOptionShortName()
     */
    public function testNameConfigs(string $script, ?string $name, ?string $errorOutput): void {
        $escapedName = null !== $name ? escapeshellarg($name) : '';

        if (null !== $errorOutput) {
            static::assertConfigExceptionOutput($script, $errorOutput, $escapedName);
        } else {
            static::assertNoErrorsOutput($script, $escapedName);
        }
    }

    /**
     * @return array[]
     */
    public static function provideNameConfigs(): array {
        return [
            'argument-name-err-empty' => [
                'script'      => __DIR__ . '/scripts/template-argument-name.php',
                'name'        => '',
                'errorOutput' => "'' >>> Config error: too short param name; must contain at least 2 symbols.",
            ],
            'argument-name-err-length' => [
                'script'      => __DIR__ . '/scripts/template-argument-name.php',
                'name'        => 't',
                'errorOutput' => "'t' >>> Config error: too short param name; must contain at least 2 symbols.",
            ],

            'argument-name-err-first-digit' => [
                'script'      => __DIR__ . '/scripts/template-argument-name.php',
                'name'        => '1c',
                'errorOutput' => "'1c' >>> Config error: invalid characters. Must start with latin (lower);"
                    . ' the rest symbols may be of latin (lower), digit, underscore or hyphen.',
            ],
            'argument-name-err-invalid-symbol' => [
                'script'      => __DIR__ . '/scripts/template-argument-name.php',
                'name'        => 'a的',
                'errorOutput' => "'a的' >>> Config error: invalid characters. Must start with latin (lower);"
                    . ' the rest symbols may be of latin (lower), digit, underscore or hyphen.',
            ],

            'argument-name-ok' => [
                'script'      => __DIR__ . '/scripts/template-argument-name.php',
                'name'        => 'l18n-string_v123--',
                'errorOutput' => null,
            ],


            'option-name-err-empty' => [
                'script'      => __DIR__ . '/scripts/template-option-name.php',
                'name'        => '',
                'errorOutput' => "'' >>> Config error: the option must have prefix '--' (example: '--name').",
            ],
            'option-name-err-prefix-no-hyphens' => [
                'script'      => __DIR__ . '/scripts/template-option-name.php',
                'name'        => 'test',
                'errorOutput' => "'test' >>> Config error: the option must have prefix '--' (example: '--name').",
            ],
            'option-name-err-prefix-short' => [
                'script'      => __DIR__ . '/scripts/template-option-name.php',
                'name'        => '-test',
                'errorOutput' => "'-test' >>> Config error: the option must have prefix '--' (example: '--name').",
            ],
            'option-name-err-prefix-hyphen-suffix' => [
                'script'      => __DIR__ . '/scripts/template-option-name.php',
                'name'        => 'test--',
                'errorOutput' => "'test--' >>> Config error: the option must have prefix '--' (example: '--name').",
            ],

            'option-name-err-prefix-empty' => [
                'script'      => __DIR__ . '/scripts/template-option-name.php',
                'name'        => '--',
                'errorOutput' => "'' >>> Config error: too short param name; must contain at least 2 symbols.",
            ],
            'option-name-err-length' => [
                'script'      => __DIR__ . '/scripts/template-option-name.php',
                'name'        => '--t',
                'errorOutput' => "'t' >>> Config error: too short param name; must contain at least 2 symbols.",
            ],

            'option-name-err-first-digit' => [
                'script'      => __DIR__ . '/scripts/template-option-name.php',
                'name'        => '--1c',
                'errorOutput' => "'1c' >>> Config error: invalid characters. Must start with latin (lower);"
                    . ' the rest symbols may be of latin (lower), digit, underscore or hyphen.',
            ],
            'option-name-err-invalid-symbol' => [
                'script'      => __DIR__ . '/scripts/template-option-name.php',
                'name'        => '--a的',
                'errorOutput' => "'a的' >>> Config error: invalid characters. Must start with latin (lower);"
                    . ' the rest symbols may be of latin (lower), digit, underscore or hyphen.',
            ],

            'option-name-ok' => [
                'script'      => __DIR__ . '/scripts/template-option-name.php',
                'name'        => '--l18n-string_v123--',
                'errorOutput' => null,
            ],

            'option-short-name-err-empty' => [
                'script'      => __DIR__ . '/scripts/template-option-short-name.php',
                'name'        => '',
                'errorOutput' => "'' >>> Config error: the option's short name must have prefix '-' (example: '-n').",
            ],
            'option-short-name-err-prefix-not-hyphen' => [
                'script'      => __DIR__ . '/scripts/template-option-short-name.php',
                'name'        => 't',
                'errorOutput' => "'t' >>> Config error: the option's short name must have prefix '-' (example: '-n').",
            ],
            'option-short-name-err-prefix-hyphen-suffix' => [
                'script'      => __DIR__ . '/scripts/template-option-short-name.php',
                'name'        => 't-',
                'errorOutput' => "'t-' >>> Config error: the option's short name must have prefix '-' (example: '-n').",
            ],

            'option-short-name-err-length-short' => [
                'script'      => __DIR__ . '/scripts/template-option-short-name.php',
                'name'        => '-',
                'errorOutput' => "'' ('name') >>> Config error: the short name must be a single latin character.",
            ],
            'option-short-name-err-length-long' => [
                'script'      => __DIR__ . '/scripts/template-option-short-name.php',
                'name'        => '-te',
                'errorOutput' => "'te' ('name') >>> Config error: the short name must be a single latin character.",
            ],

            'option-short-name-err-invalid' => [
                'script'      => __DIR__ . '/scripts/template-option-short-name.php',
                'name'        => '-Ы',
                'errorOutput' => "'Ы' ('name') >>> Config error: the short name must be a single latin character.",
            ],

            'option-short-name-ok' => [
                'script'      => __DIR__ . '/scripts/template-option-short-name.php',
                'name'        => '-t',
                'errorOutput' => null,
            ],
            'option-short-name-ok-null' => [
                'script'      => __DIR__ . '/scripts/template-option-short-name.php',
                'name'        => null,
                'errorOutput' => null,
            ],

            'subcommand-value-err-length-empty' => [
                'script'      => __DIR__ . '/scripts/template-subcommand-value.php',
                'name'        => '',
                'errorOutput' => "'switchme' subcommand >>> Config error: empty value; must contain at least 1 symbol.",
            ],

            'subcommand-value-err-first-digit' => [
                'script'      => __DIR__ . '/scripts/template-subcommand-value.php',
                'name'        => '1c',
                'errorOutput' => "'switchme' subcommand >>> Config error: invalid characters in value '1c'."
                    . ' Must start with latin (lower); the rest symbols may be of latin (lower), digit, underscore or hyphen.',
            ],
            'subcommand-value-err-invalid-symbol' => [
                'script'      => __DIR__ . '/scripts/template-subcommand-value.php',
                'name'        => 'a的',
                'errorOutput' => "'switchme' subcommand >>> Config error: invalid characters in value 'a的'."
                    . ' Must start with latin (lower); the rest symbols may be of latin (lower), digit, underscore or hyphen.',
            ],

            'subcommand-value-ok' => [
                'script'      => __DIR__ . '/scripts/template-subcommand-value.php',
                'name'        => 'l18n-string_v123--',
                'errorOutput' => null,
            ],
        ];
    }
}
