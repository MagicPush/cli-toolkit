<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\EnvironmentConfig;

use MagicPush\CliToolkit\Parametizer\Parametizer;
use MagicPush\CliToolkit\Tests\Tests\TestCaseAbstract;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

use function PHPUnit\Framework\assertSame;

class EnvironmentConfigTest extends TestCaseAbstract {
    #[DataProvider('provideDifferentBranchConfigs')]
    /**
     * Tests that a setting is read from an {@see EnvironmentConfig} instance linked to a corresponding config branch,
     * when a parse error is thrown during a script execution.
     *
     * Here it does not matter what exact {@see EnvironmentConfig} setting is tested.
     * As an example, {@see EnvironmentConfig::$optionHelpShortName} is analyzed.
     *
     * @see Parametizer::setExceptionHandlerForParsing()
     */
    public function testDifferentBranchConfigs(string $parametersString, string $expectedErrorOutput): void {
        static::assertFullErrorOutput(
            __DIR__ . '/scripts/main-and-subcommands.php',
            $expectedErrorOutput,
            $parametersString,
        );
    }

    public static function provideDifferentBranchConfigs(): array {
        return [
            'main' => [
                'parametersString'    => '',
                'expectedErrorOutput' => <<<STDERR_OUTPUT
Need more parameters


  -X, --help      Show full help page.

  <switchme-l1>   Allowed values: conf-l2-s1, conf-l2-s2
  (required)      Subcommand help: <script_name> ... <subcommand value> --help

STDERR_OUTPUT,
            ],
            'level-2' => [
                'parametersString'    => 'conf-l2-s2',
                'expectedErrorOutput' => <<<STDERR_OUTPUT
Need more parameters


  -y, --help         Show full help page.

  <switchme-l2-s2>   Allowed values: conf-l3-s1, conf-l3-s2
  (required)         Subcommand help: <script_name> ... <subcommand value> --help

STDERR_OUTPUT,
            ],
            'level-3' => [
                'parametersString'    => 'conf-l2-s2 conf-l3-s1 invalid',
                'expectedErrorOutput' => <<<STDERR_OUTPUT
Too many arguments, starting with 'invalid'


  -z, --help   Show full help page.

STDERR_OUTPUT,
            ],
        ];
    }

    #[DataProvider('provideAutoloadFromFiles')]
    /**
     * Tests various environment config autoload cases.
     *
     * @param mixed[] $expectedConfigValues
     * @covers EnvironmentConfig::createFromConfigsBottomUpHierarchy()
     * @covers EnvironmentConfig::detectTopmostDirectoryPath()
     * @covers EnvironmentConfig::fillFromJsonConfigFile()
     * @covers Parametizer::newConfig()
     */
    public function testAutoloadFromFiles(string $scriptPath, array $expectedConfigValues): void {
        $outputJson   = static::assertNoErrorsOutput($scriptPath)->getStdOut();
        $actualValues = json_decode($outputJson, true, flags: JSON_THROW_ON_ERROR);

        assertSame($expectedConfigValues, $actualValues);
    }

    public static function provideAutoloadFromFiles(): array {
        return [
            'same-directory' => [
                'scriptPath'           => __DIR__ . '/' . 'autoload/same-directory.php',
                'expectedConfigValues' => [
                    'optionHelpShortName'                                 => 'A',
                    'helpGeneratorShortDescriptionCharsMinBeforeFullStop' => 1,
                    'helpGeneratorShortDescriptionCharsMax'               => 2,
                ],
            ],

            // If there is no config file in the same directory as a script file,
            // we should look for a config file in a directory above.
            'lower-dir-find-higher-config' => [
                'scriptPath'           => __DIR__ . '/' . 'autoload/level2/level2-no-config-nearby.php',
                'expectedConfigValues' => [
                    'optionHelpShortName'                                 => 'A',
                    'helpGeneratorShortDescriptionCharsMinBeforeFullStop' => 1,
                    'helpGeneratorShortDescriptionCharsMax'               => 2,
                ],
            ],

            // If a config nearby contains only a part of settings,
            // the rest might be filled from other configs above (if present).
            'filled-by-several-configs' => [
                'scriptPath'           => __DIR__ . '/' . 'autoload/level2/level3/partial-config-nearby.php',
                'expectedConfigValues' => [
                    'optionHelpShortName'                                 => 'X', // From the nearby config.
                    'helpGeneratorShortDescriptionCharsMinBeforeFullStop' => 1,   // From the topmost config.
                    'helpGeneratorShortDescriptionCharsMax'               => 2,   // From the topmost config.
                ],
            ],

            // Stop searching for configs above and leave the rest of settings with default values,
            // if reached the topmost search directory.
            'no-search-above-topmost' => [
                'scriptPath'           => __DIR__ . '/' . 'autoload/level2/level3/no-search-above-topmost.php',
                'expectedConfigValues' => [
                    'optionHelpShortName'                                 => 'X',  // From the nearby config.
                    'helpGeneratorShortDescriptionCharsMinBeforeFullStop' => 40,   // A default value.
                    'helpGeneratorShortDescriptionCharsMax'               => 70,   // A default value.
                ],
            ],
        ];
    }

