<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\Mocks;

use MagicPush\CliToolkit\Parametizer\Script\ScriptAbstract;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptClassDetector;

/**
 * Contains logic needed for tests only.
 */
class ScriptClassDetectorMock extends ScriptClassDetector {
    /**
     * @return array<ScriptAbstract|string, ScriptAbstract|string> {@see static::$searchedFQClassNames}
     */
    public function getSearchedFQClassNames(): array {
        return $this->searchedFQClassNames;
    }

    /**
     * @return ScriptAbstract[]|string[] (string) Fully Qualified class name that extends {@see ScriptAbstract}
     */
    public function getDetectedFQClassNames(): array {
        return $this->detectedFQClassNames;
    }
}
