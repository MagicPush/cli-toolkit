<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Tools\CliToolkitScripts;

use MagicPush\CliToolkit\Parametizer\ScriptClass\BuiltinSubcommand\ListSubcommands;
use MagicPush\CliToolkit\Tools\CliToolkit\ScriptClasses\Internal\GenerateMassTestScripts;

use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertMatchesRegularExpression;
use function PHPUnit\Framework\assertSame;

class GenerateMassTestScriptsTest extends CliToolkitScriptTestAbstract {
    private const string GENERATED_MASS_TEST_DIRECTORY_PATH = self::GENERATED_DIRECTORY_PATH . '/GenerateMassTestScriptsTest';


    private string $scriptName;


    protected function setUp(): void {
        parent::setUp();

        $this->scriptName = GenerateMassTestScripts::getScriptName();
    }

    /**
     * Tests the basic "generator suit": generator execution without errors,
     * then if a launcher and a random class script are operable.
     *
     * @see GenerateMassTestScripts::execute()
     */
    public function testSuccess(): void {
        // 1. Launch the generator, ensure no errors and expected empty STDOUT.

        assertSame(
            '',
            static::assertNoErrorsOutput(
                static::LAUNCHER_PATH,
                sprintf('%s --generated-path="%s" 1', $this->scriptName, self::GENERATED_MASS_TEST_DIRECTORY_PATH),
            )
                ->getStdOut(),
        );

        // 2. Ensure the launcher is operable and reflects the expected subcommands count.

        $launcherOutputLines = static::assertNoErrorsOutput(
            self::GENERATED_MASS_TEST_DIRECTORY_PATH . '/mass-test.php',
            ListSubcommands::getScriptName() . ' --slim',
        )
            ->getStdOutAsArray();
        assertCount(
            7,
            $launcherOutputLines,
            'Actual output:' . PHP_EOL . implode(PHP_EOL, $launcherOutputLines) . PHP_EOL,
        );
        assertSame('Stats:', $launcherOutputLines[4]);
        [$scriptName, ] = explode(' ', $launcherOutputLines[2], 2);

        // 3. Ensure a randomly generated script is operable and outputs expected substrings.

        $scriptOutputLines = static::assertNoErrorsOutput(
            self::GENERATED_MASS_TEST_DIRECTORY_PATH . '/mass-test.php',
            $scriptName,
        )
            ->getStdOutAsArray();
        assertCount(
            5,
            $scriptOutputLines,
            'Actual output:' . PHP_EOL . implode(PHP_EOL, $launcherOutputLines) . PHP_EOL,
        );
        assertMatchesRegularExpression(
            '/^\{"[a-z]{3}"\:null,"[a-z]{3}"\:false,"[a-z]{3}"\:null\}$/',
            $scriptOutputLines[0],
        );
    }
}
