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
        if ($parametersString) {
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
     * Asserts that an error message was printed without a stack trace.
     */
    protected static function assertParseErrorOutput(
        string $scriptPath,
        string $expectedErrorOutputSubstring,
        string $parametersString = '',
    ): CliProcess {
        return self::assertAnyErrorOutput(
            $scriptPath,
            $expectedErrorOutputSubstring,
            $parametersString,
            shouldAssertExitCode: true,
        );
    }

    /**
     * Asserts that a {@see ConfigException} exception was printed out. Exit code may vary thus is not asserted.
     */
    protected static function assertConfigExceptionOutput(
        string $scriptPath,
        string $expectedErrorOutputSubstring,
        string $parametersString = '',
    ): void {
        self::assertAnyErrorOutput(
            $scriptPath,
            $expectedErrorOutputSubstring,
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
        string $scriptPath,
        string $expectedErrorOutputSubstring,
        string $parametersString = '',
    ): void {
        self::assertAnyErrorOutput(
            $scriptPath,
            $expectedErrorOutputSubstring,
            $parametersString,
            exceptionHeaderSubstring: LogicException::class . ': ',
            shouldAssertExitCode: true,
        );
    }

    protected static function assertAnyErrorOutput(
        string $scriptPath,
        string $expectedErrorOutputSubstring,
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

        $assertOutputMessage = "{$assertOutputPrefix}Unexpected STDERR: {$stdErr}";
        assertStringContainsString(
            ($exceptionHeaderSubstring ?? '') . $expectedErrorOutputSubstring,
            $stdErr,
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
        if ($parametersString) {
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
