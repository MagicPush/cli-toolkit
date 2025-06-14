<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector;

use MagicPush\CliToolkit\Parametizer\Script\ScriptAbstract;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptClassDetector;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\SearchDirectoryContext;
use MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedAbstract;
use MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\ScriptX;
use MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Zcript;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

use function PHPUnit\Framework\assertSame;

class ScriptClassDetectorTest extends ScriptClassDetectorTestAbstract {
    private static function createScriptClassDetector(): ScriptClassDetector {
        return new ScriptClassDetector(throwOnException: true);
    }

    /**
     * @return array[]
     */
    public static function provideInvalidPaths(): array {
        return [
            'empty-string-throw'         => ['throwOnException' => true, 'path' => ''],
            'empty-string-ignore'        => ['throwOnException' => false, 'path' => ''],
            'new-line-character-throw'   => ['throwOnException' => true, 'path' => PHP_EOL],
            'new-line-character-ignore'  => ['throwOnException' => false, 'path' => PHP_EOL],
            'non-existing-throw'         => ['throwOnException' => true, 'path' => 'asd'],
            'non-existing-ignore'        => ['throwOnException' => false, 'path' => 'asd'],
            'non-readable-throw'         => ['throwOnException' => true, 'path' => '/root'],
            'non-readable-ignore'        => ['throwOnException' => false, 'path' => '/root'],
            'non-directory-throw'        => ['throwOnException' => true, 'path' => __DIR__ . '/ScriptClasses/ScriptZero.php'],
            'non-directory-ignore'       => ['throwOnException' => false, 'path' => __DIR__ . '/ScriptClasses/ScriptZero.php'],
        ];
    }


    #[DataProvider('provideThrowOnException')]
    /**
     * Tests the case when a detector is not initialized.
     *
     * @see ScriptClassDetector::detectBySettings()
     */
    public function testNoSearchSettings(bool $throwOnException): void {
        if ($throwOnException) {
            $this->expectExceptionObject(new RuntimeException('There are no search settings specified.'));
        }

        // This assertion should happen only if no exception is thrown during the detector object's setup:
        assertSame([], (new ScriptClassDetector($throwOnException))->getClassNamesByScriptNames());
    }

    #[DataProvider('provideSearchAndExclude')]
    /**
     * Tests {@see ScriptAbstract} classes detections.
     *
     * @param array<string, string> $expectedClasses
     * @param array<string, string> $actualClasses
     * @see ScriptClassDetector::scriptClassName()
     * @see ScriptClassDetector::scriptClassNames()
     * @see ScriptClassDetector::searchDirectory()
     * @see ScriptClassDetector::searchDirectories()
     * @see ScriptClassDetector::excludeDirectory()
     * @see ScriptClassDetector::excludeDirectories()
     * @see ScriptClassDetector::detectBySettings()
     */
    public function testSearchAndExclude(array $expectedClasses, array $actualClasses): void {
        assertSame($expectedClasses, $actualClasses);
    }

