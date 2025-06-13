<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests;

use LogicException;
use MagicPush\CliToolkit\Parametizer\Exception\ConfigException;
use MagicPush\CliToolkit\Parametizer\Parametizer;
use MagicPush\CliToolkit\Tests\Utils\CliProcess;
use PHPUnit\Framework\TestCase;

use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertStringContainsString;

abstract class TestCaseAbstract extends TestCase {
    protected static function assertNoErrorsOutput(string $scriptPath, string $parametersString = ''): CliProcess {
        $command = 'php ' . escapeshellarg($scriptPath);
        if ('' !== $parametersString) {
            $command .= " {$parametersString}";
        }
        $result = new CliProcess($command);

        // The heading space before a script name is needed for a script being clickable in PhpStorm console log
        // as a script file link.
        $failedAssertMessage = " {$scriptPath}" . PHP_EOL
            . "COMMAND: {$command}" . PHP_EOL
            . "Unexpected error output: {$result->getStdErr()}";
        assertSame(0, $result->getExitCode(), $failedAssertMessage);
        assertSame('', $result->getStdErr(), $failedAssertMessage);

        return $result;
    }

    /**
     * Asserts that an error message was printed in {@see STDERR} (without a stack trace) during a script execution.
     */
    protected static function assertExecutionErrorOutput(
        string $scriptPath,
        string $expectedErrorOutputSubstring,
        string $parametersString = '',
    ): CliProcess {
        return static::assertAnyErrorOutput(
            $scriptPath,
            $expectedErrorOutputSubstring,
            $parametersString,
            shouldAssertExitCode: true,
            shouldAssertStdErr: true,
        );
    }

    /**
     * Asserts that a {@see ConfigException} exception was printed out during a script configuration.
     * Exit code may vary thus is not asserted.
     */
    protected static function assertConfigExceptionOutput(
        string $scriptPath,
        string $expectedErrorOutputSubstring,
        string $parametersString = '',
    ): CliProcess {
        return static::assertAnyErrorOutput(
            $scriptPath,
            $expectedErrorOutputSubstring,
            $parametersString,
            exceptionHeaderSubstring: ConfigException::class . ': ',
            // Config exceptions happen before a specific error code is set for the exception handler:
            shouldAssertExitCode: false,
            shouldAssertStdErr: false,
        );
    }

    /**
     * Asserts that a {@see LogicException} exception was printed in {@see STDERR} (without a stack trace)
     * during a script execution.
     */
    protected static function assertExecutionLogicExceptionOutput(
        string $scriptPath,
        string $expectedErrorOutputSubstring,
        string $parametersString = '',
    ): void {
        static::assertAnyErrorOutput(
            $scriptPath,
            $expectedErrorOutputSubstring,
            $parametersString,
            exceptionHeaderSubstring: LogicException::class . ': ',
            shouldAssertExitCode: true,
            shouldAssertStdErr: true,
        );
    }

    /**
     * @param bool $shouldAssertStdErr Otherwise asserts a substring in {@see STDOUT} and {@see STDERR} concatenated.
     *                                 On some configurations unhandled exceptions are shown
     *                                 in {@see STDOUT} instead of {@see STDERR}.
     */
    protected static function assertAnyErrorOutput(
        string $scriptPath,
        string $expectedErrorOutputSubstring,
        string $parametersString = '',
        ?string $exceptionHeaderSubstring = null,
        bool $shouldAssertExitCode = true,
        bool $shouldAssertStdErr = true,
    ): CliProcess {
        $command = 'php ' . escapeshellarg($scriptPath);
        if ('' !== $parametersString) {
            $command .= " {$parametersString}";
        }
        $result = new CliProcess($command);

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

        if ($shouldAssertStdErr) {
            $actualContents = $result->getStdErr();
            $assertOutputMessage = "{$assertOutputPrefix}Unexpected STDERR: {$actualContents}";
        } else {
            $actualContents = $result->getStdAll();
            $assertOutputMessage = "{$assertOutputPrefix}Unexpected output: {$actualContents}";
        }
        assertStringContainsString(
            ($exceptionHeaderSubstring ?? '') . $expectedErrorOutputSubstring,
            $actualContents,
            $assertOutputMessage,
        );

        return $result;
    }

    /**
     * Asserts an exact math of `$expectedErrorOutput` with STDERR.
     */
    public static function assertFullErrorOutput(
        string $scriptPath,
        string $expectedErrorOutput,
        string $parametersString,
    ): void {
        $command = 'php ' . escapeshellarg($scriptPath);
        if ('' !== $parametersString) {
            $command .= " {$parametersString}";
        }
        $result = new CliProcess($command);
        $stdErr = $result->getStdErr();

        // The heading space before a script name is needed for a script being clickable in PhpStorm console log
        // as a script file link.
        $assertOutputPrefix = " {$scriptPath}" . PHP_EOL
            . "COMMAND: {$command}" . PHP_EOL;

        assertSame(
            Parametizer::ERROR_EXIT_CODE,
            $result->getExitCode(),
            "{$assertOutputPrefix}Unexpected exit code",
        );

        assertSame($expectedErrorOutput, $stdErr, "{$assertOutputPrefix}Unexpected STDERR: {$stdErr}");
    }
}
