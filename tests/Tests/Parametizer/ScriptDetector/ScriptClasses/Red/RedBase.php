<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red;

use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptClassAbstract;

abstract class RedBase extends ScriptClassAbstract {
    public static function getNameSections(): array {
        return array_merge(parent::getNameSections(), ['red']);
    }
}
