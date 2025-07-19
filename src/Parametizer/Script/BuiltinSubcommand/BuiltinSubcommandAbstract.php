<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Script\BuiltinSubcommand;

use MagicPush\CliToolkit\Parametizer\Script\ScriptAbstract;
use Override;

abstract class BuiltinSubcommandAbstract extends ScriptAbstract {
    #[Override]
    public static function isAvailableByDetector(): bool {
        return false;
    }
}
