<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses;

use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptAbstract;

final class SomethingZero extends ScriptAbstract {
    public static function getScriptInnerName(): string {
        return 'something';
    }


    public function execute(): void { }
}
