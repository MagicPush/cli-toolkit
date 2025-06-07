<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tools\CliToolkit\ScriptClasses;

use MagicPush\CliToolkit\Parametizer\Script\ScriptAbstract;

abstract class CliToolkitScriptAbstract extends ScriptAbstract {
    public static function getNameSections(): array {
        return ['cli-toolkit'];
    }
}
