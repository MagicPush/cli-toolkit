<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptLauncher;

use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Parametizer\EnvironmentConfig;
use MagicPush\CliToolkit\Parametizer\Parametizer;
use MagicPush\CliToolkit\Parametizer\Script\ScriptAbstract;
use MagicPush\CliToolkit\Parametizer\Script\ScriptLauncher\ScriptLauncher;
use MagicPush\CliToolkit\Parametizer\Script\ScriptLauncher\Subcommand\ClearCache\ClearCache;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptClassDetector;
use MagicPush\CliToolkit\Tests\Tests\Parametizer\EnvironmentConfig\EnvironmentConfigTest;
use MagicPush\CliToolkit\Tests\Tests\TestCaseAbstract;
use PHPUnit\Framework\Attributes\DataProvider;

use function PHPUnit\Framework\assertFileDoesNotExist;
use function PHPUnit\Framework\assertFileExists;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertStringContainsString;
use function PHPUnit\Framework\assertStringNotContainsString;
use function PHPUnit\Framework\assertTrue;

class ScriptLauncherTest extends TestCaseAbstract {
    private const string CACHE_PATH = __DIR__ . '/scripts/launcher-with-cache.json';

    private const string CONFIG_PATH_LAUNCHER = __DIR__ . '/ThrowOnException/'
        . EnvironmentConfig::CONFIG_FILENAME;
    private const string CONFIG_PATH_SCRIPT_CLASSES = __DIR__ . '/ThrowOnException/ScriptClasses/'
        . EnvironmentConfig::CONFIG_FILENAME;


    protected function setUp(): void {
        parent::setUp();

        $filesToDelete = [self::CACHE_PATH, self::CONFIG_PATH_LAUNCHER, self::CONFIG_PATH_SCRIPT_CLASSES];
        foreach ($filesToDelete as $filePath) {
            if (file_exists($filePath)) {
                assertTrue(unlink($filePath));
            }
        }
    }


    #[DataProvider('provideDetectorCacheCleanup')]
    /**
     * Tests {@see ClearCache} subcommand availability and execution if a specified cache file exists.
     *
     * @see ScriptLauncher::execute()
     * @see ClearCache::getConfigBuilder()
     * @see ClearCache::execute()
     */
    public function testDetectorCacheCleanup(
        bool $doesDetectorThrowOnException,
        ?string $detectorCacheFilePath,
        bool $isClearCacheSubcommandAvailable,
    ): void {
        if (null !== $detectorCacheFilePath) {
            assertFileDoesNotExist($detectorCacheFilePath);
        }

        $launcherScriptPath       = __DIR__ . '/scripts/l-with-cache.php';
        $parametersBaseString     = (int) $doesDetectorThrowOnException . " '{$detectorCacheFilePath}'";
        $clearCacheSubcommandName = ClearCache::getScriptName();

        $result = null !== $detectorCacheFilePath && !$isClearCacheSubcommandAvailable
            // Cache file is expected but failed to be created:
            ? static::assertAnyErrorOutput(
                $launcherScriptPath,
                'mkdir(): File exists in',
                "{$parametersBaseString} " . Config::PARAMETER_NAME_LIST,
                shouldAssertExitCode: false,
                shouldAssertStdErr: false,
            )
            : static::assertNoErrorsOutput(
                $launcherScriptPath,
                "{$parametersBaseString} " . Config::PARAMETER_NAME_LIST,
            );

        if (null !== $detectorCacheFilePath && $isClearCacheSubcommandAvailable) {
            assertFileExists($detectorCacheFilePath);

            // "clear-cache" subcommand is available and shown in a launcher's "list" output:
            assertStringContainsString(
                $clearCacheSubcommandName,
                $result->getStdOut(),
            );

            // "clear-cache" subcommand help page reflects the specified cache file path:
            assertStringContainsString(
                $detectorCacheFilePath,
                static::assertNoErrorsOutput(
                    $launcherScriptPath,
                    "{$parametersBaseString} {$clearCacheSubcommandName} --" . Config::OPTION_NAME_HELP,
                )
                    ->getStdOut(),
            );

            // A cache file deletion is successful:
            static::assertNoErrorsOutput($launcherScriptPath, "{$parametersBaseString} {$clearCacheSubcommandName}");
            assertFileDoesNotExist($detectorCacheFilePath);
        } else {
            assertStringNotContainsString(
                $clearCacheSubcommandName,
                $result->getStdOut(),
            );

            // There should be no file still if an invalid path is set:
            if (null !== $detectorCacheFilePath) {
                assertFileDoesNotExist($detectorCacheFilePath);

                // Also a direct attempt to delete the file should fail:
                static::assertExecutionErrorOutput(
                    $launcherScriptPath,
                    "Incorrect value '{$clearCacheSubcommandName}' for argument <subcommand-name>",
                    "{$parametersBaseString} {$clearCacheSubcommandName}",
                );
            }
        }
    }

