<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\ScriptClass\BuiltinSubcommand;

use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptAbstract;
use Override;

abstract class BuiltinSubcommandAbstract extends ScriptAbstract {
    #[Override]
    public static function isAvailableByDetector(): bool {
        return false;
    }
}
