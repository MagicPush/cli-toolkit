<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\Mocks;

use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptClassAbstract;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptClassDetector;

/**
 * Contains logic needed for tests only.
 */
final class ScriptClassDetectorMock extends ScriptClassDetector {
    /**
     * @return array<ScriptClassAbstract|string, ScriptClassAbstract|string> {@see static::$searchedFQClassNames}
     */
    public function _getSearchedFQClassNames(): array {
        return $this->searchedFQClassNames;
    }

    /**
     * @return ScriptClassAbstract[]|string[] (string) Fully Qualified class name
     * that extends {@see ScriptClassAbstract}
     */
    public function _getDetectedFQClassNames(): array {
        return $this->detectedFQClassNames;
    }
}
