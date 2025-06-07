<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\Config;

use MagicPush\CliToolkit\Parametizer\Exception\ConfigException;
use MagicPush\CliToolkit\Tests\Tests\TestCaseAbstract;
use PHPUnit\Framework\Attributes\DataProvider;

use function PHPUnit\Framework\assertSame;

class ConfigTest extends TestCaseAbstract {
    #[DataProvider('provideConfigLogicExceptions')]
    /**
     * Tests various {@see ConfigException}s when setting up a config.
     *
     * @see Config::ensureNoDuplicateName()
     * @see Config::ensureNoDuplicateShortName()
     * @see VariableBuilderAbstract::ensureNotAllowedValuesSetWithValidatorOrCompletionSimultaneously()
     * @see VariableBuilderAbstract::ensureNotRequiredAndHasDefaultSimultaneously()
     */
    public function testConfigLogicExceptions(string $scriptPath, string $errorOutput): void {
        static::assertConfigExceptionOutput($scriptPath, $errorOutput);
    }

    /**
     * @return array[]
     */
    public static function provideConfigLogicExceptions(): array {
        return [
            'allowed-values-with-completion' => [
                'scriptPath'  => __DIR__ . '/' . 'scripts/error-allowed-values-with-completion.php',
                'errorOutput' => "'name' >>> Config error: do not set allowed values and completion simultaneously.",
            ],
            'allowed-values-with-validator' => [
                'scriptPath'  => __DIR__ . '/' . 'scripts/error-allowed-values-with-validator.php',
                'errorOutput' => "'name' >>> Config error: do not set allowed values and validation simultaneously.",
            ],

            'argument-duplicate-name' => [
                'scriptPath'  => __DIR__ . '/' . 'scripts/error-argument-duplicate-name.php',
                'errorOutput' => "Duplicate argument <name> declaration: argument <name> already exists.",
            ],

            'option-duplicate-name' => [
                'scriptPath'  => __DIR__ . '/' . 'scripts/error-option-duplicate-name.php',
                'errorOutput' => "Duplicate option 'name' (--name (-o)) declaration: option '--name (-n)' already exists.",
            ],
            'option-duplicate-short-name' => [
                'scriptPath'  => __DIR__ . '/' . 'scripts/error-option-duplicate-short-name.php',
                'errorOutput' => "Duplicate option short name '-n' (--test (-n)) declaration: already used for the option '--name (-n)'.",
            ],

            'argument-and-option-same-names' => [
                'scriptPath'  => __DIR__ . '/' . 'scripts/error-argument-and-option-same-names.php',
                'errorOutput' => "Duplicate option 'name' (--name) declaration: argument <name> already exists.",
            ],
            'option-and-argument-same-names' => [
                'scriptPath'  => __DIR__ . '/' . 'scripts/error-option-and-argument-same-names.php',
                'errorOutput' => "Duplicate argument <name> declaration: option '--name' already exists.",
            ],

            'argument-after-array-argument' => [
                'scriptPath'  => __DIR__ . '/' . 'scripts/error-argument-after-array-argument.php',
                'errorOutput' => "'single-arg' >>> Config error: extra arguments are not allowed after already registered array"
                    . " argument ('multi-arg') due to ambiguous parsing. Register 'multi-arg' argument as the last one.",
            ],

            'required-argument-with-default-value' => [
                'scriptPath'  => __DIR__ . '/' . 'scripts/error-required-argument-with-default-value.php',
                'errorOutput' => "'name' >>> Config error: a parameter can't be required and have a default simultaneously.",
            ],
            'required-option-with-default-value' => [
                'scriptPath'  => __DIR__ . '/' . 'scripts/error-required-option-with-default-value.php',
                'errorOutput' => "'name' >>> Config error: a parameter can't be required and have a default simultaneously.",
            ],
        ];
    }

