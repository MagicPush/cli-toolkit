<?php declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\CliRequest;

use MagicPush\CliToolkit\Parametizer\CliRequest\CliRequest;
use MagicPush\CliToolkit\Tests\Tests\TestCaseAbstract;

use PHPUnit\Framework\Attributes\DataProvider;

use function PHPUnit\Framework\assertSame;

class CliRequestTest extends TestCaseAbstract {
    #[DataProvider('provideGettingParameterValue')]
    /**
     * Tests different reading of parameter values processed from a request.
     *
     * @covers CliRequest::getParam()
     * @covers CliRequest::getParams()
     */
    public function testGettingParameterValue(string $parameterName, string $parameterValue): void {
        $result = static::assertNoErrorsOutput(__DIR__ . '/scripts/template-parameter-name.php', $parameterName);

        assertSame(
            [
                'parameter_value' => $parameterValue,

                'all_parameter_values' => [
                    'option-parameter'   => 'option_value',
                    'argument-parameter' => 'argument_value',
                ],
            ],
            json_decode($result->getStdOut(), true),
        );
    }

    /**
     * @return array[]
     */
    public static function provideGettingParameterValue(): array {
        return [
            'parameter-option' => [
                'parameterName'  => 'option-parameter',
                'parameterValue' => 'option_value',
            ],
            'parameter-argument' => [
                'parameterName'  => 'argument-parameter',
                'parameterValue' => 'argument_value',
            ],
        ];
    }

    /**
     * Tests the exception if an unknown parameter is addressed.
     *
     * @covers CliRequest::getParam()
     */
    public function testGettingParameterValueByInvalidName(): void {
        static::assertLogicExceptionOutput(
            __DIR__ . '/scripts/template-parameter-name.php',
            "Parameter 'cool-parameter' not found in the request."
                . ' The parameters being parsed: option-parameter, argument-parameter',
            'cool-parameter',
        );
    }

    #[DataProvider('provideParameterTypeCast')]
    /**
     * Tests type casting helper methods for parameter values processed from a request.
     *
     * @param mixed[] $expectedValues
     * @covers CliRequest::getParam()
     * @covers CliRequest::getParamAsInt()
     * @covers CliRequest::getParamAsIntList()
     * @covers CliRequest::getParamAsFloat()
     * @covers CliRequest::getParamAsFloatList()
     */
    public function testParameterTypeCast(?string $castType, array $expectedValues): void {
        $parametersString = '--single=3.14something --array=9.8whatever --array=0';
        if (null !== $castType) {
            $parametersString .= " --type={$castType}";
        }

        $result = static::assertNoErrorsOutput(__DIR__ . '/scripts/single-and-array-options.php', $parametersString);

        assertSame($expectedValues, json_decode($result->getStdOut(), true));
    }

    /**
     * @return array[]
     */
    public static function provideParameterTypeCast(): array {
        return [
            'no-cast' => [
                // By default, parameters' values are strings.
                'castType'       => null,
                'expectedValues' => [
                    'single' => '3.14something',
                    'array'  => ['9.8whatever', '0'],
                ],
            ],
            'cast-int' => [
                'castType'       => 'int',
                'expectedValues' => [
                    'single' => 3,
                    'array'  => [9, 0],
                ],
            ],
            'cast-float' => [
                'castType'       => 'float',
                'expectedValues' => [
                    'single' => 3.14,
                    'array'  => [9.8, 0],
                ],
            ],
        ];
    }

    #[DataProvider('provideParameterLogicErrors')]
    /**
     * Tests logic errors while reading parameter values processed from a request.
     *
     * @covers CliRequest::validateValueIsArray()
     * @covers CliRequest::validateValueNotArray()
     */
    public function testParameterLogicErrors(string $scriptPath, string $expectedErrorSubstring): void {
        static::assertLogicExceptionOutput($scriptPath, $expectedErrorSubstring);
    }

    /**
     * @return array[]
     */
    public static function provideParameterLogicErrors(): array {
        return [
            'array-as-single' => [
                'scriptPath'             => __DIR__ . '/scripts/get-array-as-single.php',
                'expectedErrorSubstring' => "Parameter 'option-array' contains an array",
            ],
            'single-as-array' => [
                'scriptPath'             => __DIR__ . '/scripts/get-single-as-array.php',
                'expectedErrorSubstring' => "Parameter 'option-single' contains a single value",
            ],
        ];
    }
}