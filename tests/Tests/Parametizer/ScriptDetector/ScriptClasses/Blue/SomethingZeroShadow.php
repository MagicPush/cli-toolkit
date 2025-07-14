<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Blue;

use MagicPush\CliToolkit\Parametizer\Script\ScriptAbstract;

final class SomethingZeroShadow extends ScriptAbstract {
    public static function getScriptInnerName(): string {
        return 'something'; /** The same value as in {@see SomethingZero::getScriptName()} */
    }


    public function execute(): void { }
}
