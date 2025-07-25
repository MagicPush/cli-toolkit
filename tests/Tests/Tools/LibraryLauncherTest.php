<?php

declare(strict_types=1);

use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Tests\Tests\TestCaseAbstract;

use function PHPUnit\Framework\assertSame;

final class LibraryLauncherTest extends TestCaseAbstract {
    /**
     * Tests expected availability of the stock launcher commands.
     *
     * @see ../../../tools/cli-toolkit/launcher.php
     */
    public function testLauncherAvailableCommands(): void {
        assertSame(
            <<<TEXT
            help                                               Outputs a help page for a specified subcommand.
            list                                               Shows available subcommands.
            cli-toolkit:generate:autocompletion-script         Generates a file with Bash completion scripts.
            cli-toolkit:generate:environment-config-file       Generates an environment config file with all possible settings.
            cli-toolkit:internal:generate-mass-test-scripts    Generates dummy scripts for performance testing.
            cli-toolkit:terminal-formatter-showcase            Shows examples of formatting a substring in a terminal.

            TEXT,
            static::assertNoErrorsOutput(
                __DIR__ . '/../../../tools/cli-toolkit/launcher.php',
                Config::PARAMETER_NAME_LIST . ' --slim',
            )
                ->getStdOut(),
        );
    }
}
