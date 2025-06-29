<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\TestClasses;

use MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\Mocks\ScriptDetectorMock;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

use function PHPUnit\Framework\assertFileDoesNotExist;
use function PHPUnit\Framework\assertSame;

class ScriptDetectorCacheTest extends ScriptDetectorCacheTestAbstract {
    /**
     * Tests transforming a relative cache file path to an absolute.
     *
     * @see ScriptDetectorAbstract::cacheFilePath()
     * @see ScriptDetectorAbstract::getCacheFilePath()
     */
    public function testCachePathRelativeToAbsolute(): void {
        $detector = new ScriptDetectorMock(throwOnException: true);

        $detector->cacheFilePath(static::CACHE_FILE_RELATIVE_PATH);
        // If a cache file does not exist, the relative (unchanged) path is stored:
        assertFileDoesNotExist(static::CACHE_FILE_RELATIVE_PATH);
        assertSame(static::CACHE_FILE_RELATIVE_PATH, $detector->getCacheFilePath());

        $this->createCacheFile('no-matter');
        // But if a cache file exists, the relative path set is immediately transformed into a real path:
        $detector->cacheFilePath(static::CACHE_FILE_RELATIVE_PATH);
        assertSame(realpath(static::CACHE_FILE_RELATIVE_PATH), $detector->getCacheFilePath());
    }

    #[DataProvider('provideThrowOnException')]
    /**
     * Tests if a cache file exists, but can not be readable (for instance, no access).
     *
     * @see ScriptDetectorAbstract::detectFromCache()
     * @see ScriptDetectorAbstract::doesCacheFileExist()
     * @see ScriptDetectorAbstract::detect()
     */
    public function testCacheFileNotReadable(bool $throwOnException): void {
        if ($throwOnException) {
            $this->expectExceptionObject(new RuntimeException("Could not read the cache file: '/etc/shadow'"));
        }

        assertSame(
            [], // This assertion should happen only if exceptions are disabled.
            (new ScriptDetectorMock($throwOnException))
                ->searchDirectory(__DIR__ . '/../ScriptClasses', isRecursive: true)
                ->cacheFilePath('/etc/shadow')
                ->getDetectedData(),
        );
    }

    #[DataProvider('provideThrowOnException')]
    /**
     * Tests cache contents parsing failure.
     *
     * @see ScriptDetectorAbstract::detectFromCache()
     * @see ScriptDetectorAbstract::doesCacheFileExist()
     * @see ScriptDetectorAbstract::detect()
     */
    public function testFailToParseCacheFileContents(bool $throwOnException): void {
        $this->createCacheFile('not-a-json');

        if ($throwOnException) {
            $this->expectExceptionObject(
                new RuntimeException(
                    sprintf(
                        "Unable to parse JSON from the cache file '%s': %s",
                        realpath(static::CACHE_FILE_RELATIVE_PATH),
                        'Syntax error',
                    ),
                ),
            );
        }

        assertSame(
            [], // This assertion should happen only if exceptions are disabled.
            (new ScriptDetectorMock($throwOnException))
                ->searchDirectory(__DIR__ . '/../ScriptClasses', isRecursive: true)
                ->cacheFilePath(static::CACHE_FILE_RELATIVE_PATH)
                ->getDetectedData(),
        );
    }

    #[DataProvider('provideThrowOnException')]
    /**
     * Tests failed attempts to create a cache file directory (for instance, no access).
     *
     * @see ScriptDetectorAbstract::storeDetectedToCache()
     * @see ScriptDetectorAbstract::detectBySettings()
     * @see ScriptDetectorAbstract::getCacheFilePath()
     */
    public function testUnableToCreateDirectory(bool $throwOnException): void {
        if ($throwOnException) {
            $this->expectExceptionObject(
                new RuntimeException("Unable to create a directory '/asd' for the cache file: '/asd/zxc'"),
            );
        }

        $detector = (new ScriptDetectorMock($throwOnException))
            ->searchDirectory(__DIR__ . '/../ScriptClasses/Red/RedLeft')
            ->cacheFilePath('/asd/zxc');

        set_error_handler(function (int $errorNumber, string $errorMessage) {
            if (str_contains($errorMessage, 'mkdir(): Permission denied')) {
                return true;
            }

            return false;
        });
        try {
            // This assertion should happen only if exceptions are disabled.
            assertSame(
                [realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft/Something2.php')],
                $detector->getDetectedData(),
            );
        } finally {
            restore_error_handler();
        }

        // If exceptions are disabled, then the detection happens silently without a cache file creation.
        assertFileDoesNotExist('/asd/zxc');
        // Ensure the stored path is not damaged after failed processing:
        assertSame('/asd/zxc', $detector->getCacheFilePath());
    }

    #[DataProvider('provideThrowOnException')]
    /**
     * Tests cache raw contents encoding failure.
     *
     * @see ScriptDetectorAbstract::storeDetectedToCache()
     * @see ScriptDetectorAbstract::getDataToStoreInCache()
     * @see ScriptDetectorAbstract::detectBySettings()
     */
    public function testStoringInvalidData(bool $throwOnException): void {
        if ($throwOnException) {
            $this->expectExceptionObject(
                new RuntimeException(
                    sprintf(
                        "Unable to create JSON contents for the cache file '%s': %s",
                        static::CACHE_FILE_RELATIVE_PATH,
                        'Malformed UTF-8 characters, possibly incorrectly encoded',
                    ),
                ),
            );
        }

        $detector = $this->getMockBuilder(ScriptDetectorMock::class)
            ->enableOriginalConstructor()
            ->onlyMethods(['getDataToStoreInCache'])
            ->setConstructorArgs(['throwOnException' => $throwOnException])
            ->getMock();
        // Let's modify this mock so it returns invalid data for `json_encode()`:
        $detector
            ->expects($this->once())
            ->method('getDataToStoreInCache')
            ->willReturn(['☀'[0]]); // We put here a part (a single byte) of utf-8 multibyte symbol.

        // This assertion should happen only if exceptions are disabled.
        assertSame(
            [
                realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft/Something2.php'),
            ],
            $detector
                ->searchDirectory(__DIR__ . '/../ScriptClasses/Red/RedLeft')
                ->cacheFilePath(static::CACHE_FILE_RELATIVE_PATH)
                ->getDetectedData(),
        );
    }

    #[DataProvider('provideThrowOnException')]
    /**
     * Tests failed attempts to write into a cache file (for instance, no access).
     *
     * @see ScriptDetectorAbstract::storeDetectedToCache()
     * @see ScriptDetectorAbstract::detectBySettings()
     */
    public function testUnableToWriteIntoFile(bool $throwOnException): void {
        if ($throwOnException) {
            $this->expectExceptionObject(
                new RuntimeException("Unable to write data into the cache file: '/root/asd'"),
            );
        }

        $detector = (new ScriptDetectorMock($throwOnException))
            ->searchDirectory(__DIR__ . '/../ScriptClasses/Red/RedLeft')
            ->cacheFilePath('/root/asd');

        set_error_handler(function (int $errorNumber, string $errorMessage) {
            if (str_contains($errorMessage, 'Failed to open stream: Permission denied')) {
                return true;
            }

            return false;
        });
        try {
            // This assertion should happen only if exceptions are disabled.
            assertSame(
                [realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft/Something2.php')],
                $detector->getDetectedData(),
            );
        } finally {
            restore_error_handler();
        }

        // If exceptions are disabled, then the detection happens silently without a cache file creation.
        assertFileDoesNotExist('/root/asd');
        // Ensure the stored path is not damaged after failed processing:
        assertSame('/root/asd', $detector->getCacheFilePath());
    }
}