    /**
     * @return array[]
     */
    public static function provideDetectorCacheCleanup(): array {
        return [
            'no-cache' => [
                'doesDetectorThrowOnException'    => true,
                'detectorCacheFilePath'           => null,
                'isClearCacheSubcommandAvailable' => false,
            ],
            'cache-enabled' => [
                'doesDetectorThrowOnException'    => true,
                'detectorCacheFilePath'           => self::CACHE_PATH,
                'isClearCacheSubcommandAvailable' => true,
            ],

            // Here we create a condition of invalid cache file creation:
            // a cache file path is set, but the file itself is not created, thus the subcommand is not available.
            'cache-not-generated' => [
                'doesDetectorThrowOnException'    => false,
                'detectorCacheFilePath'           => '/dev/null/launcher-cache.json',
                'isClearCacheSubcommandAvailable' => false,
            ],
        ];
    }

    #[DataProvider('provideLauncherSettingThrowOnException')]
    /**
     * Tests that the corresponding launcher setting is enabled for both internal instances.
     *
     * Here id does not matter what exact exceptions occur.
     * The point is to assert exceptions happening if a setting is enabled.
     *
     * @see ScriptLauncher::throwOnException() The flag is set here.
     * @see ScriptLauncher::execute() Here the instances with the flag passed are created.
     * @see ScriptClassDetector::__construct() Here the flag is set for the instance.
     * @see ScriptClassDetector::detectFromCache() Here the flag affects if an exception is thrown.
     * @see Parametizer::newConfig() Here the flag is set for the instance.
     * @see EnvironmentConfig::fillFromJsonConfigFile() Here the flag affects if an exception is thrown.
     */
    public function testLauncherSettingThrowOnException(
        ?string $invalidJsonFilePath,
        bool $damageScriptFilepath,
        bool $throwOnException,
        string $expectedErrorMessage,
    ): void {
        if (null !== $invalidJsonFilePath) {
            // One of possible exceptions for tested instances - if a JSON file (cache or EnvironmentConfig)
            // does not contain valid JSON. So let's create an expected file with invalid JSON.
            file_put_contents($invalidJsonFilePath, '[[definitely not a JSON string}');
        } else {
            assertFileDoesNotExist(self::CONFIG_PATH_LAUNCHER);
            assertFileDoesNotExist(self::CONFIG_PATH_SCRIPT_CLASSES);
        }

        if (!$throwOnException) {
            static::assertNoErrorsOutput(
                __DIR__ . '/' . 'ThrowOnException/setting-throw-on-exception.php',
                '0 ' . (int) $damageScriptFilepath,
            );

            return;
        }

        $result = static::assertAnyErrorOutput(
            __DIR__ . '/' . 'ThrowOnException/setting-throw-on-exception.php',
            $expectedErrorMessage,
            '1 ' . (int) $damageScriptFilepath,
            shouldAssertExitCode: false,
            shouldAssertStdErr: false,
        );

        if (null !== $invalidJsonFilePath) {
            // Let's be sure that a thrown exception is connected with a specific (parent or child) config file.
            assertStringContainsString($invalidJsonFilePath, $result->getStdAll());
        }
    }

    /**
     * @return array[]
     */
    public static function provideLauncherSettingThrowOnException(): array {
        return [
            'detector-silent' => [
                'invalidJsonFilePath'  => null,
                'damageScriptFilepath' => true,
                'throwOnException'     => false,
                'expectedErrorMessage' => '',
            ],
            'detector-exception' => [
                'invalidJsonFilePath'  => null,
                'damageScriptFilepath' => true,
                'throwOnException'     => true,
                'expectedErrorMessage' => "Path should be a readable directory: ''",
            ],
            'env-config-parent-silent' => [
                'invalidJsonFilePath'  => self::CONFIG_PATH_LAUNCHER,
                'damageScriptFilepath' => false,
                'throwOnException'     => false,
                'expectedErrorMessage' => '',
            ],
            'env-config-parent-exception' => [
                'invalidJsonFilePath'  => self::CONFIG_PATH_LAUNCHER,
                'damageScriptFilepath' => false,
                'throwOnException'     => true,
                'expectedErrorMessage' => 'Unable to read the environment config',
            ],
            'env-config-child-silent' => [
                'invalidJsonFilePath'  => self::CONFIG_PATH_SCRIPT_CLASSES,
                'damageScriptFilepath' => false,
                'throwOnException'     => false,
                'expectedErrorMessage' => '',
            ],
            'env-config-child-exception' => [
                'invalidJsonFilePath'  => self::CONFIG_PATH_SCRIPT_CLASSES,
                'damageScriptFilepath' => false,
                'throwOnException'     => true,
                'expectedErrorMessage' => 'Unable to read the environment config',
            ],
        ];
    }

