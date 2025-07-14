<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptClass\ScriptClasses\Sections;

use MagicPush\CliToolkit\Parametizer\Script\ScriptAbstract;

class Triple extends ScriptAbstract {
    public static function getNameSections(): array {
        return ['first', 'second'];
    }


    public function execute(): void { }
}
