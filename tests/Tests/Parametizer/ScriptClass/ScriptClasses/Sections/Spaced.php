<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptClass\ScriptClasses\Sections;

use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptAbstract;

class Spaced extends ScriptAbstract {
    public static function getNameSections(): array {
        return ['second-and-third-sections', '', ' 	' . PHP_EOL, 'are'];
    }


    public function execute(): void { }
}
