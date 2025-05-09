<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptLauncher;

use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Parametizer\Script\ScriptLauncher\ScriptLauncher;
use MagicPush\CliToolkit\Parametizer\Script\ScriptLauncher\Subcommand\ClearCache\ClearCache;
use MagicPush\CliToolkit\Tests\Tests\TestCaseAbstract;
use PHPUnit\Framework\Attributes\DataProvider;

use function PHPUnit\Framework\assertFileDoesNotExist;
use function PHPUnit\Framework\assertFileExists;
use function PHPUnit\Framework\assertStringContainsString;
use function PHPUnit\Framework\assertStringNotContainsString;

class ScriptLauncherTest extends TestCaseAbstract {
    #[DataProvider('provideDetectorCacheCleanup')]
    /**
     * Tests {@see ClearCache} subcommand availability and execution if a specified cache file exists.
     *
     * @see ScriptLauncher::execute()
     * @see ClearCache::getConfiguration()
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

        $launcherScriptPath       = __DIR__ . '/scripts/launcher.php';
        $parametersBaseString     = (int) $doesDetectorThrowOnException . " '{$detectorCacheFilePath}'";
        $clearCacheSubcommandName = ClearCache::getFullName();

        $result = null !== $detectorCacheFilePath && !$isClearCacheSubcommandAvailable
            // Cache file is expected but failed to be created:
            ? static::assertAnyErrorOutput(
                $launcherScriptPath,
                'PHP Warning:  mkdir()',
                "{$parametersBaseString} " . Config::PARAMETER_NAME_LIST,
                shouldAssertExitCode: false,
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
                static::assertAnyErrorOutput(
                    $launcherScriptPath,
                    "Incorrect value '{$clearCacheSubcommandName}' for argument <subcommand>",
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
                'detectorCacheFilePath'           => __DIR__ . '/generated/launcher-cache.json',
                'isClearCacheSubcommandAvailable' => true,
            ],

            // Here we create a condition of invalid cache file creation:
            // a cache file path is set, but the file itself is not created, thus the subcommand is not available.
            'cache-not-generated' => [
                'doesDetectorThrowOnException'    => false,
                'detectorCacheFilePath'           => '/dev/null/launcher-cache.json',
                'isClearCacheSubcommandAvailable' => false,
            ]
        ];
    }
}