    /**
     * @return array[]
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public static function provideSearchAndExclude(): array {
        return [
            'exact-script-classes' => [
                'expectedClasses' => [
                    'red:script1' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\Script1',
                    'red:script4' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft22\Script4',
                    'script-x'    => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\ScriptX',
                ],
                'actualClasses' => self::createScriptClassDetector()
                    ->scriptClassName(
                        \MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\Script1::class,
                    )
                    ->scriptClassName(
                        \MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft22\Script4::class,
                    )
                    ->scriptClassName(
                        ScriptX::class,
                    )
                    ->getClassNamesByScriptNames(),
            ],
            'exact-script-classes-arr' => [
                'expectedClasses' => [
                    'red:script1' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\Script1',
                    'red:script4' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft22\Script4',
                    'script-x'    => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\ScriptX',
                ],
                'actualClasses' => self::createScriptClassDetector()
                    ->scriptClassNames([
                        \MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\Script1::class,
                        \MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft22\Script4::class,
                        ScriptX::class,
                    ])
                    ->getClassNamesByScriptNames(),
            ],

            'exact-dirs-recursive-and-not-1' => [
                'expectedClasses' => [
                    'red:script8' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight\Subdirectory\Script8',
                    'red:script7' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight\Script7',
                    'red:script5' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Script5',
                ],
                'actualClasses' => self::createScriptClassDetector()
                    ->searchDirectory(__DIR__ . '/ScriptClasses/Red/RedRight', isRecursive: true)
                    ->searchDirectory(__DIR__ . '/ScriptClasses/Red/RedLeft3', isRecursive: false)
                    ->getClassNamesByScriptNames(),
            ],
            'exact-dirs-recursive-and-not-2' => [
                'expectedClasses' => [
                    'red:script7' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight\Script7',
                    'red:script6' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Subdirectory\Script6',
                    'red:script5' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Script5',
                ],
                'actualClasses' => self::createScriptClassDetector()
                    ->searchDirectory(__DIR__ . '/ScriptClasses/Red/RedRight', isRecursive: false)
                    ->searchDirectory(__DIR__ . '/ScriptClasses/Red/RedLeft3', isRecursive: true)
                    ->getClassNamesByScriptNames(),
            ],
            'exact-dirs-arr-recursive' => [
                'expectedClasses' => [
                    'red:script8' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight\Subdirectory\Script8',
                    'red:script7' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight\Script7',
                    'red:script6' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Subdirectory\Script6',
                    'red:script5' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Script5',
                ],
                'actualClasses' => self::createScriptClassDetector()
                    ->searchDirectories(
                        [
                            __DIR__ . '/ScriptClasses/Red/RedRight',
                            __DIR__ . '/ScriptClasses/Red/RedLeft3',
                        ],
                        isRecursive: true,
                    )
                    ->getClassNamesByScriptNames(),
            ],
            'exact-dirs-arr-non-recursive' => [
                'expectedClasses' => [
                    'red:script7' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight\Script7',
                    'red:script5' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Script5',
                ],
                'actualClasses' => self::createScriptClassDetector()
                    ->searchDirectories(
                        [
                            __DIR__ . '/ScriptClasses/Red/RedRight',
                            __DIR__ . '/ScriptClasses/Red/RedLeft3',
                        ],
                        isRecursive: false,
                    )
                    ->getClassNamesByScriptNames(),
            ],

            'exclude-dirs' => [
                'expectedClasses' => [
                    'red:script4' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft22\Script4',
                    'script-x'    => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\ScriptX',
                    'red:script7' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight\Script7',
                    'red:script6' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Subdirectory\Script6',
                    'red:script5' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Script5',
                    'red:script1' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\Script1',
                ],
                'actualClasses' => self::createScriptClassDetector()
                    ->searchDirectory(__DIR__ . '/ScriptClasses/Red', isRecursive: true)
                    ->excludeDirectory(__DIR__ . '/ScriptClasses/Red/RedLeft')
                    ->excludeDirectory(__DIR__ . '/ScriptClasses/Red/RedLeft2')
                    ->excludeDirectory(__DIR__ . '/ScriptClasses/Red/RedRight/Subdirectory')
                    ->getClassNamesByScriptNames(),
            ],
            'exclude-dirs-arr' => [
                'expectedClasses' => [
                    'red:script4' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft22\Script4',
                    'script-x'    => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\ScriptX',
                    'red:script7' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight\Script7',
                    'red:script6' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Subdirectory\Script6',
                    'red:script5' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Script5',
                    'red:script1' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\Script1',
                ],
                'actualClasses' => self::createScriptClassDetector()
                    ->searchDirectory(__DIR__ . '/ScriptClasses/Red', isRecursive: true)
                    ->excludeDirectories([
                        __DIR__ . '/ScriptClasses/Red/RedLeft',
                        __DIR__ . '/ScriptClasses/Red/RedLeft2',
                        __DIR__ . '/ScriptClasses/Red/RedRight/Subdirectory',
                    ])
                    ->getClassNamesByScriptNames(),
            ],
        ];
    }

    /**
     * Tests detecting or ignoring different special types of classes.
     *
     * @see ScriptClassDetector::detectBySettings()
     */
    public function testAbstractFinalAndNotBaseClass(): void {
        assertSame(
            [
                // The "final" script class:
                'script' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\ScriptZero',

                // The script class without a namespace:
                'script-no-namespace' => 'ScriptNoNamespace',

                /**
                 * The script class extended from {@see ScriptAbstract}, but located in another namespace,
                 * near a related abstract subclass {@see RedAbstract}:
                 */
                'script-x'  => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\ScriptX',

                // Other class(es) detected along the way (not particularly interesting for this test):
                'red:script1' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\Script1',

                /**
                 * {@see RedAbstract} abstract class itself is not detected.
                 *
                 * Classes not connected to {@see ScriptAbstract} are ignored, specifically {@see Zcript} that has
                 * the same methods, but a completely different parent.
                 */
            ],
            self::createScriptClassDetector()
                ->searchDirectory(__DIR__ . '/ScriptClasses', isRecursive: false)
                ->searchDirectory(__DIR__ . '/ScriptClasses/Red', isRecursive: false)
                ->getClassNamesByScriptNames(),
        );
    }

