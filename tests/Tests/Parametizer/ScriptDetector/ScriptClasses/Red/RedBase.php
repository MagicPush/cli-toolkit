<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red;

use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptAbstract;

abstract class RedBase extends ScriptAbstract {
    public static function getNameSections(): array {
        return array_merge(parent::getNameSections(), ['red']);
    }
}
