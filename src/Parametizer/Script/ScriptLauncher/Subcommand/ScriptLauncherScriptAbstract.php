<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Script\ScriptLauncher\Subcommand;

use MagicPush\CliToolkit\Parametizer\Script\ScriptAbstract;

abstract class ScriptLauncherScriptAbstract extends ScriptAbstract {
    public static function getNameSections(): array {
        return array_merge(parent::getNameSections(), ['script-launcher']);
    }
}
