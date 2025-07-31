<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tools\CliToolkit\ScriptClasses;

use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptClassAbstract;

abstract class CliToolkitScriptAbstract extends ScriptClassAbstract {
    public static function getNameSections(): array {
        return ['cli-toolkit'];
    }
}
