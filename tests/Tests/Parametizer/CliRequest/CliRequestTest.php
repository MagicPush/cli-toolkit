<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\CliRequest;

use MagicPush\CliToolkit\Parametizer\CliRequest\CliRequest;
use MagicPush\CliToolkit\Parametizer\CliRequest\CliRequestProcessor;
use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Parametizer\Config\Parameter\ParameterAbstract;
use MagicPush\CliToolkit\Parametizer\Exception\ConfigException;
use MagicPush\CliToolkit\Tests\Tests\TestCaseAbstract;
use PHPUnit\Framework\Attributes\DataProvider;

use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertStringContainsString;

class CliRequestTest extends TestCaseAbstract {
    #[DataProvider('provideGettingParameterValue')]
    /**
     * Tests different reading of parameter values processed from a request.
     *
     * @see CliRequest::getParam()
     * @see CliRequest::getParams()
     */
    public function testGettingParameterValue(string $parameterName, string $parameterValue): void {
        $result = static::assertNoErrorsOutput(__DIR__ . '/scripts/template-request-parameter-name.php', $parameterName);

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
     * @see CliRequest::getParam()
     */
    public function testGettingParameterValueByInvalidName(): void {
        static::assertLogicExceptionOutput(
            __DIR__ . '/scripts/template-request-parameter-name.php',
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
     * @see CliRequest::getParam()
     * @see CliRequest::getParamAsBool()
     * @see CliRequest::getParamAsInt()
     * @see CliRequest::getParamAsIntList()
     * @see CliRequest::getParamAsFloat()
     * @see CliRequest::getParamAsFloatList()
     * @see CliRequest::getParamAsString()
     * @see CliRequest::getParamAsStringList()
     */
    public function testParameterTypeCast(?string $castType, array $expectedValues): void {
        $parametersString = "--single=3.14something --array=9.8whatever --array='-' --array=1";
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
                    'array'  => ['9.8whatever', null, true],
                ],
            ],
            'cast-bool' => [
                'castType'       => 'bool',
                'expectedValues' => [
                    'single' => true,
                    'array'  => ['9.8whatever', null, true], // No bool casting is implemented for array elements.
                ],
            ],
            'cast-int' => [
                'castType'       => 'int',
                'expectedValues' => [
                    'single' => 3,
                    'array'  => [9, 0, 1],
                ],
            ],
            'cast-float' => [
                'castType'       => 'float',
                'expectedValues' => [
                    'single' => 3.14,
                    'array'  => [9.8, 0, 1],
                ],
            ],
            'cast-string' => [
                'castType'       => 'string',
                'expectedValues' => [
                    'single' => '3.14something',
                    'array'  => ['9.8whatever', '', '1'],
                ],
            ],
        ];
    }

    #[DataProvider('provideParameterLogicErrors')]
    /**
     * Tests logic errors while reading parameter values processed from a request.
     *
     * @see CliRequest::getParamAsBool()
     * @see CliRequest::getParamAsInt()
     * @see CliRequest::getParamAsIntList()
     * @see CliRequest::getParamAsFloat()
     * @see CliRequest::getParamAsFloatList()
     * @see CliRequest::getParamAsString()
     * @see CliRequest::getParamAsStringList()
     * @see CliRequest::validateValueIsArray()
     * @see CliRequest::validateValueNotArray()
     */
    public function testParameterLogicErrors(
        string $scriptPath,
        string $castType,
        string $expectedErrorSubstring,
    ): void {
        static::assertLogicExceptionOutput($scriptPath, $expectedErrorSubstring, "--type={$castType}");
    }

    /**
     * @return array[]
     */
    public static function provideParameterLogicErrors(): array {
        return [
            'array-as-single-bool' => [
                'scriptPath'             => __DIR__ . '/scripts/get-array-as-single.php',
                'castType'               => 'bool',
                'expectedErrorSubstring' => "Parameter 'option-array' contains an array",
            ],
            'array-as-single-int' => [
                'scriptPath'             => __DIR__ . '/scripts/get-array-as-single.php',
                'castType'               => 'int',
                'expectedErrorSubstring' => "Parameter 'option-array' contains an array",
            ],
            'array-as-single-float' => [
                'scriptPath'             => __DIR__ . '/scripts/get-array-as-single.php',
                'castType'               => 'float',
                'expectedErrorSubstring' => "Parameter 'option-array' contains an array",
            ],
            'array-as-single-string' => [
                'scriptPath'             => __DIR__ . '/scripts/get-array-as-single.php',
                'castType'               => 'string',
                'expectedErrorSubstring' => "Parameter 'option-array' contains an array",
            ],

            'single-as-array-int' => [
                'scriptPath'             => __DIR__ . '/scripts/get-single-as-array.php',
                'castType'               => 'int',
                'expectedErrorSubstring' => "Parameter 'option-single' contains a single value",
            ],
            'single-as-array-float' => [
                'scriptPath'             => __DIR__ . '/scripts/get-single-as-array.php',
                'castType'               => 'float',
                'expectedErrorSubstring' => "Parameter 'option-single' contains a single value",
            ],
            'single-as-array-string' => [
                'scriptPath'             => __DIR__ . '/scripts/get-single-as-array.php',
                'castType'               => 'string',
                'expectedErrorSubstring' => "Parameter 'option-single' contains a single value",
            ],
        ];
    }

