<?php declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\Callback;

use MagicPush\CliToolkit\Parametizer\CliRequest\CliRequestProcessor;
use MagicPush\CliToolkit\Parametizer\Config\Parameter\ParameterAbstract;
use MagicPush\CliToolkit\Tests\Tests\TestCaseAbstract;
use PHPUnit\Framework\Attributes\DataProvider;

use function PHPUnit\Framework\assertSame;

class CallbackTest extends TestCaseAbstract {
    // Now it's impossible to test if an invalid callable is provided - internal type check renders a fatal error.

    #[DataProvider('provideCallbackConfigs')]
    /**
     * Tests valid callback configs.
     *
     * @covers ParameterAbstract::runCallback()
     */
    public function testCallbackConfig(string $script, string $argument, string $standardOutput): void {
        $escapedArgument = escapeshellarg($argument);

        $result = static::assertNoErrorsOutput($script, $escapedArgument);
        assertSame($standardOutput, $result->getStdOut());
    }

    /**
     * @return array[]
     */
    public static function provideCallbackConfigs(): array {
        return [
            'null-disables-callback' => [
                'script'         => __DIR__ . '/scripts/disabled.php',
                'argument'       => 'value',
                'standardOutput' => '',
            ],
            'named-function' => [
                'script'         => __DIR__ . '/scripts/named-function.php',
                'argument'       => 'value',
                'standardOutput' => 'value',
            ],
            'lambda-function' => [
                'script'         => __DIR__ . '/scripts/lambda-function.php',
                'argument'       => 'value',
                'standardOutput' => "The parsed value is: 'value' | value",
            ],
        ];
    }

    /**
     * Tests if a callback is executed after a validator for each parameter (an argument and an option).
     *
     * @covers CliRequestProcessor::registerArgument()
     * @covers CliRequestProcessor::registerOption()
     */
    public function testCallbackIsExecutedAfterValidator(): void {
        $script = __DIR__ . '/scripts/callback-after-validator.php';

        $result = static::assertParseErrorOutput(
            $script,
            "Incorrect value 'test' for argument <arg>. Only digits are allowed for <arg>",
            'test --opt=test',
        );
        // Here we have strings in STDERR only, because no callback was able to execute.
        assertSame('', $result->getStdOut());

        $result = static::assertParseErrorOutput(
            $script,
            "Incorrect value 'test' for option --opt. Only digits are allowed for --opt",
            '10 --opt=test',
        );
        // ... But in this case the <arg> value was validated successfully, so it's callback was executed after that.
        assertSame("<arg>: '10'" . PHP_EOL, $result->getStdOut());

        $result = static::assertNoErrorsOutput($script, '10 --opt=20');
        assertSame(
            [
                "<arg>: '10'",  // Callback executed for <arg>.
                "--opt: '20'",  // Callback executed for --opt.
            ],
            $result->getStdOutAsArray(),
        );
    }
}
