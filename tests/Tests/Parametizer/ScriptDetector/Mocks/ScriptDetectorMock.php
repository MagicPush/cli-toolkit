<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\Mocks;

use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptDetectorAbstract;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\SearchDirectoryContext;
use Override;

/**
 * Contains logic needed for tests only.
 *
 * Must NOT be final, so PHPUnit could mock it further.
 *
 * @method \string[] getDetectedData() [(string) detected file path, ...]
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class ScriptDetectorMock extends ScriptDetectorAbstract {
    /** @var string[] */
    protected array $detectedFilePaths = [];

    // IMPLEMENTATION OF ABSTRACT METHODS:

    #[Override]
    protected function clearMemoryCache(): void {
        $this->detectedFilePaths = [];
    }

    #[Override]
    protected function hasMinimalCustomSearchSettings(): bool {
        // This method should be tested for each child implementation.
        // This mock detector lacks custom processing, so this exact implementation should not be tested.
        return false;
    }

    #[Override]
    protected function processDetectedFileContents(string $filePath, string $fileContents): void {
        // There should be no specific validation or processing for the mock detection result.
        // Let's just store file paths.
        $this->detectedFilePaths[] = $filePath;
    }

    #[Override]
    protected function processCustomDetections(): void {
        // No custom detection is possible for the base class testing.
    }

    #[Override]
    protected function loadDataFromCache(array $dataFromCache): void {
        // Intentionally does nothing.
    }

    #[Override]
    protected function getDataToStoreInCache(): array {
        // Intentionally returns nothing useful.
        return [];
    }

    /**
     * @return string[]
     */
    #[Override]
    protected function getDataProcessedAfterDetection(): array {
        return $this->detectedFilePaths;
    }


    // METHODS NEEDED FOR TESTING:

    /**
     * @return array<string, SearchDirectoryContext> {@see ScriptDetectorAbstract::$searchingDirectories}
     */
    public function _getSearchingDirectories(): array {
        return $this->searchingDirectories;
    }

    /**
     * @return array<string, string> {@see ScriptDetectorAbstract::$excludedDirectoryPaths}
     */
    public function _getExcludedDirectoryPaths(): array {
        return $this->excludedDirectoryPaths;
    }
}
