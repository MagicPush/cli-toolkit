<?php

declare(strict_types=1);

/**
 * This is an alternative launcher for class scripts (based on {@see ScriptClassAbstract}) to call those directly.
 *
 * How to:
 *      `php execute-class.php 'Your\Class\Script\Fully\Qualified\Name' [class script parameters]`
 * Example:
 *      `php execute-class.php '\MagicPush\CliToolkit\Tools\CliToolkit\ScriptClasses\TerminalFormatterShowcase' --help`
 *
 * Note that in this case the class name must be the first parameter. The rest of parameters may be in any order,
 * according to the library parameters placement rules (see "Parameter types" in {@link ../../docs/features-manual.md}).
 *
 * When you would want to use this "class script caller":
 *  1. You've just created a new class script located outside of the standard launcher detector's scope. At first, you
 *      want to test your new script. And only then you will decide if you should include it into a specific launcher
 *      (or move to a directory that is parsed by your target launcher).
 *  2. You have a few "plumber" scripts that you do not want to appear in the standard launcher's list of available
 *      subcommands. And you do not want to create a separate launcher solely for those "plumber" scripts.
 *  3. Something nasty is happening on your production server right now, but a script that could stop or fix it
 *      actually does not appear in standard launcher within available subcommands for some odd reason.
 */

use MagicPush\CliToolkit\Parametizer\HelpFormatter;
use MagicPush\CliToolkit\Parametizer\ScriptClass\ScriptClassAbstract;

require_once __DIR__ . '/init.php';

$errorFormatter = HelpFormatter::createForStdErr();

$className = $_SERVER['argv'][1] ?? null;
if (null === $className) {
    throw new RuntimeException($errorFormatter->error('Class name is not specified'));
}
if (!class_exists($className)) {
    throw new RuntimeException(
        $errorFormatter->error("'" . $errorFormatter->paramValue($className) . "' is not defined or autoloaded"),
    );
}
if (!is_subclass_of($className, ScriptClassAbstract::class)) {
    throw new RuntimeException(
        $errorFormatter->error(
            "'" . $errorFormatter->paramValue($className) . "' is not a subclass of "
                . $errorFormatter->helpNote(ScriptClassAbstract::class),
        ),
    );
}

unset($_SERVER['argv'][1]);
$_SERVER['argv'] = array_values($_SERVER['argv']);
$_SERVER['argc']--;

$configBuilder = $className::getConfigBuilder();
$configBuilder
    ->getConfig()
    ->scriptName(
        basename($_SERVER['argv'][0])
            . ' ' . HelpFormatter::createForStdOut()->paramValue("'{$className}'"),
    );

(new $className($configBuilder->run()))
    ->execute();
