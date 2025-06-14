<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tools\CliToolkit\ScriptClasses\Generate;

use MagicPush\CliToolkit\Tools\CliToolkit\ScriptClasses\CliToolkitScriptAbstract;

abstract class CliToolkitGenerateScriptAbstract extends CliToolkitScriptAbstract {
    public static function getNameSections(): array {
        return array_merge(parent::getNameSections(), ['generate']);
    }
}
