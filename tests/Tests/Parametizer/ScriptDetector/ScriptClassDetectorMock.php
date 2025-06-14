<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector;

use MagicPush\CliToolkit\Parametizer\Script\ScriptAbstract;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptClassDetector;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\SearchDirectoryContext;

/**
 * Contains logic needed for tests only.
 */
class ScriptClassDetectorMock extends ScriptClassDetector {
    /**
     * @return array<ScriptAbstract|string, ScriptAbstract|string> {@see ScriptClassDetectorMock::$searchedFQClassNames}
     */
    public function getSearchedFQClassNames(): array {
        return $this->searchedFQClassNames;
    }

    /**
     * @return array<string, SearchDirectoryContext> {@see ScriptClassDetectorMock::$searchingDirectories}
     */
    public function getSearchingDirectories(): array {
        return $this->searchingDirectories;
    }

    /**
     * @return array<string, string> {@see ScriptClassDetectorMock::$excludedDirectoryPaths}
     */
    public function getExcludedDirectoryPaths(): array {
        return $this->excludedDirectoryPaths;
    }
}
