<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\TestClasses;

use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptDetectorAbstract;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptDetectorRuntimeException;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptFileDetector;
use PHPUnit\Framework\Attributes\DataProvider;

use function PHPUnit\Framework\assertFileDoesNotExist;
use function PHPUnit\Framework\assertFileExists;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

final class ScriptFileDetectorCacheTest extends ScriptDetectorCacheTestAbstract {
    #[DataProvider('provideCachedDetection')]
    /**
     * Tests scripts detection (via directories) caching.
     *
     * @see ScriptFileDetector::getDataToStoreInCache()
     * @see ScriptFileDetector::loadDataFromCache()
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
        $detector = ScriptFileDetector::create(throwOnException: true)
            ->cacheFilePath($isCachePathSet ? static::CACHE_FILE_RELATIVE_PATH : null)
            ->searchDirectory(__DIR__ . '/../ScriptFiles/Red', isRecursive: true);

        /*
         * After the first launch we expect:
         *  1. A specific set of script files detected.
         *  2. A cache file being created depending on the path is set or not.
         */
        assertSame(
            [
                'somewhat-l' => realpath(__DIR__ . '/../ScriptFiles/Red/Subdirectory/somewhat-l.php'),
                'somewhat'   => realpath(__DIR__ . '/../ScriptFiles/Red/somewhat.php'),
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
            ->excludeDirectory(__DIR__ . '/../ScriptFiles/Red/Subdirectory')
            ->searchDirectory(__DIR__ . '/../ScriptFiles/Green');
        if ($isCachePathSet && !$doesCacheFileExistAfterFirstLaunch) {
            assertTrue(unlink(static::CACHE_FILE_RELATIVE_PATH));
        }

        if ($isCacheDetectionExpected) {
            // If caching is enabled and a cache file is available, then we will see the same result as before:
            assertSame(
                [
                    'somewhat-l' => realpath(__DIR__ . '/../ScriptFiles/Red/Subdirectory/somewhat-l.php'),
                    'somewhat'   => realpath(__DIR__ . '/../ScriptFiles/Red/somewhat.php'),
                ],
                $detector->getDetectedData(),
            );
        } else {
            // ... Otherwise the second detection result will differ:
            assertSame(
                [
                    'somewhat'         => realpath(__DIR__ . '/../ScriptFiles/Red/somewhat.php'),
                    'somewhat-another' => realpath(__DIR__ . '/../ScriptFiles/Green/somewhat-another.php'),
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

    #[DataProvider('provideThrowOnException')]
    /**
     * Tests how the file detector handles invalid cache elements.
     *
     * @see ScriptFileDetector::loadDataFromCache()
     */
    public function testLoadDataFromCacheException(bool $throwOnException): void {
        if ($throwOnException) {
            $this->expectExceptionObject(
                new ScriptDetectorRuntimeException(
                    'Script file is not readable or does not exist: /asd',
                ),
            );
        }

        // Let's create a cache file with the first invalid element and the second valid element:
        $this->createCacheFile(
            json_encode(
                value: [
                    'asd'      => '/asd',
                    'somewhat' => realpath(__DIR__ . '/../ScriptFiles/Blue/somewhat.php'),
                ],
                flags: JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR,
            ),
        );

        // Now let's set up a detector with caching...
        $detector = ScriptFileDetector::create($throwOnException)
            ->cacheFilePath(static::CACHE_FILE_RELATIVE_PATH)
            // Search settings are not important because a cache file should be used instead.
            ->searchDirectory(__DIR__);

        // If exceptions are disabled for the detector, we will see here the only valid detected element:
        assertSame(
            ['somewhat' => realpath(__DIR__ . '/../ScriptFiles/Blue/somewhat.php')],
            $detector->getDetectedData(),
        );
    }
}
