<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses;

use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptClassAbstract;

final class SomethingZero extends ScriptClassAbstract {
    public static function getScriptInnerName(): string {
        return 'something';
    }


    public function execute(): void { }
}