    #[DataProvider('provideNameConfigs')]
    /**
     * Tests names and short names for parameters.
     *
     * @see ParameterAbstract::__construct()
     * @see Config::newSubcommand()
     * @see Option::shortName()
     * @see BuilderAbstract::getValidatedOptionName()
     * @see BuilderAbstract::getValidatedOptionShortName()
     */
    public function testNameConfigs(string $scriptPath, ?string $name, ?string $errorOutput): void {
        $escapedName = null !== $name ? escapeshellarg($name) : '';

        if (null !== $errorOutput) {
            static::assertConfigExceptionOutput($scriptPath, $errorOutput, $escapedName);
        } else {
            static::assertNoErrorsOutput($scriptPath, $escapedName);
        }
    }

    /**
     * @return array[]
     */
    public static function provideNameConfigs(): array {
        return [
            'argument-name-err-empty' => [
                'scriptPath'  => __DIR__ . '/scripts/template-argument-name.php',
                'name'        => '',
                'errorOutput' => "'' >>> Config error: too short param name; must contain at least 2 symbols.",
            ],
            'argument-name-err-length' => [
                'scriptPath'  => __DIR__ . '/scripts/template-argument-name.php',
                'name'        => 't',
                'errorOutput' => "'t' >>> Config error: too short param name; must contain at least 2 symbols.",
            ],

            'argument-name-err-first-digit' => [
                'scriptPath'  => __DIR__ . '/scripts/template-argument-name.php',
                'name'        => '1c',
                'errorOutput' => "'1c' >>> Config error: invalid characters. Must start with latin (lower);"
                    . ' the rest symbols may be of latin (lower), digit, underscore or hyphen.',
            ],
            'argument-name-err-invalid-symbol' => [
                'scriptPath'  => __DIR__ . '/scripts/template-argument-name.php',
                'name'        => 'a的',
                'errorOutput' => "'a的' >>> Config error: invalid characters. Must start with latin (lower);"
                    . ' the rest symbols may be of latin (lower), digit, underscore or hyphen.',
            ],

            'argument-name-ok' => [
                'scriptPath'  => __DIR__ . '/scripts/template-argument-name.php',
                'name'        => 'l18n-string_v123--',
                'errorOutput' => null,
            ],


            'option-name-err-empty' => [
                'scriptPath'  => __DIR__ . '/scripts/template-option-name.php',
                'name'        => '',
                'errorOutput' => "'' >>> Config error: the option must have prefix '--' (example: '--name').",
            ],
            'option-name-err-prefix-no-hyphens' => [
                'scriptPath'  => __DIR__ . '/scripts/template-option-name.php',
                'name'        => 'test',
                'errorOutput' => "'test' >>> Config error: the option must have prefix '--' (example: '--name').",
            ],
            'option-name-err-prefix-short' => [
                'scriptPath'  => __DIR__ . '/scripts/template-option-name.php',
                'name'        => '-test',
                'errorOutput' => "'-test' >>> Config error: the option must have prefix '--' (example: '--name').",
            ],
            'option-name-err-prefix-hyphen-suffix' => [
                'scriptPath'  => __DIR__ . '/scripts/template-option-name.php',
                'name'        => 'test--',
                'errorOutput' => "'test--' >>> Config error: the option must have prefix '--' (example: '--name').",
            ],

            'option-name-err-prefix-empty' => [
                'scriptPath'  => __DIR__ . '/scripts/template-option-name.php',
                'name'        => '--',
                'errorOutput' => "'' >>> Config error: too short param name; must contain at least 2 symbols.",
            ],
            'option-name-err-length' => [
                'scriptPath'  => __DIR__ . '/scripts/template-option-name.php',
                'name'        => '--t',
                'errorOutput' => "'t' >>> Config error: too short param name; must contain at least 2 symbols.",
            ],

            'option-name-err-first-digit' => [
                'scriptPath'  => __DIR__ . '/scripts/template-option-name.php',
                'name'        => '--1c',
                'errorOutput' => "'1c' >>> Config error: invalid characters. Must start with latin (lower);"
                    . ' the rest symbols may be of latin (lower), digit, underscore or hyphen.',
            ],
            'option-name-err-invalid-symbol' => [
                'scriptPath'  => __DIR__ . '/scripts/template-option-name.php',
                'name'        => '--a的',
                'errorOutput' => "'a的' >>> Config error: invalid characters. Must start with latin (lower);"
                    . ' the rest symbols may be of latin (lower), digit, underscore or hyphen.',
            ],

            'option-name-ok' => [
                'scriptPath'  => __DIR__ . '/scripts/template-option-name.php',
                'name'        => '--l18n-string_v123--',
                'errorOutput' => null,
            ],

            'option-short-name-err-empty' => [
                'scriptPath'  => __DIR__ . '/scripts/template-option-short-name.php',
                'name'        => '',
                'errorOutput' => "'' >>> Config error: the option's short name must have prefix '-' (example: '-n').",
            ],
            'option-short-name-err-prefix-not-hyphen' => [
                'scriptPath'  => __DIR__ . '/scripts/template-option-short-name.php',
                'name'        => 't',
                'errorOutput' => "'t' >>> Config error: the option's short name must have prefix '-' (example: '-n').",
            ],
            'option-short-name-err-prefix-hyphen-suffix' => [
                'scriptPath'  => __DIR__ . '/scripts/template-option-short-name.php',
                'name'        => 't-',
                'errorOutput' => "'t-' >>> Config error: the option's short name must have prefix '-' (example: '-n').",
            ],

            'option-short-name-err-length-short' => [
                'scriptPath'  => __DIR__ . '/scripts/template-option-short-name.php',
                'name'        => '-',
                'errorOutput' => "'' ('name') >>> Config error: the short name must be a single latin character.",
            ],
            'option-short-name-err-length-long' => [
                'scriptPath'  => __DIR__ . '/scripts/template-option-short-name.php',
                'name'        => '-te',
                'errorOutput' => "'te' ('name') >>> Config error: the short name must be a single latin character.",
            ],

            'option-short-name-err-invalid' => [
                'scriptPath'  => __DIR__ . '/scripts/template-option-short-name.php',
                'name'        => '-Ы',
                'errorOutput' => "'Ы' ('name') >>> Config error: the short name must be a single latin character.",
            ],

            'option-short-name-ok' => [
                'scriptPath'  => __DIR__ . '/scripts/template-option-short-name.php',
                'name'        => '-t',
                'errorOutput' => null,
            ],
            'option-short-name-ok-null' => [
                'scriptPath'  => __DIR__ . '/scripts/template-option-short-name.php',
                'name'        => null,
                'errorOutput' => null,
            ],

            'subcommand-value-err-length-empty' => [
                'scriptPath'  => __DIR__ . '/scripts/template-subcommand-value.php',
                'name'        => '',
                'errorOutput' => "'subcommand-name' subcommand >>> Config error: empty value; must contain at least 1 symbol.",
            ],

            'subcommand-value-err-first-digit' => [
                'scriptPath'  => __DIR__ . '/scripts/template-subcommand-value.php',
                'name'        => '1c',
                'errorOutput' => "'subcommand-name' subcommand >>> Config error: invalid characters in value '1c'."
                    . ' Must start with a latin (lower); the rest symbols may be of latin (lower), digit, underscore, colon or hyphen.',
            ],
            'subcommand-value-err-invalid-symbol' => [
                'scriptPath'  => __DIR__ . '/scripts/template-subcommand-value.php',
                'name'        => 'a的',
                'errorOutput' => "'subcommand-name' subcommand >>> Config error: invalid characters in value 'a的'."
                    . ' Must start with a latin (lower); the rest symbols may be of latin (lower), digit, underscore, colon or hyphen.',
            ],

            'subcommand-value-ok' => [
                'scriptPath'  => __DIR__ . '/scripts/template-subcommand-value.php',
                'name'        => 'l18n-string_v123--',
                'errorOutput' => null,
            ],
        ];
    }

    /**
     * Tests that short names are case sensitive.
     *
     * @see Option::shortName()
     */
    public function testOptionShortNameCaseSensitive(): void {
        assertSame(
            '"-F" stands for "--flag-two"',
            static::assertNoErrorsOutput(__DIR__ . '/scripts/option-short-name-case-sensitive.php', '-F')->getStdOut(),
        );

        assertSame(
            '"-f" stands for "--flag-one"',
            static::assertNoErrorsOutput(__DIR__ . '/scripts/option-short-name-case-sensitive.php', '-f')->getStdOut(),
        );
    }
}
