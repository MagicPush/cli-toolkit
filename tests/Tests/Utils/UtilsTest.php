<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Utils;

use MagicPush\CliToolkit\Utils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertSame;

final class UtilsTest extends TestCase {
    /**
     * Tests how different types of full class names are treated.
     *
     * @see Utils::getClassShortName()
     */
    #[DataProvider('provideShortClassName')]
    public function testShortClassName(string $fullyQualifiedName, string $expectedShortName): void {
        assertSame($expectedShortName, Utils::getClassShortName($fullyQualifiedName));
    }

    /**
     * @return array[]
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public static function provideShortClassName(): array {
        return [
            'fq-name' => [
                'fullyQualifiedName' => \MagicPush\CliToolkit\Tests\Tests\Utils\Classes\Something::class,
                'expectedShortName'  => 'Something',
            ],
            'no-namespace' => [
                'fullyQualifiedName' => \AnotherClassNoNamespace::class,
                'expectedShortName'  => 'AnotherClassNoNamespace',
            ],
        ];
    }

    /**
     * Tests the topmost directory detection (with cache).
     *
     * The test does (can) NOT validate the returning value, if the library is actually incorporated into some project.
     *
     * @see Utils::detectTopmostProjectRootDirectory()
     */
    public function testTopmostDirectoryDetection(): void {
        // Initially the cache property is not set:
        assertNull(UtilsMock::_getCachedTopmostProjectRootDirectory());
        // Let's detect the path:
        assertSame(realpath(__DIR__ . '/../../../src/..'), UtilsMock::detectTopmostProjectRootDirectory());
        // Now the cache property should be set with the same path:
        assertSame(realpath(__DIR__ . '/../../../src/..'), UtilsMock::_getCachedTopmostProjectRootDirectory());

        // Now, let's be sure, that subsequent method calls will not re-detect the path,
        // if the cache property is already filled...
        UtilsMock::_setCachedTopmostProjectRootDirectory('/some/non-existing/path');
        assertSame('/some/non-existing/path', UtilsMock::detectTopmostProjectRootDirectory());
    }
}