    #[DataProvider('provideExcludeSameOrWiderThanSearch')]
    /**
     * Tests a detection process when a searching directory is excluded by the same or a wider (higher) path.
     *
     * @see ScriptClassDetector::validateSearchingAndExcludedPathsIntersections()
     * @see ScriptClassDetector::detectBySettings()
     */
    public function testExcludeSameOrWiderThanSearch(
        bool $throwOnException,
        bool $isSearchRecursive,
        bool $isExcludedSameAsSearching,
    ): void {
        $excludedPath = $isExcludedSameAsSearching
            ? __DIR__ . '/ScriptClasses/Red/RedLeft3'
            : __DIR__ . '/ScriptClasses';               // ... Otherwise we exclude much "higher/wider" directory.

        if ($throwOnException) {
            $this->expectExceptionObject(
                new RuntimeException(
                    sprintf(
                        "Excluded path '%s' fully excludes searching path '%s'",
                        $excludedPath,
                        realpath(__DIR__ . '/ScriptClasses/Red/RedLeft3'),
                    )
                ),
            );
        }

        // This assertion should happen only if no exception is thrown during the detector object's setup:
        assertSame(
            [], // Nothing should be found in any case.
            (new ScriptClassDetector($throwOnException))
                ->searchDirectory(__DIR__ . '/ScriptClasses/Red/RedLeft3', $isSearchRecursive)
                ->excludeDirectory($excludedPath)
                ->getClassNamesByScriptNames(),
        );
    }

    /**
     * @return array[]
     */
    public static function provideExcludeSameOrWiderThanSearch(): array {
        return [
            'throw-non-recursive-same' => [
                'throwOnException'          => true,
                'isSearchRecursive'         => false,
                'isExcludedSameAsSearching' => false,
            ],
            'throw-non-recursive-wider' => [
                'throwOnException'          => true,
                'isSearchRecursive'         => false,
                'isExcludedSameAsSearching' => true,
            ],
            'throw-recursive-same' => [
                'throwOnException'          => true,
                'isSearchRecursive'         => true,
                'isExcludedSameAsSearching' => false,
            ],
            'throw-recursive-wider' => [
                'throwOnException'          => true,
                'isSearchRecursive'         => true,
                'isExcludedSameAsSearching' => true,
            ],

            'ignore-non-recursive-same' => [
                'throwOnException'          => false,
                'isSearchRecursive'         => false,
                'isExcludedSameAsSearching' => false,
            ],
            'ignore-non-recursive-wider' => [
                'throwOnException'          => false,
                'isSearchRecursive'         => false,
                'isExcludedSameAsSearching' => true,
            ],
            'ignore-recursive-same' => [
                'throwOnException'          => false,
                'isSearchRecursive'         => true,
                'isExcludedSameAsSearching' => false,
            ],
            'ignore-recursive-wider' => [
                'throwOnException'          => false,
                'isSearchRecursive'         => true,
                'isExcludedSameAsSearching' => true,
            ],
        ];
    }

    #[DataProvider('provideExcludeUnrelatedPath')]
    /**
     * Tests cases when an excluded directory is not related to a directory being searched.
     *
     * @param array<string, string> $expectedClasses
     * @see ScriptClassDetector::validateSearchingAndExcludedPathsIntersections()
     */
    public function testExcludeUnrelatedPath(
        bool $throwOnException,
        bool $isExceptionExpected,
        SearchDirectoryContext $searchContext,
        string $excludeDirectory,
        array $expectedClasses,
    ): void {
        if ($isExceptionExpected) {
            $this->expectExceptionObject(
                new RuntimeException(
                    sprintf(
                        "Excluded path '%s' is not related to any of specified searching paths.",
                        realpath($excludeDirectory),
                    ),
                ),
            );
        }

        // This assertion should happen only if no exception is thrown during the detector object's setup:
        assertSame(
            $expectedClasses,
            (new ScriptClassDetector($throwOnException))
                ->searchDirectory($searchContext->normalizedPath, $searchContext->isRecursive)
                ->excludeDirectory($excludeDirectory)
                ->getClassNamesByScriptNames(),
        );
    }

