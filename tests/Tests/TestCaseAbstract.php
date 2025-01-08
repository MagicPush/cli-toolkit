<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests;

use LogicException;
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
     * Asserts that a {@see ConfigException} exception was printed out. Exit code may vary thus is not asserted.
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
            // Config exceptions happen before a specific error code is set for the exception handler:
            shouldAssertExitCode: false,
        );
    }

    /**
     * Asserts that a {@see LogicException} exception was printed out.
     */
    protected static function assertLogicExceptionOutput(
        string $script,
        string $expectedErrorOutput,
        string $parametersString = '',
    ): void {
        self::assertAnyErrorOutput(
            $script,
            $expectedErrorOutput,
            $parametersString,
            exceptionHeaderSubstring: LogicException::class . ': ',
            shouldAssertExitCode: true,
        );
    }

    protected static function assertAnyErrorOutput(
        string $scriptPath,
        string $expectedErrorOutput,
        string $parametersString = '',
        ?string $exceptionHeaderSubstring = null,
        bool $shouldAssertExitCode = false,
    ): CliProcess {
        $command = 'php ' . escapeshellarg($scriptPath);
        if ($parametersString) {
            $command .= " {$parametersString}";
        }
        $result = new CliProcess($command);
        $stdErr = $result->getStdErr();

        // The heading space before a script name is needed for a script being clickable in PhpStorm console log
        // as a script file link.
        $assertOutputPrefix = " {$scriptPath}" . PHP_EOL
            . "COMMAND: {$command}" . PHP_EOL;

        if ($shouldAssertExitCode) {
            assertSame(
                Parametizer::ERROR_EXIT_CODE,
                $result->getExitCode(),
                "{$assertOutputPrefix}Unexpected exit code",
            );
        }

        $assertOutputMessage = "{$assertOutputPrefix}Unexpected error output: {$stdErr}";
        if ($exceptionHeaderSubstring) {
            assertStringContainsString(
                $exceptionHeaderSubstring . $expectedErrorOutput,
                $stdErr,
                $assertOutputMessage,
            );
        } else {
            assertSame($expectedErrorOutput . PHP_EOL, $stdErr, $assertOutputMessage);
        }

        return $result;
    }

    /**
     * Includes asserting `help` option mention in the generated help output.
     *
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
