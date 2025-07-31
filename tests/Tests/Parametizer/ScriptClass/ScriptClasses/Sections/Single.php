<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptClass\ScriptClasses\Sections;

use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptClassAbstract;

class Single extends ScriptClassAbstract {
    public static function getNameSections(): array {
        return [];
    }


    public function execute(): void { }
}
