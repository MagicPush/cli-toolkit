<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tools\CliToolkit\Scripts;

use MagicPush\CliToolkit\Parametizer\Script\Subcommand\ScriptAbstract;

abstract class CliToolkitScriptAbstract extends ScriptAbstract {
    public static function getNameSections(): array {
        return ['cli-toolkit'];
    }
}
