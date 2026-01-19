<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Tools\CliToolkitScripts\GenerateEnvironmentConfigFile;

use MagicPush\CliToolkit\Tests\Tests\Tools\CliToolkitScripts\CliToolkitScriptTestAbstract;
use MagicPush\CliToolkit\Tools\CliToolkit\ScriptClasses\Generate\EnvironmentConfigFile;

use function PHPUnit\Framework\assertFileExists;
use function PHPUnit\Framework\assertIsArray;
use function PHPUnit\Framework\assertJsonFileEqualsJsonFile;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

final class GenerateEnvironmentConfigFileTest extends CliToolkitScriptTestAbstract {
    /** The path should contain 2+ directories to test that directories are created recursively. */
    private const string CONFIG_PATH = self::GENERATED_DIRECTORY_PATH . '/GenerateEnvironmentConfigFileTest/env-config/parametizer.env.json';


    private string $scriptName;


    protected function setUp(): void {
        parent::setUp();

        $this->scriptName = EnvironmentConfigFile::getScriptName();
    }


    /**
     * Tests a failed attempt to create a directory (no access) for a generated file.
     *
     * @see EnvironmentConfigFile::execute()
     */
    public function testFailToCreateDirectory(): void {
        static::assertExecutionErrorOutput(
            static::LAUNCHER_PATH,
            "Unable to create a directory: '/asd'",
            "{$this->scriptName} /asd",
        );
    }

    /**
     * Tests a failed attempt to generate a file (no access).
     *
     * @see EnvironmentConfigFile::execute()
     */
    public function testFailToWriteIntoFile(): void {
        static::assertExecutionErrorOutput(
            static::LAUNCHER_PATH,
            sprintf("Unable to write into '/root/%s'", basename(self::CONFIG_PATH)),
            "{$this->scriptName} /root",
        );
    }

    /**
     * Tests the generated file contents and the generator output.
     *
     * @see EnvironmentConfigFile::execute()
     */
    public function testSuccess(): void {
        assertSame(
            sprintf(
                'A directory has been created: %1$s%3$s' . 'Environment config file is created: %2$s%3$s',
                /* #1 */ dirname(self::CONFIG_PATH),
                /* #2 */ self::CONFIG_PATH,
                /* #3 */ PHP_EOL,
            ),
            static::assertNoErrorsOutput(
                static::LAUNCHER_PATH,
                "{$this->scriptName} " . dirname(self::CONFIG_PATH),
            )
                ->getStdOut(),
        ) ;

        assertFileExists(self::CONFIG_PATH);
        assertJsonFileEqualsJsonFile(__DIR__ . '/../../parametizer.env.json', self::CONFIG_PATH);
    }

    /**
     * Tests that `--force` flags allows overwriting of previously generated config files.
     *
     * @see EnvironmentConfigFile::execute()
     */
    public function testForce(): void {
        // Ensure the file initially exists and contains non-JSON data:
        $configDirectory = dirname(self::CONFIG_PATH);
        if (!file_exists($configDirectory)) {
            assertTrue(mkdir($configDirectory, recursive: true));
        }
        file_put_contents(self::CONFIG_PATH, '!not-a-json');
        assertNull(json_decode(file_get_contents(self::CONFIG_PATH), true));

        // Trying to overwrite the file with correct data... But it fails without `--force`.
        static::assertExecutionErrorOutput(
            static::LAUNCHER_PATH,
            "File '" . realpath(self::CONFIG_PATH) . "' already exists.",
            "{$this->scriptName} " . dirname(self::CONFIG_PATH),
        );

        // The file still exists:
        assertFileExists(self::CONFIG_PATH);

        // Now when using `--force` the script launch is successful:
        static::assertNoErrorsOutput(
            static::LAUNCHER_PATH,
            "{$this->scriptName} --force " . dirname(self::CONFIG_PATH),
        );

        // ... And the generated file is overwritten:
        assertFileExists(self::CONFIG_PATH);
        assertIsArray(json_decode(file_get_contents(self::CONFIG_PATH), true));
    }
}
