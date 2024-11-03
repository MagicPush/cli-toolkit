<?php declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\utils;

use RuntimeException;

class CliProcess {
    final protected const DESCRIPTOR_STDOUT = 1;
    final protected const DESCRIPTOR_STDERR = 2;

    private readonly int $exitCode;
    private readonly string $stdOut;
    private readonly string $stdErr;

    /**
     * Executes the command to launch a process, stores the results in corresponding properties.
     *
     * @param string $command
     */
    public function __construct(string $command)
    {
        $descriptors = [
            static::DESCRIPTOR_STDOUT => ['pipe', 'w'],
            static::DESCRIPTOR_STDERR => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes);
        if (false === $process) {
            throw new RuntimeException(
                "Unable to open pointers for stdout and stderr while executing the command '{$command}'",
            );
        }

        try {
            $stdOut = stream_get_contents($pipes[static::DESCRIPTOR_STDOUT]);
            if (false === $stdOut) {
                throw new RuntimeException("Unable to read STDOUT while executing the command '{$command}'");
            }
            $this->stdOut = $stdOut;

            $stdErr = stream_get_contents($pipes[self::DESCRIPTOR_STDERR]);
            if (false === $stdErr) {
                throw new RuntimeException("Unable to read STDERR while executing the command '{$command}'");
            }
            $this->stdErr = $stdErr;
        } finally {
            foreach ($pipes as $pipe) {
                fclose($pipe);
            }

            $this->exitCode = proc_close($process);
        }
    }

    public function getExitCode(): int
    {
        return $this->exitCode;
    }

    public function getStdOut(): string
    {
        return $this->stdOut;
    }

    /**
     * Returns STDOUT as array of output lines without line breaks at line ends.
     *
     * @return string[]
     */
    public function getStdOutAsArray(): array
    {
        if (empty($this->stdOut)) {
            return [];
        }

        $lines = explode(PHP_EOL, $this->stdOut);
        if ('' === end($lines)) {
            array_pop($lines);
        }

        return $lines;
    }

    public function getStdErr(): string
    {
        return $this->stdErr;
    }
}
