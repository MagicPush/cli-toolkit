<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptClass;

use MagicPush\CliToolkit\Parametizer\Config\Config;
use MagicPush\CliToolkit\Parametizer\Script\ScriptAbstract;
use MagicPush\CliToolkit\Tests\Tests\TestCaseAbstract;
use PHPUnit\Framework\Attributes\DataProvider;

use function PHPUnit\Framework\assertSame;

class ScriptClassTest extends TestCaseAbstract {
    #[DataProvider('provideEmptyLocalNames')]
    /**
     * Tests that a script local name can not be empty.
     *
     * @see ScriptAbstract::getFullName()
     */
    public function testEmptyLocalNames(string $className): void {
        self::assertConfigExceptionOutput(
            __DIR__ . '/scripts/by-single-name.php',
            "Script '{$className}' >>> Config error: local name can not be empty.",
            "'{$className}'",
        );
    }

    /**
     * @return string[][]
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public static function provideEmptyLocalNames(): array {
        return [
            'empty'       => ['className' => \MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptClass\ScriptClasses\EmptyNames\EmptyName::class],
            'space-chars' => ['className' => \MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptClass\ScriptClasses\EmptyNames\NameOfSpaceChars::class],
        ];
    }

    /**
     * Tests local names automatic generation based on class names.
     *
     * @see ScriptAbstract::getLocalName()
     * @noinspection SpellCheckingInspection
     */
    public function testAutoLocalName(): void {
        assertSame(
            <<<TEXT
            help              Outputs a help page for a specified subcommand.
            list              Shows available subcommands.
            abbr
            abbr-word
            lowercasename
            some-abbr-word
            two-words
            uppercasename
            word-abbr

            TEXT,
            self::assertNoErrorsOutput(
                __DIR__ . '/scripts/local-names.php',
                Config::PARAMETER_NAME_LIST . ' --slim',
            )
                ->getStdOut(),
        );
    }

    /**
     * Tests local names automatic generation based on class names.
     *
     * @see ScriptAbstract::getFullName()
     */
    public function testNamesWithSections(): void {
        assertSame(
            <<<TEXT
            help                                    Outputs a help page for a specified subcommand.
            list                                    Shows available subcommands.
            first:double
            first:second:triple
            second-and-third-sections:are:spaced
            single

            TEXT,
            self::assertNoErrorsOutput(
                __DIR__ . '/scripts/sections.php',
                Config::PARAMETER_NAME_LIST . ' --slim',
            )
                ->getStdOut(),
        );
    }
}
