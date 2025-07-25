<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Tools\CliToolkitScripts;

use MagicPush\CliToolkit\Tests\Tests\TestCaseAbstract;

abstract class CliToolkitScriptTestAbstract extends TestCaseAbstract {
    protected const string LAUNCHER_PATH = __DIR__ . '/' . '../../../../tools/cli-toolkit/launcher.php';

    protected const string GENERATED_DIRECTORY_PATH = __DIR__ . '/generated';


    protected function setUp(): void {
        parent::setUp();

        require_once __DIR__ . '/' . '../../../../tools/cli-toolkit/init-autoloader.php';

        static::removeDirectoryRecursively(static::GENERATED_DIRECTORY_PATH);
    }
}
