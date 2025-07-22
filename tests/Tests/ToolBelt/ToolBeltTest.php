<?php

declare(strict_types=1);

use MagicPush\CliToolkit\ToolBelt;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function PHPUnit\Framework\assertSame;

class ToolBeltTest extends TestCase {
    #[DataProvider('provideShortClassName')]
    /**
     * Tests how different types of full class names are treated.
     *
     * @see ToolBelt::getClassShortName()
     */
    public function testShortClassName(string $fullyQualifiedName, string $expectedShortName): void {
        assertSame($expectedShortName, ToolBelt::getClassShortName($fullyQualifiedName));
    }

    /**
     * @return array[]
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public static function provideShortClassName(): array {
        return [
            'fq-name' => [
                'fullyQualifiedName' => \MagicPush\CliToolkit\Tests\Tests\ToolBelt\Classes\Something::class,
                'expectedShortName'  => 'Something',
            ],
            'no-namespace' => [
                'fullyQualifiedName' => AnotherClassNoNamespace::class,
                'expectedShortName'  => 'AnotherClassNoNamespace',
            ],
        ];
    }
}