    #[DataProvider('provideThrowingOnExceptions')]
    /**
     * Tests if all environment config autoload-related methods process `$throwOnException` flag correctly.
     *
     * @covers EnvironmentConfig::createFromConfigsBottomUpHierarchy()
     * @covers EnvironmentConfig::fillFromJsonConfigFile()
     */
    public function testThrowingOnExceptions(
        string $scriptPath,
        bool $throwOnException,
        string $expectedErrorOutput,
        string $expectedStdOutput,
    ): void {
        if ($throwOnException) {
            // This way `$throwOnException` flag is passed to a config builder:
            $parametersString = '1';

            self::assertAnyErrorOutput(
                $scriptPath,
                $expectedErrorOutput,
                $parametersString,
                RuntimeException::class . ': ',
                false,
            );
        } else {
            // This way `$throwOnException` flag is not passed to a config builder, throwing is disabled:
            $parametersString = '';

            assertSame($expectedStdOutput, self::assertNoErrorsOutput($scriptPath, $parametersString)->getStdOut());
        }
    }

    /**
     * @return array[]
     */
    public static function provideThrowingOnExceptions(): array {
        return [
            'invalid-path-bottommost-no-throw' => [
                'scriptPath'          => __DIR__ . '/' . 'autoload-with-exceptions/invalid-path-bottommost.php',
                'throwOnException'    => false,
                'expectedErrorOutput' => '',
                'expectedStdOutput'   => '',
            ],
            'invalid-path-bottommost-exception' => [
                'scriptPath'          => __DIR__ . '/' . 'autoload-with-exceptions/invalid-path-bottommost.php',
                'throwOnException'    => true,
                'expectedErrorOutput' => 'Unable to read the bottommost directory',
                'expectedStdOutput'   => '',
            ],

            'invalid-path-topmost-no-throw' => [
                'scriptPath'          => __DIR__ . '/' . 'autoload-with-exceptions/invalid-path-topmost.php',
                'throwOnException'    => false,
                'expectedErrorOutput' => '',
                'expectedStdOutput'   => '',
            ],
            'invalid-path-topmost-exception' => [
                'scriptPath'          => __DIR__ . '/' . 'autoload-with-exceptions/invalid-path-topmost.php',
                'throwOnException'    => true,
                'expectedErrorOutput' => 'Unable to read the topmost directory',
                'expectedStdOutput'   => '',
            ],

            'invalid-json-no-throw' => [
                'scriptPath'          => __DIR__ . '/' . 'autoload-with-exceptions/invalid-json-file/invalid-json-file.php',
                'throwOnException'    => false,
                'expectedErrorOutput' => '',
                'expectedStdOutput'   => '',
            ],
            'invalid-json-exception' => [
                'scriptPath'          => __DIR__ . '/' . 'autoload-with-exceptions/invalid-json-file/invalid-json-file.php',
                'throwOnException'    => true,
                'expectedErrorOutput' => 'Unable to read the environment config',
                'expectedStdOutput'   => '',
            ],

            // Here we try setting as many values as possible.
            // The rest of parameters are set via the next config in the search tree (in the directory above).
            'invalid-parameter-types-no-throw' => [
                'scriptPath'          => __DIR__ . '/' . 'autoload-with-exceptions/invalid-parameter-types/level2/invalid-parameter-types.php',
                'throwOnException'    => false,
                'expectedErrorOutput' => '',
                'expectedStdOutput'   => '["p",100,20]',
            ],
            'invalid-parameter-types-exception' => [
                'scriptPath'          => __DIR__ . '/' . 'autoload-with-exceptions/invalid-parameter-types/level2/invalid-parameter-types.php',
                'throwOnException'    => true,
                'expectedErrorOutput' => "Unable to set 'optionHelpShortName' environment config setting to the value: 0.0",
                'expectedStdOutput'   => '',
            ],
        ];
    }
}
