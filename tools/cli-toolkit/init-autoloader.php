<?php
/*
 * This autoloader hack is needed only for the local launcher script - the classes utilized by the launcher are not
 * autoloaded by composer settings. That is done intentionally to prevent "plumber" classes to be considered
 * as the library re-usable code. (The same stuff is done for /tests/* classes).
 * If you want to change it, you can always add relevant PSR-4 entries to your `composer.json`.
 */

declare(strict_types=1);

use MagicPush\CliToolkit\Tools\CliToolkit\Classes\AutoloadDetector;

require_once __DIR__ . '/Classes/AutoloadDetector.php';

AutoloadDetector::detectAndRequire();

use Composer\Autoload\ClassLoader;

$composerLoader = new ClassLoader();
$composerLoader->addPsr4('MagicPush\\CliToolkit\\Tools\\CliToolkit\\', [__DIR__]);
$composerLoader->register();
