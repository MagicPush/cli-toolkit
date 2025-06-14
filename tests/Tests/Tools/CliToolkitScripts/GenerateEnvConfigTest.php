<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Tools\CliToolkitScripts;

use MagicPush\CliToolkit\Tests\Tests\TestCaseAbstract;
use MagicPush\CliToolkit\Tools\CliToolkit\ScriptClasses\Generate\EnvConfig;
use PHPUnit\Framework\Attributes\DataProvider;

use function PHPUnit\Framework\assertFileExists;
use function PHPUnit\Framework\assertIsArray;
use function PHPUnit\Framework\assertJsonFileEqualsJsonFile;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertTrue;

class GenerateEnvConfigTest extends TestCaseAbstract {
    private const string CONFIG_PATH   = __DIR__ . '/generated/parametizer.env.json';
    private const string LAUNCHER_PATH = __DIR__ . '/' . '../../../../tools/cli-toolkit/launcher.php';


    private string $subcommandName;


    protected function setUp(): void {
        parent::setUp();

        require_once __DIR__ . '/' . '../../../../tools/cli-toolkit/init-autoloader.php';
        $this->subcommandName = EnvConfig::getFullName();

        if (file_exists(self::CONFIG_PATH)) {
            assertTrue(unlink(self::CONFIG_PATH));
        }
    }


    #[DataProvider('provideInvalidPaths')]
    /**
     * Tests various types of invalid directory paths (`path` argument) for a generated file.
     *
     * @see EnvConfig::getConfiguration()
     */
    public function testInvalidPaths(string $path): void {
        self::assertExecutionErrorOutput(
            self::LAUNCHER_PATH,
            'Path should be a readable directory.',
            "{$this->subcommandName} {$path}",
        );
    }

    /**
     * @return array[]
     */
    public static function provideInvalidPaths(): array {
        return [
            'not-existing'    => ['path' => '/non-existing-path'],
            'not-a-directory' => ['path' => __DIR__ . '/generated/.gitignore'],
            'not-readable'    => ['path' => '/root'],
        ];
    }

    /**
     * Tests the generated file contents.
     *
     * @see EnvConfig::execute()
     */
    public function testContents(): void {
        self::assertNoErrorsOutput(
            self::LAUNCHER_PATH,
            "{$this->subcommandName} " . dirname(self::CONFIG_PATH),
        );

        assertFileExists(self::CONFIG_PATH);
        assertJsonFileEqualsJsonFile(__DIR__ . '/expected/parametizer.env.json', self::CONFIG_PATH);
    }

    /**
     * Tests that `--force` flags allows overwriting of previously generated config files.
     *
     * @see EnvConfig::execute()
     */
    public function testForce(): void {
        // Ensure the file initially exists and contains non-JSON data:
        file_put_contents(self::CONFIG_PATH, '!not-a-json');
        assertNull(json_decode(file_get_contents(self::CONFIG_PATH), true));

        // Trying to overwrite the file with correct data... But it fails without `--force`.
        self::assertExecutionErrorOutput(
            self::LAUNCHER_PATH,
            "File '" . realpath(self::CONFIG_PATH) . "' already exists.",
            "{$this->subcommandName} " . dirname(self::CONFIG_PATH),
        );

        // The file still exists:
        assertFileExists(self::CONFIG_PATH);

        // Now when using `--force` the script launch is successful:
        self::assertNoErrorsOutput(
            self::LAUNCHER_PATH,
            "{$this->subcommandName} --force " . dirname(self::CONFIG_PATH),
        );

        // ... And the generated file is overwritten:
        assertFileExists(self::CONFIG_PATH);
        assertIsArray(json_decode(file_get_contents(self::CONFIG_PATH), true));
    }
}
