<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\TestClasses;

use MagicPush\CliToolkit\Tests\Tests\TestCaseAbstract;

abstract class ScriptDetectorTestAbstract extends TestCaseAbstract {
    protected function setUp(): void {
        parent::setUp();

        /*
         * Warning! Data providers launch before `setUp()`. Thus all actual detection should happen in
         * `test*()` methods (not in provider methods). Otherwise you will have to copy the line below at the beginning
         * of each provider that actually calls a detection method.
         */
        require_once __DIR__ . '/../ScriptClasses/SomethingNoNamespace.php';
    }

    /**
     * @return array[]
     */
    public static function provideThrowOnException(): array {
        return [
            'throw-on-exception' => ['throwOnException' => true],
            'ignore-exceptions'  => ['throwOnException' => false],
        ];
    }
}