    #[DataProvider('provideLauncherSettingSameEnvConfigForSubcommands')]
    /**
     * Tests that {@see EnvironmentConfig} instance set for a parent config is also utilized by subcommands,
     * if the corresponding setting is enabled.
     *
     * Here id does not matter what exact {@see EnvironmentConfig} setting is analyzed.
     * The point is to assert that the expected {@see EnvironmentConfig} instance is used.
     *
     * Built-in subcommands are tested here:
     * {@see EnvironmentConfigTest::testBuiltInSubcommandsUtilizeParentEnvConfig()}.
     *
     * @see ScriptLauncher::useParentEnvConfigForSubcommands() The flag is set here.
     * @see ScriptLauncher::execute() Here the parent config {@see EnvironmentConfig} instance is passed
     * (or not) to subcommand configs.
     * @see ScriptAbstract::getConfigBuilder() Here the parent config may be passed to a subcommand.
     */
    public function testLauncherSettingSameEnvConfigForSubcommands(
        bool $isSameEnvConfigForSubcommands,
        bool $isEnvConfigManual,
        string $expectedSubstringParent,
        string $expectedSubstringSubcommand,
    ): void {
        // Assert the env config setting affecting the parent config:
        assertStringContainsString(
            $expectedSubstringParent,
            static::assertNoErrorsOutput(
                __DIR__ . '/' . 'SameEnvConfig/scripts/setting-parent-config-for-subcommands.php',
                sprintf(
                    '%d %d --%s',
                    $isSameEnvConfigForSubcommands,
                    $isEnvConfigManual,
                    Config::OPTION_NAME_HELP,
                ),
            )
                ->getStdOut(),
        );

        // Then assert the state of the same env config setting for a subcommand:
        assertStringContainsString(
            $expectedSubstringSubcommand,
            static::assertNoErrorsOutput(
                __DIR__ . '/' . 'SameEnvConfig/scripts/setting-parent-config-for-subcommands.php',
                sprintf(
                    '%d %d test-some --%s',
                    $isSameEnvConfigForSubcommands,
                    $isEnvConfigManual,
                    Config::OPTION_NAME_HELP,
                ),
            )
                ->getStdOut(),
        );
    }

    /**
     * @return array[]
     */
    public static function provideLauncherSettingSameEnvConfigForSubcommands(): array {
        return [
            'different-configs-with-autoload' => [
                'isSameEnvConfigForSubcommands' => false,
                'isEnvConfigManual'             => false,
                'expectedSubstringParent'       => '-A, --' . Config::OPTION_NAME_HELP,
                'expectedSubstringSubcommand'   => '-L, --' . Config::OPTION_NAME_HELP,
            ],
            'same-configs-parent-autoload' => [
                'isSameEnvConfigForSubcommands' => true,
                'isEnvConfigManual'             => false,
                'expectedSubstringParent'       => '-A, --' . Config::OPTION_NAME_HELP,
                'expectedSubstringSubcommand'   => '-A, --' . Config::OPTION_NAME_HELP,
            ],
            'different-configs-parent-manual' => [
                'isSameEnvConfigForSubcommands' => false,
                'isEnvConfigManual'             => true,
                'expectedSubstringParent'       => '-M, --' . Config::OPTION_NAME_HELP,
                'expectedSubstringSubcommand'   => '-L, --' . Config::OPTION_NAME_HELP,
            ],
            'same-configs-parent-manual' => [
                'isSameEnvConfigForSubcommands' => true,
                'isEnvConfigManual'             => true,
                'expectedSubstringParent'       => '-M, --' . Config::OPTION_NAME_HELP,
                'expectedSubstringSubcommand'   => '-M, --' . Config::OPTION_NAME_HELP,
            ],
        ];
    }

    /**
     * Tests the case with global scope detection that no stock subcommands must be detected:
     * no built-in subcommands, no stock "cli-toolkit" subcommands, no test script classes.
     *
     * @see ScriptLauncher::execute()
     * @see ScriptClassDetector::processDetectedFileContents()
     * @see ScriptAbstract::isAvailableByDetector()
     */
    public function testBuiltInCommandsAreAlwaysIgnored(): void {
        assertSame(
            <<<TEXT
            help    Outputs a help page for a specified subcommand.
            list    Shows available subcommands.

            TEXT,
            self::assertNoErrorsOutput(
                __DIR__ . '/scripts/l-global.php',
                Config::PARAMETER_NAME_LIST . ' --slim',
            )
                ->getStdOut(),
        );
    }
}
