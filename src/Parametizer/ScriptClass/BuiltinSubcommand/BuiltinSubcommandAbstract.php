<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\ScriptClass\BuiltinSubcommand;

use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptClassAbstract;
use Override;

abstract class BuiltinSubcommandAbstract extends ScriptClassAbstract {
    #[Override]
    public static function isAvailableByDetector(): bool {
        return false;
    }
}
