<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\Mocks;

use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptDetectorAbstract;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\SearchDirectoryContext;
use Override;

/**
 * Contains logic needed for tests only.
 *
 * @method \string[] getDetectedData() [(string) detected file path, ...]
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class ScriptDetectorMock extends ScriptDetectorAbstract {
    // IMPLEMENTATION OF ABSTRACT METHODS:

    /** @var string[] */
    protected array $detectedFilePaths = [];

    #[Override]
    protected function clearMemoryCache(): void {
        $this->detectedFilePaths = [];
    }

    #[Override]
    protected function hasMinimalSearchSettings(): bool {
        // This method should be tested for each child implementation.
        // This mock detector lacks custom processing, so this exact implementation should not be tested.
        return true;
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

    #[Override]
    /**
     * @return string[]
     */
    protected function getDataProcessedAfterDetection(): array {
        return $this->detectedFilePaths;
    }


    // METHODS NEEDED FOR TESTING:

    /**
     * @return array<string, SearchDirectoryContext> {@see ScriptDetectorAbstract::$searchingDirectories}
     */
    public function getSearchingDirectories(): array {
        return $this->searchingDirectories;
    }

    /**
     * @return array<string, string> {@see ScriptDetectorAbstract::$excludedDirectoryPaths}
     */
    public function getExcludedDirectoryPaths(): array {
        return $this->excludedDirectoryPaths;
    }
}
