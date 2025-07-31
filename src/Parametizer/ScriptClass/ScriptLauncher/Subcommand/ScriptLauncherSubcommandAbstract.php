<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptLauncher\Subcommand;

use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptAbstract;
use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptLauncher\ScriptLauncher;
use Override;

/**
 * Built-in subcommands utilized by {@see ScriptLauncher}.
 */
abstract class ScriptLauncherSubcommandAbstract extends ScriptAbstract {
    #[Override]
    public static function isAvailableByDetector(): bool {
        return false;
    }

    public static function getNameSections(): array {
        return array_merge(parent::getNameSections(), ['script-launcher']);
    }
}
