<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector;

use MagicPush\CliToolkit\Parametizer\Script\ScriptAbstract;
use MagicPush\CliToolkit\Parametizer\Script\ScriptDetector\ScriptDetector;
use MagicPush\CliToolkit\Parametizer\Script\ScriptDetector\SearchDirectoryContext;

/**
 * Contains logic needed for tests only.
 */
class ScriptDetectorMock extends ScriptDetector {
    /**
     * @return array<ScriptAbstract|string, ScriptAbstract|string> {@see ScriptDetectorMock::$searchedFQClassNames}
     */
    public function getSearchedFQClassNames(): array {
        return $this->searchedFQClassNames;
    }

    /**
     * @return array<string, SearchDirectoryContext> {@see ScriptDetectorMock::$searchingDirectories}
     */
    public function getSearchingDirectories(): array {
        return $this->searchingDirectories;
    }

    /**
     * @return array<string, string> {@see ScriptDetectorMock::$excludedDirectoryPaths}
     */
    public function getExcludedDirectoryPaths(): array {
        return $this->excludedDirectoryPaths;
    }
}
