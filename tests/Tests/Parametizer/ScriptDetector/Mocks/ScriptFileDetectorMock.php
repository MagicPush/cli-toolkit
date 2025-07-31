<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\Mocks;

use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptFileDetector;

/**
 * Contains logic needed for tests only.
 */
final class ScriptFileDetectorMock extends ScriptFileDetector {
    /**
     * @return array<string, string> (string) alias => (string) absolute path
     */
    public function _getDetectedFilePathsByAliases(): array {
        return $this->detectedFilePathsByAliases;
    }
}
