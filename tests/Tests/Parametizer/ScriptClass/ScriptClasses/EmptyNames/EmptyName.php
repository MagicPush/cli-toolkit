<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptClass\ScriptClasses\EmptyNames;

use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptClassAbstract;

class EmptyName extends ScriptClassAbstract {
    public static function getScriptInnerName(): string {
        return '';
    }


    public function execute(): void { }
}
