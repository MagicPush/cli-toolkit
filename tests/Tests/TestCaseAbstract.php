<?php declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests;

use MagicPush\CliToolkit\Parametizer\Exception\ConfigException;
use MagicPush\CliToolkit\Parametizer\Parametizer;
use MagicPush\CliToolkit\Tests\utils\CliProcess;
use PHPUnit\Framework\TestCase;

use function PHPUnit\Framework\assertMatchesRegularExpression;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertStringContainsString;

abstract class TestCaseAbstract extends TestCase {
    protected static function assertNoErrorsOutput(string $script, string $parametersString = ''): CliProcess {
        $command = 'php ' . escapeshellarg($script);
        if ($parametersString) {
            $command .= " {$parametersString}";
        }
        $result = new CliProcess($command);

        // The heading space before a script name is needed for a script being clickable in PhpStorm console log
        // as a script file link.
        $failedAssertMessage = " {$script}" . PHP_EOL
            . "COMMAND: {$command}" . PHP_EOL
            . "Unexpected error output: {$result->getStdErr()}";
        assertSame(0, $result->getExitCode(), $failedAssertMessage);
        assertSame('', $result->getStdErr(), $failedAssertMessage);

        return $result;
    }

    /**
     * Asserts that an error message was printed without a stack trace.
     */
    protected static function assertParseErrorOutput(
        string $script,
        string $expectedErrorOutput,
        string $parametersString = '',
    ): CliProcess {
        return self::assertAnyErrorOutput(
            $script,
            $expectedErrorOutput,
            $parametersString,
            shouldAssertExitCode: true,
        );
    }

    /**
     * Asserts that a correct exception was printed out, {@see ConfigException}.
     */
    protected static function assertConfigExceptionOutput(
        string $script,
        string $expectedErrorOutput,
        string $parametersString = '',
    ): void {
        self::assertAnyErrorOutput(
            $script,
            $expectedErrorOutput,
            $parametersString,
            exceptionHeaderSubstring: ConfigException::class . ': ',
        );
    }

    private static function assertAnyErrorOutput(
        string $script,
        string $expectedErrorOutput,
        string $parametersString,
        ?string $exceptionHeaderSubstring = null,
        bool $shouldAssertExitCode = false,
    ): CliProcess {
        $command = 'php ' . escapeshellarg($script);
        if ($parametersString) {
            $command .= " {$parametersString}";
        }
        $result = new CliProcess($command);

        // The heading space before a script name is needed for a script being clickable in PhpStorm console log
        // as a script file link.
        $assertOutputPrefix = " {$script}" . PHP_EOL
            . "COMMAND: {$command}" . PHP_EOL;

        if ($shouldAssertExitCode) {
            assertSame(
                Parametizer::ERROR_EXIT_CODE,
                $result->getExitCode(),
                "{$assertOutputPrefix}Unexpected exit code",
            );
        }

        $assertOutputMessage = "{$assertOutputPrefix}Unexpected error output: {$result->getStdErr()}";
        if ($exceptionHeaderSubstring) {
            assertStringContainsString(
                $exceptionHeaderSubstring . $expectedErrorOutput,
                $result->getStdErr(),
                $assertOutputMessage,
            );
        } else {
            assertSame($expectedErrorOutput . PHP_EOL, $result->getStdErr(), $assertOutputMessage);
        }

        return $result;
    }

    /**
     * @param string[] $helpSubstrings
     */
    public static function assertParseErrorOutputWithHelp(
        string $script,
        string $expectedErrorOutput,
        string $parametersString,
        array $helpSubstrings,
    ): void {
        $command = 'php ' . escapeshellarg($script);
        if ($parametersString) {
            $command .= " {$parametersString}";
        }
        $result = new CliProcess($command);
        $stdErr = $result->getStdErr();
        $stdOut = $result->getStdOut();

        // The heading space before a script name is needed for a script being clickable in PhpStorm console log
        // as a script file link.
        $assertOutputPrefix = " {$script}" . PHP_EOL
            . "COMMAND: {$command}" . PHP_EOL;

        assertSame(
            Parametizer::ERROR_EXIT_CODE,
            $result->getExitCode(),
            "{$assertOutputPrefix}Unexpected exit code",
        );

        assertSame(
            $expectedErrorOutput . PHP_EOL,
            $stdErr,
            "{$assertOutputPrefix}Unexpected error output: {$stdErr}",
        );

        $assertHelpMessage = "{$assertOutputPrefix}Unexpected standard output: {$stdOut}";
        assertMatchesRegularExpression('/ +\-\-help +Show full help page\./', $stdOut, $assertHelpMessage);
        if ($helpSubstrings) {
            foreach ($helpSubstrings as $helpSubstring) {
                assertStringContainsString($helpSubstring, $stdOut, $assertHelpMessage);
            }
        }
    }
}
