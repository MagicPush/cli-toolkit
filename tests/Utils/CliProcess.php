<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Utils;

use Exception;
use LogicException;
use RuntimeException;

class CliProcess {
    final protected const int DESCRIPTOR_STDIN  = 0;
    final protected const int DESCRIPTOR_STDOUT = 1;
    final protected const int DESCRIPTOR_STDERR = 2;

    private readonly int    $exitCode;
    private readonly string $stdOut;
    private readonly string $stdErr;

    /**
     * Executes the command to launch a process, stores the results in corresponding properties.
     *
     * @param string[] $stdinLines List of lines for passing to {@see STDIN}. Each element / line represents a single
     *                             input (or an answer for a single question). Later all lines are combined with
     *                             {@see PHP_EOL}, so initially each line separator in an element should be escaped.
     */
    public function __construct(string $command, array $stdinLines = []) {
        $descriptors = [
            static::DESCRIPTOR_STDIN  => ['pipe', 'r'],
            static::DESCRIPTOR_STDOUT => ['pipe', 'w'],
            static::DESCRIPTOR_STDERR => ['pipe', 'w'],
        ];

        /** Here we assume that both current and external processes have the same execution time limit. */
        $maxExecutionTime         = (int) ini_get('max_execution_time');
        $stdInTimeoutSeconds      = 0;
        $stdInTimeoutMicroseconds = 1000 * 500; // 500 ms
        $stdInHangingLimit        = $stdInTimeoutSeconds + $stdInTimeoutMicroseconds / 1e+6;

        /**
         * {@var $stdInTimeoutSeconds} + {@var $stdInTimeoutMicroseconds} must be ~ 2 less
         * than {@see set_time_limit()} in {@see ../Tests/init-console.php}
         * to distinguish more precisely the lack of STDIN from just a too long processing.
         */
        if ($stdInHangingLimit >= $maxExecutionTime) {
            throw new LogicException(
                sprintf(
                    'Invalid execution setup: STDIN hanging timeout (%.3f seconds) must be smaller'
                        . ' (preferably - 2 or more times) than `max_execution_time` PHP setting (%d.0 seconds).',
                    $stdInHangingLimit,
                    $maxExecutionTime,
                ),
            );
        }

        $tsStart = microtime(true);
        $process = proc_open($command, $descriptors, $pipes);
        if (false === $process) {
            throw new RuntimeException(
                "Unable to open pointers for stdout and stderr while executing the command: {$command}",
            );
        }

        try {
            if ($stdinLines) {
                fwrite($pipes[static::DESCRIPTOR_STDIN], implode(PHP_EOL, $stdinLines) . PHP_EOL);
            }

            $pipesRead         = [$pipes[static::DESCRIPTOR_STDIN]];
            $notAnalyzedWrite  = null;
            $notAnalyzedExcept = null;

            if (
                !stream_select(
                    $pipesRead,
                    $notAnalyzedWrite,
                    $notAnalyzedExcept,
                    $stdInTimeoutSeconds,
                    $stdInTimeoutMicroseconds,
                )
            ) {
                $tsDiff = microtime(true) - $tsStart;
                /*
                 * Here we assume that the timeout was caused by lack of STDIN.
                 * `max_execution_time` overtime will be handled later in `finally` section.
                 *
                 * For now even if an external process hanged for too much time, `stream_select()` will stop its work
                 * after its own timeout (before `max_execution_time`). Thus we will know for sure about external
                 * process execution overtime only after `proc_close()` takes place.
                 */
                if ($tsDiff >= $stdInHangingLimit) {
                    throw new RuntimeException(
                        sprintf(
                            'The command was waiting %.3f seconds for input.%sCommand: %s%s'
                                . 'Already provided STDIN parameters: %s%sSTDIN stream listening timeout: %.3f seconds',
                            $tsDiff,
                            PHP_EOL,
                            $command,
                            PHP_EOL,
                            var_export($stdinLines, true),
                            PHP_EOL,
                            $stdInHangingLimit,
                        ),
                    );
                }
            }

            $stdOut = stream_get_contents($pipes[static::DESCRIPTOR_STDOUT]);
            if (false === $stdOut) {
                throw new RuntimeException("Unable to read STDOUT while executing the command: {$command}");
            }
            $this->stdOut = $stdOut;

            $stdErr = stream_get_contents($pipes[self::DESCRIPTOR_STDERR]);
            if (false === $stdErr) {
                throw new RuntimeException("Unable to read STDERR while executing the command: {$command}");
            }
            $this->stdErr = $stdErr;
        } catch (Exception $exception) {
            // Keep it for now. Throw it later in `finally` section at the right moment.
        } finally {
            foreach ($pipes as $pipe) {
                fclose($pipe);
            }

            /*
             * `proc_close()` terminates a process immediately, if a process is actually finished or hanging because of
             * not enough input provided for STDIN. So waiting for `proc_close` to finish for too long is a good enough
             * indicator of a too slow or "endless loop" process.
             *
             * On the contrary, `proc_terminate` always executes immediately - it just sends a signal and then lets
             * the rest of the main program to be executed (works like `kill 'some-process'` shell command).
             * So it's not very handy here to use this function, unless you don't care why exactly a process was
             * terminated prematurely.
             */
            $this->exitCode = proc_close($process);

            // And now let's deal with timeouts and other exceptions...

            $tsDiff = microtime(true) - $tsStart;
            if ($maxExecutionTime > 0 && $tsDiff >= $maxExecutionTime) {
                throw new RuntimeException(
                    sprintf(
                        'The command was processing for too long (%.3f seconds).%sCommand: %s%s'
                            . 'Max execution time: %d.0 seconds',
                        $tsDiff,
                        PHP_EOL,
                        $command,
                        PHP_EOL,
                        $maxExecutionTime,
                    ),
                );
            }

            // Now we can safely throw any exception not related to execution overtime:
            if (isset($exception)) {
                throw $exception;
            }
        }
    }

    public function getExitCode(): int {
        return $this->exitCode;
    }

    public function getStdOut(): string {
        return $this->stdOut;
    }

    /**
     * Returns STDOUT as array of output lines without line breaks at line ends.
     *
     * @return string[]
     */
    public function getStdOutAsArray(): array {
        if (empty($this->stdOut)) {
            return [];
        }

        $lines = explode(PHP_EOL, $this->stdOut);
        if ('' === end($lines)) {
            array_pop($lines);
        }

        return $lines;
    }

    public function getStdErr(): string {
        return $this->stdErr;
    }

    public function getStdAll(): string {
        $stdContents = [
            $this->getStdOut(),
            $this->getStdErr(),
        ];

        $result = '';
        foreach ($stdContents as $contents) {
            if ('' !== $result && '' !== $contents) {
                $result .= PHP_EOL;
            }
            $result .= $contents;
        }

        return $result;
    }
}
