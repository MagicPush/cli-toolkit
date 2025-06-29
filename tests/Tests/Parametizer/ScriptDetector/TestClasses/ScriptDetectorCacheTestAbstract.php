<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\TestClasses;

use function PHPUnit\Framework\assertNotFalse;
use function PHPUnit\Framework\assertTrue;

class ScriptDetectorCacheTestAbstract extends ScriptDetectorTestAbstract {
    protected const string GENERATED_DIRECTORY_PATH = __DIR__ . '/../generated';

    /** The path should contain 2+ directories to test that directories are created recursively. */
    protected const string CACHE_FILE_RELATIVE_PATH = self::GENERATED_DIRECTORY_PATH . '/detector-cache/detector-cache.json';


    protected function setUp(): void {
        parent::setUp();

        static::removeDirectoryRecursively(static::GENERATED_DIRECTORY_PATH);
    }

    protected function createCacheFile(string $contents): void {
        assertTrue(mkdir(dirname(static::CACHE_FILE_RELATIVE_PATH), recursive: true));
        assertNotFalse(file_put_contents(static::CACHE_FILE_RELATIVE_PATH, $contents));
    }
}
