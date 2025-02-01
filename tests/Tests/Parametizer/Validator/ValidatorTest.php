<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\Validator;

use MagicPush\CliToolkit\Parametizer\CliRequest\CliRequestProcessor;
use MagicPush\CliToolkit\Parametizer\Config\Parameter\ParameterAbstract;
use MagicPush\CliToolkit\Tests\Tests\TestCaseAbstract;
use PHPUnit\Framework\Attributes\DataProvider;

use function PHPUnit\Framework\assertSame;

class ValidatorTest extends TestCaseAbstract {
    /**
     * Tests invalid validator config exception.
     *
     * @see ParameterAbstract::validate()
     */
    public function testValidatorInvalidConfig(): void {
        static::assertConfigExceptionOutput(
            __DIR__ . '/scripts/config-invalid.php',
            "'arg' >>> Config error: invalid validator",
            'no-m@tter-WHAT!-y0u-t4pe-h3r3',
        );
    }

    #[DataProvider('provideValidatorConfigs')]
    /**
     * Tests different validator configs.
     *
     * @see ParameterAbstract::validate()
     */
    public function testValidatorConfigs(
        string $script,
        string $parametersString,
        ?string $errorOutput,
    ): void {
        if (null !== $errorOutput) {
            static::assertParseErrorOutput($script, $errorOutput, $parametersString);
        } else {
            static::assertNoErrorsOutput($script, $parametersString);
        }
    }

    /**
     * @return array[]
     */
    public static function provideValidatorConfigs(): array {
        return [
            'null-as-no-validator' => [
                'script'           => __DIR__ . '/scripts/config-null.php',
                'parametersString' => 'no-m@tter-WHAT!-y0u-t4pe-h3r3',
                'errorOutput'      => null,
            ],

            'pattern-ok' => [
                'script'           => __DIR__ . '/scripts/pattern.php',
                'parametersString' => 'test',
                'errorOutput'      => null,
            ],
            'pattern-err' => [
                'script'           => __DIR__ . '/scripts/pattern.php',
                'parametersString' => 'Test',
                'errorOutput'      => "Incorrect value 'Test' for argument <arg>",
            ],
            'pattern-err-custom-message' => [
                'script'           => __DIR__ . '/scripts/pattern-custom-message.php',
                'parametersString' => 'Test',
                'errorOutput'      => "Incorrect value 'Test' for argument <arg>. Lowercase letters only",
            ],

            'named-function-ok' => [
                'script'           => __DIR__ . '/scripts/named-function.php',
                'parametersString' => '100500',
                'errorOutput'      => null,
            ],
            'named-function-err' => [
                'script'           => __DIR__ . '/scripts/named-function.php',
                'parametersString' => '"100 500"',
                'errorOutput'      => "Incorrect value '100 500' for argument <arg>",
            ],
            'named-function-err-custom-message' => [
                'script'           => __DIR__ . '/scripts/named-function-custom-message.php',
                'parametersString' => '"100 500"',
                'errorOutput'      => "Incorrect value '100 500' for argument <arg>. Digits only",
            ],

            'lambda-function-ok' => [
                'script'           => __DIR__ . '/scripts/lambda-function.php',
                'parametersString' => '42',
                'errorOutput'      => null,
            ],
            'lambda-function-err' => [
                'script'           => __DIR__ . '/scripts/lambda-function.php',
                'parametersString' => '41',
                'errorOutput'      => "Incorrect value '41' for argument <arg>",
            ],
            'lambda-function-err-custom-message' => [
                'script'           => __DIR__ . '/' . 'scripts/lambda-function-custom-message.php',
                'parametersString' => '41',
                'errorOutput'      => "Incorrect value '41' for argument <arg>. Only values that can be divided by 3 without a remainder",
            ],
            'lambda-function-err-exception-priority' => [
                'script'           => __DIR__ . '/' . 'scripts/lambda-function-custom-message.php',
                'parametersString' => 'test',
                'errorOutput'      => "Incorrect value 'test' for argument <arg>. Numeric values only",
            ],

            'option-err' => [
                'script'           => __DIR__ . '/scripts/validator-option.php',
                'parametersString' => '--opt=Test',
                'errorOutput'      => "Incorrect value 'Test' for option --opt (-o)",
            ],

            'allowed-values-ok' => [
                'script'           => __DIR__ . '/scripts/config-allowed-values.php',
                'parametersString' => '--one-of-values=222',
                'errorOutput'      => null,
            ],
            'allowed-values-err' => [
                'script'           => __DIR__ . '/scripts/config-allowed-values.php',
                'parametersString' => '--one-of-values=xxx',
                'errorOutput'      => "Incorrect value 'xxx' for option --one-of-values",
            ],
            'allowed-values-emptied-ok' => [
                'script'           => __DIR__ . '/scripts/config-allowed-values.php',
                'parametersString' => '--one-of-values-emptied=xxx',
                'errorOutput'      => null,
            ],
            'allowed-values-emptied-but-not-validator-ok' => [
                'script'           => __DIR__ . '/scripts/config-allowed-values.php',
                'parametersString' => '--validator-no-particular-allowed-values=xxx',
                'errorOutput'      => null,
            ],
            'allowed-values-emptied-but-not-validator-err' => [
                'script'           => __DIR__ . '/scripts/config-allowed-values.php',
                'parametersString' => '--validator-no-particular-allowed-values=222',
                'errorOutput'      => "Incorrect value '222' for option --validator-no-particular-allowed-values",
            ],
        ];
    }

    /**
     * Tests if validators act as filters and alter parameters' values.
     *
     * @see CliRequestProcessor::validateParam()
     * @see ParameterAbstract::validate()
     */
    public function testValidatorFilterValue(): void {
        $result = static::assertNoErrorsOutput(
            __DIR__ . '/scripts/custom-filter.php',
            'value',
        );
        assertSame('value UPDATED ARGUMENT', $result->getStdOut());

        $result = static::assertNoErrorsOutput(
            __DIR__ . '/scripts/custom-filter.php',
            '--opt-list="value-one" --opt-list="value-two"',
        );
        assertSame('value-one UPDATED ELEMENT; value-two UPDATED ELEMENT', $result->getStdOut());
    }
}
