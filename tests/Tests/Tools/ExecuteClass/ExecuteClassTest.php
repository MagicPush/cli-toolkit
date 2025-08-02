<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Tools\ExecuteClass;

use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Tests\Tests\TestCaseAbstract;
use MagicPush\CliToolkit\Tests\Tests\Tools\ExecuteClass\Classes\AnotherThing;
use MagicPush\CliToolkit\Tests\Tests\Tools\ExecuteClass\Classes\Something;
use MagicPush\CliToolkit\Tests\Tests\Tools\ExecuteClass\Classes\SomethingAbstract;
use PHPUnit\Framework\Attributes\DataProvider;

use SomeClassNoNamespace;

use function PHPUnit\Framework\assertSame;

final class ExecuteClassTest extends TestCaseAbstract {
    private const string EXECUTOR_SCRIPT_PATH = __DIR__ . '/scripts/run-with-loaded-test-classes.php';


    #[DataProvider('provideSuccessfulClassLaunches')]
    /**
     * Tests that valid classes are executed as expected.
     *
     * @see ../../../../tools/cli-toolkit/execute-class.php
     */
    public function testSuccessfulClassLaunches(string $parametersString, string $expectedOutput): void {
        assertSame(
            $expectedOutput,
            static::assertNoErrorsOutput(self::EXECUTOR_SCRIPT_PATH, $parametersString)
                ->getStdOut(),
        );
    }

    /**
     * @return array[]
     */
    public static function provideSuccessfulClassLaunches(): array {
        return [
            'some-class'         => [
                'parametersString' => sprintf("'%s' --%s", Something::class, Config::PARAMETER_NAME_HELP),
                'expectedOutput'   => <<<TEXT

                      Does something in all child classes.

                      This exact class processes different parameter types.

                    USAGE

                      run-with-loaded-test-classes.php 'MagicPush\CliToolkit\Tests\Tests\Tools\ExecuteClass\Classes\Something' [-f] [--flag] [--option=… | -o …] <array-argument>

                    OPTIONS

                            --help       Show full help page.

                      -f,   --flag

                      -o …, --option=…

                    ARGUMENTS

                      <array-argument>   (multiple values allowed)
                      (required)


                    TEXT,
            ],
            'no-namespace'       => [
                'parametersString' => sprintf("'%s' --%s", SomeClassNoNamespace::class, Config::PARAMETER_NAME_HELP),
                'expectedOutput'   => <<<TEXT

                      This class has no namespace

                    USAGE

                      run-with-loaded-test-classes.php 'SomeClassNoNamespace'

                    OPTIONS

                      --help   Show full help page.


                    TEXT,
            ],

            // Let's ensure that  after the class name all other values are
            'passing-parameters' => [
                'parametersString' => sprintf("'%s' -f a b -oval c", Something::class),
                'expectedOutput'   => <<<TEXT
                    Flag: 1
                    Option: "val"
                    Argument list: a|b|c

                    TEXT,
            ],
            'argument-instead-of-flag' => [
                'parametersString' => sprintf("'%s' -f a b -- -oval c", Something::class),
                'expectedOutput'   => <<<TEXT
                    Flag: 1
                    Option: ""
                    Argument list: a|b|-oval|c

                    TEXT,
            ],
        ];
    }

    #[DataProvider('provideInvalidClasses')]
    /**
     * Tests invalid class entries.
     *
     * @see ../../../../tools/cli-toolkit/execute-class.php
     */
    public function testInvalidClasses(string $parametersString, string $expectedErrorSubstring): void {
        static::assertAnyErrorOutput(
            self::EXECUTOR_SCRIPT_PATH,
            $expectedErrorSubstring,
            $parametersString,
            shouldAssertExitCode: false,
            shouldAssertStdErr: false,
        );
    }

    /**
     * @return array[]
     */
    public static function provideInvalidClasses(): array {
        return [
            'nothing' => [
                'parametersString'       => '',
                'expectedErrorSubstring' => 'Class name is not specified',
            ],
            'undefined' => [
                'parametersString'       => sprintf("Asd --%s", Config::PARAMETER_NAME_HELP),
                'expectedErrorSubstring' => "'Asd' is not defined or autoloaded",
            ],
            'not-a-subclass' => [
                'parametersString'       => sprintf("'%s' --%s", AnotherThing::class, Config::PARAMETER_NAME_HELP),
                'expectedErrorSubstring' => "'" . AnotherThing::class . "' is not a subclass of ",
            ],
            'abstract' => [
                'parametersString'       => sprintf("'%s' --%s", SomethingAbstract::class, Config::PARAMETER_NAME_HELP),
                'expectedErrorSubstring' => 'Cannot instantiate abstract class ' . SomethingAbstract::class,
            ],
        ];
    }
}
