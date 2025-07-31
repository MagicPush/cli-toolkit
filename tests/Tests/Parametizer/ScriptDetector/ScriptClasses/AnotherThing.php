<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses;

use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptAbstract;

/**
 * This class is always ignored because it is not related to {@see ScriptAbstract}.
 */
class AnotherThing extends AnotherThingAbstract {
    public function execute(): void { }
}
