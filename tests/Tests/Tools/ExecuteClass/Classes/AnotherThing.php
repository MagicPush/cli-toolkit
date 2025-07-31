<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Tools\ExecuteClass\Classes;

use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptClassAbstract;
use stdClass;

/**
 * This class will not work because it is not related to {@see ScriptClassAbstract}.
 */
class AnotherThing extends stdClass {
    public function execute(): void { }
}
