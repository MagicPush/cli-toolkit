<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector;

use MagicPush\CliToolkit\Tests\Tests\TestCaseAbstract;

abstract class ScriptDetectorTestAbstract extends TestCaseAbstract {
    protected function setUp(): void {
        parent::setUp();

        require_once __DIR__ . '/ScriptClasses/ScriptNoNamespace.php';
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
