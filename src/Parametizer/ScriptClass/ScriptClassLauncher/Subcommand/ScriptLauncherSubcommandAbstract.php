<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptClassLauncher\Subcommand;

use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptClassAbstract;
use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptClassLauncher\ScriptClassLauncher;
use Override;

/**
 * Built-in subcommands utilized by {@see ScriptClassLauncher}.
 */
abstract class ScriptLauncherSubcommandAbstract extends ScriptClassAbstract {
    #[Override]
    public static function isAvailableByDetector(): bool {
        return false;
    }

    #[Override]
    public static function getNameSections(): array {
        return array_merge(parent::getNameSections(), ['script-launcher']);
    }
}
