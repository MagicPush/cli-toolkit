<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red;

use MagicPush\CliToolkit\Parametizer\Script\ScriptAbstract;

// Located within `Red` namespace (and the directory), but does not extend RedBase.
class SomethingX extends ScriptAbstract {
    public function execute(): void { }
}
