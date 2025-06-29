<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\TestClasses;

use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptClassDetector;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptDetectorAbstract;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

use function PHPUnit\Framework\assertFileDoesNotExist;
use function PHPUnit\Framework\assertFileExists;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

class ScriptClassDetectorCacheTest extends ScriptDetectorCacheTestAbstract {
    #[DataProvider('provideCachedDetection')]
    /**
     * Tests class detection (via directories) caching.
     *
     * @see ScriptClassDetector::getDataToStoreInCache()
     * @see ScriptClassDetector::loadDataFromCache()
     * @see ScriptDetectorAbstract::cacheFilePath()
     * @see ScriptDetectorAbstract::storeDetectedToCache()
     * @see ScriptDetectorAbstract::detectBySettings()
     * @see ScriptDetectorAbstract::getCacheFilePath()
     * @see ScriptDetectorAbstract::detectFromCache()
     * @see ScriptDetectorAbstract::doesCacheFileExist()
     * @see ScriptDetectorAbstract::detect()
     */
    public function testCachedDetection(
        bool $isCachePathSet,
        bool $doesCacheFileExistAfterFirstLaunch,
        bool $isCacheDetectionExpected,
    ): void {
        // Initially the cache file does not exist:
        assertFileDoesNotExist(static::CACHE_FILE_RELATIVE_PATH);

        // Let's set up the detector:
        $detector = (new ScriptClassDetector(throwOnException: true))
            ->cacheFilePath($isCachePathSet ? static::CACHE_FILE_RELATIVE_PATH : null)
            ->searchDirectory(__DIR__ . '/../ScriptClasses/Red/RedLeft3', isRecursive: true);

        /*
         * After the first launch we expect:
         *  1. A specific set of script classes detected.
         *  2. A cache file being created depending on the path is set or not.
         */
        assertSame(
            [
                'red:something6' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Subdirectory\Something6',
                'red:something5' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Something5',
            ],
            $detector->getDetectedData(),
        );
        if ($isCachePathSet) {
            assertFileExists(static::CACHE_FILE_RELATIVE_PATH);
            // Real path is stored instead of relative:
            assertSame(realpath(static::CACHE_FILE_RELATIVE_PATH), $detector->getCacheFilePath());
        } else {
            assertFileDoesNotExist(static::CACHE_FILE_RELATIVE_PATH);
            assertNull($detector->getCacheFilePath());
        }

        // And now goes the interesting part: we update the set of detection rules and optionally remove the cache file.
        $detector
            ->excludeDirectory(__DIR__ . '/../ScriptClasses/Red/RedLeft3/Subdirectory')
            ->searchDirectory(__DIR__ . '/../ScriptClasses/Red/RedRight', isRecursive: true);
        if ($isCachePathSet && !$doesCacheFileExistAfterFirstLaunch) {
            assertTrue(unlink(static::CACHE_FILE_RELATIVE_PATH));
        }

        if ($isCacheDetectionExpected) {
            // If caching is enabled and a cache file is available, then we will see the same result as before:
            assertSame(
                [
                    'red:something6' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Subdirectory\Something6',
                    'red:something5' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Something5',
                ],
                $detector->getDetectedData(),
            );
        } else {
            // ... Otherwise the second detection result will differ:
            assertSame(
                [
                    'red:something5' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Something5',
                    'red:something8' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight\Subdirectory\Something8',
                    'red:something7' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight\Something7',
                ],
                $detector->getDetectedData(),
            );
        }
    }

    /**
     * @return array[]
     */
    public static function provideCachedDetection(): array {
        return [
            'no-path-no-caching' => [
                'isCachePathSet'                     => false,
                'doesCacheFileExistAfterFirstLaunch' => false,
                'isCacheDetectionExpected'           => false,
            ],
            'missing-file-no-caching' => [
                'isCachePathSet'                     => true,
                'doesCacheFileExistAfterFirstLaunch' => false,
                'isCacheDetectionExpected'           => false,
            ],
            'cached' => [
                'isCachePathSet'                     => true,
                'doesCacheFileExistAfterFirstLaunch' => true,
                'isCacheDetectionExpected'           => true,
            ],
        ];
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection */
    #[DataProvider('provideThrowOnException')]
    /**
     * Tests how the class detector handles invalid cache elements.
     *
     * @see ScriptClassDetector::loadDataFromCache()
     */
    public function testLoadDataFromCacheException(bool $throwOnException): void {
        if ($throwOnException) {
            $this->expectExceptionObject(
                new RuntimeException(
                    sprintf(
                        "'%s' is not a subclass of %s",
                        \MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\AnotherThing::class,
                        \MagicPush\CliToolkit\Parametizer\Script\ScriptAbstract::class,
                    ),
                ),
            );
        }

        // Let's create a cache file with the first invalid element and the second valid element:
        $this->createCacheFile(
            json_encode(
                value: [
                    \MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\AnotherThing::class,
                    \SomethingNoNamespace::class,
                ],
                flags: JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR,
            ),
        );

        // Now let's set up a detector with caching...
        $detector = (new ScriptClassDetector($throwOnException))
            ->cacheFilePath(static::CACHE_FILE_RELATIVE_PATH)
            // Search settings are not important because a cache file should be used instead.
            ->searchDirectory(__DIR__, isRecursive: true);

        // If exceptions are disabled for the detector, we will see here the only valid detected element:
        assertSame(
            ['something-no-namespace' => 'SomethingNoNamespace'],
            $detector->getDetectedData(),
        );
    }
}
