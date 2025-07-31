<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Blue;

use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptClassAbstract;

final class SomethingZeroShadow extends ScriptClassAbstract {
    public static function getScriptInnerName(): string {
        return 'something'; /** The same value as in {@see SomethingZero::getScriptName()} */
    }


    public function execute(): void { }
}