    /**
     * @return array[]
     */
    public static function provideExcludeUnrelatedPath(): array {
        return [
            'throw-recursive-subdirectory' => [
                'throwOnException' => true,
                'isExceptionExpected' => false,
                'searchContext' => new SearchDirectoryContext(
                    normalizedPath: realpath(__DIR__ . '/ScriptClasses/Red/RedRight'),
                    isRecursive: true,
                ),
                'excludeDirectory' => __DIR__ . '/ScriptClasses/Red/RedRight/Subdirectory',
                'expectedClasses' => [
                    // Script8 is excluded successfully.
                    'red:script7' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight\Script7',
                ],
            ],
            'throw-non-recursive-subdirectory' => [
                'throwOnException' => true,
                'isExceptionExpected' => true,
                'searchContext' => new SearchDirectoryContext(
                    normalizedPath: realpath(__DIR__ . '/ScriptClasses/Red/RedRight'),
                    isRecursive: false,
                ),
                'excludeDirectory' => __DIR__ . '/ScriptClasses/Red/RedRight/Subdirectory',
                'expectedClasses' => [
                    // An exception should be thrown.
                    'non-existing-script' => 'Non\Existing\ClassName',
                ],
            ],
            'throw-recursive-unrelated' => [
                'throwOnException' => true,
                'isExceptionExpected' => true,
                'searchContext' => new SearchDirectoryContext(
                    normalizedPath: realpath(__DIR__ . '/ScriptClasses/Red/RedRight'),
                    isRecursive: true,
                ),
                'excludeDirectory' => __DIR__ . '/ScriptClasses/Red/RedLeft3',
                'expectedClasses' => [
                    // An exception should be thrown.
                    'non-existing-script' => 'Non\Existing\ClassName',
                ],
            ],

            'ignore-recursive-subdirectory' => [
                'throwOnException' => false,
                'isExceptionExpected' => false,
                'searchContext' => new SearchDirectoryContext(
                    normalizedPath: realpath(__DIR__ . '/ScriptClasses/Red/RedRight'),
                    isRecursive: true,
                ),
                'excludeDirectory' => __DIR__ . '/ScriptClasses/Red/RedRight/Subdirectory',
                'expectedClasses' => [
                    // Script8 is excluded successfully.
                    'red:script7' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight\Script7',
                ],
            ],
            'ignore-non-recursive-subdirectory' => [
                'throwOnException' => false,
                'isExceptionExpected' => false,
                'searchContext' => new SearchDirectoryContext(
                    normalizedPath: realpath(__DIR__ . '/ScriptClasses/Red/RedRight'),
                    isRecursive: false,
                ),
                'excludeDirectory' => __DIR__ . '/ScriptClasses/Red/RedRight/Subdirectory',
                'expectedClasses' => [
                    // Script8 is not detected because of non-recursive search.
                    'red:script7' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight\Script7',
                ],
            ],
            'ignore-recursive-unrelated' => [
                'throwOnException' => false,
                'isExceptionExpected' => false,
                'searchContext' => new SearchDirectoryContext(
                    normalizedPath: realpath(__DIR__ . '/ScriptClasses/Red/RedRight'),
                    isRecursive: true,
                ),
                'excludeDirectory' => __DIR__ . '/ScriptClasses/Red/RedLeft3',
                'expectedClasses' => [
                    // Script8 is added because of unrelated excluded directory - eventually nothing was excluded.
                    'red:script8' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight\Subdirectory\Script8',
                    'red:script7' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight\Script7',
                ],
            ],
        ];
    }

    #[DataProvider('provideInvalidPaths')]
    /**
     * Tests invalid paths processing for a searching directory.
     *
     * @see ScriptClassDetector::getValidatedRealPath()
     * @see ScriptClassDetector::searchDirectory()
     */
    public function testInvalidPathsSearch(bool $throwOnException, string $path): void {
        if ($throwOnException) {
            $this->expectExceptionObject(
                new RuntimeException('Path should be a readable directory: ' . var_export($path, true)),
            );
        }

        // This assertion should happen only if no exception is thrown during the detector object's setup:
        assertSame(
            [], // Nothing should be found in any case.
            (new ScriptClassDetector($throwOnException))
                ->searchDirectory($path)
                ->getClassNamesByScriptNames(),
        );
    }

    #[DataProvider('provideInvalidPaths')]
    /**
     * Tests invalid paths processing for a searching directory.
     *
     * @see ScriptClassDetector::getValidatedRealPath()
     * @see ScriptClassDetector::excludeDirectory()
     */
    public function testInvalidPathsExclude(bool $throwOnException, string $path): void {
        if ($throwOnException) {
            $this->expectExceptionObject(
                new RuntimeException('Path should be a readable directory: ' . var_export($path, true)),
            );
        }

        // This assertion should happen only if no exception is thrown during the detector object's setup:
        assertSame(
            [
                'red:script8' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight\Subdirectory\Script8',
                'red:script7' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight\Script7',
            ],
            (new ScriptClassDetector($throwOnException))
                ->searchDirectory(__DIR__ . '/ScriptClasses/Red/RedRight', isRecursive: true)
                ->excludeDirectory($path)
                ->getClassNamesByScriptNames(),
        );
    }
}
