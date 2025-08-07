<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tools\CliToolkit\ScriptClasses;

use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptClassAbstract;
use Override;

abstract class CliToolkitScriptAbstract extends ScriptClassAbstract {
    #[Override]
    public static function getNameSections(): array {
        return array_merge(parent::getNameSections(), ['cli-toolkit']);
    }
}
