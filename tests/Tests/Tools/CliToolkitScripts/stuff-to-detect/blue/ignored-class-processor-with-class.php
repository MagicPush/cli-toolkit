<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Script\ScriptLauncher\ScriptLauncher;

final readonly    class ClassProcessorSomething { }

require_once __DIR__ . '/' . '../../../../init-console.php';

(new ScriptLauncher())->execute();