    #[DataProvider('provideSubcommandDataInRequest')]
    /**
     * Tests that if no subcommands are available, subcommand-related methods in the request object will render `null`.
     *
     * @see CliRequest::getRequestedSubcommandName()
     * @see CliRequest::getSubcommandRequest()
     */
    public function testSubcommandDataInRequest(string $subcommandName, array $expectedValues): void {
        $result = static::assertNoErrorsOutput(__DIR__ . '/scripts/template-subcommand-request.php', $subcommandName);

        assertSame($expectedValues, json_decode($result->getStdOut(), true));
    }

    /**
     * @return array[]
     */
    public static function provideSubcommandDataInRequest(): array {
        return [
            'no-subcommand' => [
                'subcommandName' => '',
                'expectedValues' => [
                    'subcommand_name'           => null,
                    'subcommand_request_params' => null,
                ],
            ],
            'has-subcommand' => [
                'subcommandName' => 'awesome',
                'expectedValues' => [
                    'subcommand_name'           => 'awesome',
                    'subcommand_request_params' => ['arg' => 'default-value'],
                ],
            ],
        ];
    }

    /**
     * Tests that a subcommand has access to parent request.
     *
     * @see CliRequest::__construct()
     * @see CliRequest::getSubcommandRequest()
     */
    public function testAccessToParentRequest(): void {
        assertSame(
            <<<TEXT
            'asd'
            TEXT,
            static::assertNoErrorsOutput(__DIR__ . '/scripts/subcommand-reads-parent-request.php', '--opt=asd test')
                ->getStdOut(),
        );
    }

    #[DataProvider('provideSubcommandDoesNotShadowParentParameter')]
    /**
     * Tests that parent config parameter values are not lost if a subcommand with the same name is picked.
     *
     * It is achieved by adding {@see CliRequest::SUBCOMMAND_PREFIX}
     * to the beginning of a subcommand parameters sub-array name.
     *
     * @see CliRequestProcessor::parseSubcommandParameters()
     * @see CliRequest::getSubcommandRequest()
     */
    public function testSubcommandDoesNotShadowParentParameter(
        string $parametersString,
        array $expectedRequestParameters,
    ): void {
        assertSame(
            $expectedRequestParameters,
            json_decode(
                static::assertNoErrorsOutput(
                    __DIR__ . '/scripts/subcommand-and-parameter-same-names.php',
                    $parametersString,
                )->getStdOut(),
                true,
            ),
        );
    }

    /**
     * @return array[]
     */
    public static function provideSubcommandDoesNotShadowParentParameter(): array {
        return [
            'argument' => [
                'parametersString'          => 'some-argument argument',
                'expectedRequestParameters' => [
                    'argument'        => 'some-argument',
                    'option'          => 'option-default',
                    'subcommand-name' => 'argument',

                    CliRequest::SUBCOMMAND_PREFIX . 'argument' => [],
                ],
            ],
            'option' => [
                'parametersString'          => 'some-argument option',
                'expectedRequestParameters' => [
                    'argument'        => 'some-argument',
                    'option'          => 'option-default',
                    'subcommand-name' => 'option',

                    CliRequest::SUBCOMMAND_PREFIX . 'option' => [],
                ],
            ],
        ];
    }

    #[DataProvider('provideErrorIfRequestSubcommandPrefixIsUsedForParameterNames')]
    /**
     * Tests that it is not possible to add the request subcommand prefix ({@see CliRequest::SUBCOMMAND_PREFIX})
     * to parameter and subcommand regular names.
     *
     * This check adds insurance that when a request multi-dimensional array is created,
     * subcommand sub-request name can not replace a parent parameter name in a request array.
     *
     * @see ParameterAbstract::__construct()
     * @see Config::newSubcommand()
     */
    public function testErrorIfRequestSubcommandPrefixIsUsedForParameterNames(
        string $parameterType,
        string $expectedErrorOutputSubstringMessage,
        string $expectedErrorOutputSubstringTrace,
    ): void {
        $result = static::assertAnyErrorOutput(
            __DIR__ . '/' . 'scripts/template-parameter-or-subcommand-name.php',
            $expectedErrorOutputSubstringMessage,
            $parameterType,
            ConfigException::class . ': ',
        );

        assertStringContainsString($expectedErrorOutputSubstringTrace, $result->getStdErr());
    }

    /**
     * @return array[]
     */
    public static function provideErrorIfRequestSubcommandPrefixIsUsedForParameterNames(): array {
        $prefix = CliRequest::SUBCOMMAND_PREFIX;

        return [
            'argument' => [
                'parameterType'                       => 'argument',
                'expectedErrorOutputSubstringMessage' => "'{$prefix}something' >>> Config error: invalid characters. Must start with latin (lower);",
                'expectedErrorOutputSubstringTrace'   => 'MagicPush\CliToolkit\Parametizer\Config\Builder\ArgumentBuilder->__construct()',
            ],
            'option' => [
                'parameterType'                       => 'option',
                'expectedErrorOutputSubstringMessage' => "'{$prefix}something' >>> Config error: invalid characters. Must start with latin (lower);",
                'expectedErrorOutputSubstringTrace'   => 'MagicPush\CliToolkit\Parametizer\Config\Builder\OptionBuilder->__construct()',
            ],
            'subcommand' => [
                'parameterType'                       => 'subcommand',
                'expectedErrorOutputSubstringMessage' => "'subcommand-name' subcommand >>> Config error: invalid characters in value '{$prefix}something'. Must start with a latin (lower);",
                'expectedErrorOutputSubstringTrace'   => 'MagicPush\CliToolkit\Parametizer\Config\Config->newSubcommand()',
            ],
        ];
    }
}
