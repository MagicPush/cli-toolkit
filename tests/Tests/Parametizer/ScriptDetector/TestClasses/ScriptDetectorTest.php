<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\TestClasses;

use Exception;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptDetectorAbstract;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\SearchDirectoryContext;
use MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\Mocks\ScriptDetectorMock;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

use function PHPUnit\Framework\assertSame;

/**
 * Tests {@see ScriptDetectorAbstract}, thus any child class would suffice here.
 */
class ScriptDetectorTest extends ScriptDetectorTestAbstract {
    #[DataProvider('provideInvalidPaths')]
    /**
     * Tests invalid paths processing for a searching directory.
     *
     * @see ScriptDetectorAbstract::getValidatedRealPath()
     * @see ScriptDetectorAbstract::searchDirectory()
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
            (new ScriptDetectorMock($throwOnException))
                ->searchDirectory($path)
                ->getDetectedData(),
        );
    }

    #[DataProvider('provideInvalidPaths')]
    /**
     * Tests invalid paths processing for a searching directory.
     *
     * @see ScriptDetectorAbstract::getValidatedRealPath()
     * @see ScriptDetectorAbstract::excludeDirectory()
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
                realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedRight/Subdirectory/Something8.php'),
                realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedRight/Something7.php'),
            ],
            (new ScriptDetectorMock($throwOnException))
                ->searchDirectory(__DIR__ . '/../ScriptClasses/Red/RedRight', isRecursive: true)
                ->excludeDirectory($path)
                ->getDetectedData(),
        );
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
            'non-directory-throw'        => ['throwOnException' => true, 'path' => __DIR__ . '/../ScriptClasses/SomethingZero.php'],
            'non-directory-ignore'       => ['throwOnException' => false, 'path' => __DIR__ . '/../ScriptClasses/SomethingZero.php'],
        ];
    }

    #[DataProvider('provideSearchAndExclude')]
    /**
     * Tests files detection in "search VS exclude" directories.
     *
     * @param array[] $expectedDetectionResults
     * @param array[] $actualDetectionResults
     * @see ScriptDetectorAbstract::searchDirectory()
     * @see ScriptDetectorAbstract::searchDirectories()
     * @see ScriptDetectorAbstract::excludeDirectory()
     * @see ScriptDetectorAbstract::excludeDirectories()
     * @see ScriptDetectorAbstract::detectBySettings()
     */
    public function testSearchAndExclude(array $expectedDetectionResults, ScriptDetectorMock $detector): void {
        assertSame($expectedDetectionResults, $detector->getDetectedData());
    }

    /**
     * @return array[]
     */
    public static function provideSearchAndExclude(): array {
        return [
            'exact-dirs-recursive-and-not-1' => [
                'expectedDetectionResults' => [
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedRight/Subdirectory/Something8.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedRight/Something7.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedLeft3/Something5.php'),
                ],
                'detector' => (new ScriptDetectorMock(throwOnException: true))
                    ->searchDirectory(__DIR__ . '/../ScriptClasses/Red/RedRight', isRecursive: true)
                    ->searchDirectory(__DIR__ . '/../ScriptClasses/Red/RedLeft3', isRecursive: false),
            ],
            'exact-dirs-recursive-and-not-2' => [
                'expectedDetectionResults' => [
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedRight/Something7.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedLeft3/Subdirectory/Something6.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedLeft3/Something5.php'),
                ],
                'detector' => (new ScriptDetectorMock(throwOnException: true))
                    ->searchDirectory(__DIR__ . '/../ScriptClasses/Red/RedRight', isRecursive: false)
                    ->searchDirectory(__DIR__ . '/../ScriptClasses/Red/RedLeft3', isRecursive: true),
            ],
            'exact-dirs-arr-recursive' => [
                'expectedDetectionResults' => [
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedRight/Subdirectory/Something8.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedRight/Something7.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedLeft3/Subdirectory/Something6.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedLeft3/Something5.php'),
                ],
                'detector' => (new ScriptDetectorMock(throwOnException: true))
                    ->searchDirectories(
                        [
                            __DIR__ . '/../ScriptClasses/Red/RedRight',
                            __DIR__ . '/../ScriptClasses/Red/RedLeft3',
                        ],
                        isRecursive: true,
                    ),
            ],
            'exact-dirs-arr-non-recursive' => [
                'expectedDetectionResults' => [
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedRight/Something7.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedLeft3/Something5.php'),
                ],
                'detector' => (new ScriptDetectorMock(throwOnException: true))
                    ->searchDirectories(
                        [
                            __DIR__ . '/../ScriptClasses/Red/RedRight',
                            __DIR__ . '/../ScriptClasses/Red/RedLeft3',
                        ],
                        isRecursive: false,
                    ),
            ],

            'exclude-dirs' => [
                'expectedDetectionResults' => [
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedLeft22/Something4.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedBase.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/SomethingX.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedRight/Something7.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/Something1.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedLeft3/Subdirectory/Something6.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedLeft3/Something5.php'),
                ],
                'detector' => (new ScriptDetectorMock(throwOnException: true))
                    ->searchDirectory(__DIR__ . '/../ScriptClasses/Red', isRecursive: true)
                    ->excludeDirectory(__DIR__ . '/../ScriptClasses/Red/RedLeft')
                    ->excludeDirectory(__DIR__ . '/../ScriptClasses/Red/RedLeft2')
                    ->excludeDirectory(__DIR__ . '/../ScriptClasses/Red/RedRight/Subdirectory'),
            ],
            'exclude-dirs-arr' => [
                'expectedDetectionResults' => [
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedLeft22/Something4.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedBase.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/SomethingX.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedRight/Something7.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/Something1.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedLeft3/Subdirectory/Something6.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedLeft3/Something5.php'),
                ],
                'detector' => (new ScriptDetectorMock(throwOnException: true))
                    ->searchDirectory(__DIR__ . '/../ScriptClasses/Red', isRecursive: true)
                    ->excludeDirectories([
                        __DIR__ . '/../ScriptClasses/Red/RedLeft',
                        __DIR__ . '/../ScriptClasses/Red/RedLeft2',
                        __DIR__ . '/../ScriptClasses/Red/RedRight/Subdirectory',
                    ]),
            ],
        ];
    }

    #[DataProvider('provideExcludeSameOrWiderThanSearch')]
    /**
     * Tests a detection process when a searching directory is excluded by the same or a wider (higher) path.
     *
     * @see ScriptDetectorAbstract::validateSearchingAndExcludedPathsIntersections()
     */
    public function testExcludeSameOrWiderThanSearch(
        bool $throwOnException,
        bool $isSearchRecursive,
        bool $isExcludedSameAsSearching,
    ): void {
        $excludedPath = $isExcludedSameAsSearching
            ? realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft3')
            : realpath(__DIR__ . '/../ScriptClasses');              // ... Otherwise we exclude much "higher/wider" directory.

        if ($throwOnException) {
            $this->expectExceptionObject(
                new RuntimeException(
                    sprintf(
                        "Excluded path '%s' fully excludes searching path '%s'",
                        $excludedPath,
                        realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft3'),
                    )
                ),
            );
        }

        // This assertion should happen only if no exception is thrown during the detector object's setup:
        assertSame(
            [], // Nothing should be found in any case.
            (new ScriptDetectorMock($throwOnException))
                ->searchDirectory(__DIR__ . '/../ScriptClasses/Red/RedLeft3', $isSearchRecursive)
                ->excludeDirectory($excludedPath)
                ->getDetectedData(),
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
     * @param array<string, string> $expectedDetectionResult
     * @see ScriptDetectorAbstract::validateSearchingAndExcludedPathsIntersections()
     */
    public function testExcludeUnrelatedPath(
        bool $throwOnException,
        bool $isExceptionExpected,
        SearchDirectoryContext $searchContext,
        string $excludeDirectory,
        array $expectedDetectionResult,
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
            $expectedDetectionResult,
            (new ScriptDetectorMock($throwOnException))
                ->searchDirectory($searchContext->normalizedPath, $searchContext->isRecursive)
                ->excludeDirectory($excludeDirectory)
                ->getDetectedData(),
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
                    normalizedPath: realpath(__DIR__ . '/../ScriptClasses/Red/RedRight'),
                    isRecursive: true,
                ),
                'excludeDirectory' => __DIR__ . '/../ScriptClasses/Red/RedRight/Subdirectory',
                'expectedDetectionResult' => [
                    // Something8 is excluded successfully.
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedRight/Something7.php'),
                ],
            ],
            'throw-non-recursive-subdirectory' => [
                'throwOnException' => true,
                'isExceptionExpected' => true,
                'searchContext' => new SearchDirectoryContext(
                    normalizedPath: realpath(__DIR__ . '/../ScriptClasses/Red/RedRight'),
                    isRecursive: false,
                ),
                'excludeDirectory' => __DIR__ . '/../ScriptClasses/Red/RedRight/Subdirectory',
                'expectedDetectionResult' => [
                    // An exception should be thrown.
                    '/Non/Existing/Script.nope',
                ],
            ],
            'throw-recursive-unrelated' => [
                'throwOnException' => true,
                'isExceptionExpected' => true,
                'searchContext' => new SearchDirectoryContext(
                    normalizedPath: realpath(__DIR__ . '/../ScriptClasses/Red/RedRight'),
                    isRecursive: true,
                ),
                'excludeDirectory' => __DIR__ . '/../ScriptClasses/Red/RedLeft3',
                'expectedDetectionResult' => [
                    // An exception should be thrown.
                    '/Non/Existing/Script.nope',
                ],
            ],

            'ignore-recursive-subdirectory' => [
                'throwOnException' => false,
                'isExceptionExpected' => false,
                'searchContext' => new SearchDirectoryContext(
                    normalizedPath: realpath(__DIR__ . '/../ScriptClasses/Red/RedRight'),
                    isRecursive: true,
                ),
                'excludeDirectory' => __DIR__ . '/../ScriptClasses/Red/RedRight/Subdirectory',
                'expectedDetectionResult' => [
                    // Something8 is excluded successfully.
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedRight/Something7.php'),
                ],
            ],
            'ignore-non-recursive-subdirectory' => [
                'throwOnException' => false,
                'isExceptionExpected' => false,
                'searchContext' => new SearchDirectoryContext(
                    normalizedPath: realpath(__DIR__ . '/../ScriptClasses/Red/RedRight'),
                    isRecursive: false,
                ),
                'excludeDirectory' => __DIR__ . '/../ScriptClasses/Red/RedRight/Subdirectory',
                'expectedDetectionResult' => [
                    // Something8 is not detected because of non-recursive search.
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedRight/Something7.php'),
                ],
            ],
            'ignore-recursive-unrelated' => [
                'throwOnException' => false,
                'isExceptionExpected' => false,
                'searchContext' => new SearchDirectoryContext(
                    normalizedPath: realpath(__DIR__ . '/../ScriptClasses/Red/RedRight'),
                    isRecursive: true,
                ),
                'excludeDirectory' => __DIR__ . '/../ScriptClasses/Red/RedLeft3',
                'expectedDetectionResult' => [
                    // Something8 is added because of unrelated excluded directory - eventually nothing was excluded.
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedRight/Subdirectory/Something8.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedRight/Something7.php'),
                ],
            ],
        ];
    }

    #[DataProvider('provideDuplicateSearchSameRecursion')]
    /**
     * Tests duplicate search paths processing with the same recursion state.
     *
     * @param string[] $expectedResult
     * @see ScriptDetectorAbstract::searchDirectory()
     */
    public function testDuplicateSearchSameRecursion(
        bool $throwOnException,
        bool $isRecursive,
        array $expectedResult,
    ): void {
        if ($throwOnException) {
            $this->expectExceptionObject(
                new RuntimeException(
                    sprintf(
                        "Duplicate searching directory path: %s (raw value: '%s')",
                        realpath(__DIR__ . '/../ScriptClasses/Red/RedRight'),
                        __DIR__ . '/../ScriptClasses/Red/RedRight',
                    ),
                ),
            );
        }

        $detector = (new ScriptDetectorMock($throwOnException))
            ->searchDirectories(
                [
                    realpath(__DIR__ . '/../ScriptClasses/Red/RedRight'),
                    __DIR__ . '/../ScriptClasses/Red/RedLeft',
                    __DIR__ . '/../ScriptClasses/Red/RedRight',             // Duplicate.
                    __DIR__ . '/../ScriptClasses/Red/RedLeft',              // Duplicate.
                ],
                $isRecursive,
            );

        // The assertions below should happen only if no exception is thrown during the detector object's setup.

        // Here we ensure that no duplicate entry is added.
        $expectedFinalSearchingContexts = [
            realpath(__DIR__ . '/../ScriptClasses/Red/RedRight') => new SearchDirectoryContext(
                realpath(__DIR__ . '/../ScriptClasses/Red/RedRight'),
                $isRecursive,
            ),
            realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft')  => new SearchDirectoryContext(
                realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft'),
                $isRecursive,
            ),
        ];
        assertSame(serialize($expectedFinalSearchingContexts), serialize($detector->getSearchingDirectories()));

        assertSame($expectedResult, $detector->getDetectedData());
    }

    /**
     * @return array[]
     */
    public static function provideDuplicateSearchSameRecursion(): array {
        return [
            'throw-recursive' => [
                'throwOnException' => true,
                'isRecursive'      => true,
                'expectedResult'   => [
                    // An exception should be thrown.
                    '/Non/Existing/Script.nope',
                ],
            ],
            'throw-non-recursive' => [
                'throwOnException' => true,
                'isRecursive'      => false,
                'expectedResult'   => [
                    // An exception should be thrown.
                    '/Non/Existing/Script.nope',
                ],
            ],
            'ignore-recursive'  => [
                'throwOnException' => false,
                'isRecursive'      => true,
                'expectedResult'   => [
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedRight/Subdirectory/Something8.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedRight/Something7.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedLeft/Something2.php'),
                ],
            ],
            'ignore-non-recursive'  => [
                'throwOnException' => false,
                'isRecursive'      => false,
                'expectedResult'   => [
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedRight/Something7.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedLeft/Something2.php'),
                ],
            ],
        ];
    }

    #[DataProvider('provideDuplicateSearchDifferentRecursion')]
    /**
     * Tests duplicate search paths processing if different paths have different recursion state - the duplication
     * validation ignores recursion state.
     *
     * @param string[] $expectedResult
     * @see ScriptDetectorAbstract::searchDirectory()
     */
    public function testDuplicateSearchDifferentRecursion(
        bool $throwOnException,
        bool $isFirstRecursive,
        array $expectedResult,
    ): void {
        if ($throwOnException) {
            $this->expectExceptionObject(
                new RuntimeException(
                    sprintf(
                        "Duplicate searching directory path: %s (raw value: '%s')",
                        realpath(__DIR__ . '/../ScriptClasses/Red/RedRight'),
                        __DIR__ . '/../ScriptClasses/Red/RedRight',
                    ),
                ),
            );
        }

        $detector = (new ScriptDetectorMock($throwOnException))
            ->searchDirectory(__DIR__ . '/../ScriptClasses/Red/RedRight', $isFirstRecursive)
            ->searchDirectory(__DIR__ . '/../ScriptClasses/Red/RedRight', !$isFirstRecursive);

        // The assertions below should happen only if no exception is thrown during the detector object's setup.

        // Here we ensure that no duplicate entry is added.
        $expectedFinalSearchingContexts = [
            realpath(__DIR__ . '/../ScriptClasses/Red/RedRight') => new SearchDirectoryContext(
                realpath(__DIR__ . '/../ScriptClasses/Red/RedRight'),
                $isFirstRecursive,
            ),
        ];
        assertSame(serialize($expectedFinalSearchingContexts), serialize($detector->getSearchingDirectories()));

        assertSame($expectedResult, $detector->getDetectedData());
    }

    /**
     * @return array[]
     */
    public static function provideDuplicateSearchDifferentRecursion(): array {
        return [
            'throw-recursive-first' => [
                'throwOnException' => true,
                'isFirstRecursive' => true,
                'expectedResult'   => [
                    // An exception should be thrown.
                    '/Non/Existing/Script.nope',
                ],
            ],
            'throw-recursive-second' => [
                'throwOnException' => true,
                'isFirstRecursive' => false,
                'expectedResult'   => [
                    // An exception should be thrown.
                    '/Non/Existing/Script.nope',
                ],
            ],
            'ignore-recursive-first'  => [
                'throwOnException' => false,
                'isFirstRecursive' => true,
                'expectedResult'   => [
                    // The first search context is considered only, so the classes are detected recursively.
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedRight/Subdirectory/Something8.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedRight/Something7.php'),
                ],
            ],
            'ignore-recursive-second'  => [
                'throwOnException' => false,
                'isFirstRecursive' => false,
                'expectedResult'   => [
                    // The first search context is considered only, so no recursive search happens.
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedRight/Something7.php'),
                ],
            ],
        ];
    }

    #[DataProvider('provideDuplicateSearchSubdirectory')]
    /**
     * Tests the case when one of searching directories is a subdirectory of some another searching directory.
     *
     * @param SearchDirectoryContext[] $inputSearchingContexts
     * @param SearchDirectoryContext[] $expectedFinalSearchingContexts
     * @param string[] $expectedResult
     * @see ScriptDetectorAbstract::searchDirectory()
     */
    public function testDuplicateSearchSubdirectory(
        bool $throwOnException,
        ?Exception $expectedException,
        array $inputSearchingContexts,
        array $expectedFinalSearchingContexts,
        array $expectedResult,
    ): void {
        $detector = new ScriptDetectorMock($throwOnException);

        if ($expectedException) {
            $this->expectExceptionObject($expectedException);
        }

        foreach ($inputSearchingContexts as $searchDirectoryContext) {
            $detector->searchDirectory(
                $searchDirectoryContext->normalizedPath,
                $searchDirectoryContext->isRecursive,
            );
        }

        // The assertions below should happen only if no exception is thrown during the detector object's setup.

        // Here we ensure (mainly, for "wider" recursive search) that only a "wider" directory is stored eventually
        // in a detector - we should not process same directories more than once.
        assertSame(serialize($expectedFinalSearchingContexts), serialize($detector->getSearchingDirectories()));

        assertSame($expectedResult, $detector->getDetectedData());
    }

    /**
     * @return array[]
     */
    public static function provideDuplicateSearchSubdirectory(): array {
        return [
            'recursive-throw-wider-first' => [
                'throwOnException' => true,
                'expectedException' => new RuntimeException(
                    sprintf(
                        "Previously added directory's searching recursive scope '%s'"
                        . " includes just added directory path '%s' (raw value: '%s')",
                        realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft3'),
                        realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft3/Subdirectory'),
                        __DIR__ . '/../ScriptClasses/Red/RedLeft3/Subdirectory',
                    ),
                ),
                'inputSearchingContexts' => [
                    new SearchDirectoryContext(__DIR__ . '/../ScriptClasses/Red/RedLeft3', isRecursive: true),
                    new SearchDirectoryContext(__DIR__ . '/../ScriptClasses/Red/RedRight', true),
                    new SearchDirectoryContext(__DIR__ . '/../ScriptClasses/Red/RedLeft', true),
                    new SearchDirectoryContext(__DIR__ . '/../ScriptClasses/Red/RedLeft3/Subdirectory', true),
                ],
                'expectedFinalSearchingContexts' => [
                    // An exception should be thrown.
                    'non-relevant-context',
                ],
                'expectedResult' => [
                    // An exception should be thrown.
                    '/Non/Existing/Script.nope',
                ],
            ],
            'recursive-throw-wider-last' => [
                'throwOnException' => true,
                'expectedException' => new RuntimeException(
                    sprintf(
                        "Just added directory's searching recursive scope '%s' (raw value: '%s')"
                        . " includes previously added directory path '%s'",
                        realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft3'),
                        __DIR__ . '/../ScriptClasses/Red/RedLeft3',
                        realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft3/Subdirectory'),
                    ),
                ),
                'inputSearchingContexts' => [
                    new SearchDirectoryContext(__DIR__ . '/../ScriptClasses/Red/RedRight', true),
                    new SearchDirectoryContext(__DIR__ . '/../ScriptClasses/Red/RedLeft', true),
                    new SearchDirectoryContext(__DIR__ . '/../ScriptClasses/Red/RedLeft3/Subdirectory', true),
                    new SearchDirectoryContext(__DIR__ . '/../ScriptClasses/Red/RedLeft3', isRecursive: true),
                ],
                'expectedFinalSearchingContexts' => [
                    // An exception should be thrown.
                    'non-relevant-context',
                ],
                'expectedResult' => [
                    // An exception should be thrown.
                    '/Non/Existing/Script.nope',
                ],
            ],

            'non-recursive-ok-wider-first' => [
                'throwOnException' => true,
                'expectedException' => null,
                'inputSearchingContexts' => [
                    new SearchDirectoryContext(__DIR__ . '/../ScriptClasses/Red/RedLeft3', isRecursive: false),
                    new SearchDirectoryContext(__DIR__ . '/../ScriptClasses/Red/RedRight', true),
                    new SearchDirectoryContext(__DIR__ . '/../ScriptClasses/Red/RedLeft', true),
                    new SearchDirectoryContext(__DIR__ . '/../ScriptClasses/Red/RedLeft3/Subdirectory', true),
                ],
                'expectedFinalSearchingContexts' => [
                    realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft3')              => new SearchDirectoryContext(
                        realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft3'),
                        isRecursive: false,
                    ),
                    realpath(__DIR__ . '/../ScriptClasses/Red/RedRight')              => new SearchDirectoryContext(
                        realpath(__DIR__ . '/../ScriptClasses/Red/RedRight'),
                        true,
                    ),
                    realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft')               => new SearchDirectoryContext(
                        realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft'),
                        true,
                    ),
                    realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft3/Subdirectory') => new SearchDirectoryContext(
                        realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft3/Subdirectory'),
                        true,
                    ),
                ],
                'expectedResult' => [
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedLeft3/Something5.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedRight/Subdirectory/Something8.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedRight/Something7.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedLeft/Something2.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedLeft3/Subdirectory/Something6.php'),
                ],
            ],
            'non-recursive-ok-wider-last' => [
                'throwOnException' => true,
                'expectedException' => null,
                'inputSearchingContexts' => [
                    new SearchDirectoryContext(__DIR__ . '/../ScriptClasses/Red/RedRight', true),
                    new SearchDirectoryContext(__DIR__ . '/../ScriptClasses/Red/RedLeft', true),
                    new SearchDirectoryContext(__DIR__ . '/../ScriptClasses/Red/RedLeft3/Subdirectory', true),
                    new SearchDirectoryContext(__DIR__ . '/../ScriptClasses/Red/RedLeft3', isRecursive: false),
                ],
                'expectedFinalSearchingContexts' => [
                    realpath(__DIR__ . '/../ScriptClasses/Red/RedRight')              => new SearchDirectoryContext(
                        realpath(__DIR__ . '/../ScriptClasses/Red/RedRight'),
                        true,
                    ),
                    realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft')               => new SearchDirectoryContext(
                        realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft'),
                        true,
                    ),
                    realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft3/Subdirectory') => new SearchDirectoryContext(
                        realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft3/Subdirectory'),
                        true,
                    ),
                    realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft3')              => new SearchDirectoryContext(
                        realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft3'),
                        isRecursive: false,
                    ),
                ],
                'expectedResult' => [
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedRight/Subdirectory/Something8.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedRight/Something7.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedLeft/Something2.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedLeft3/Subdirectory/Something6.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedLeft3/Something5.php'),
                ],
            ],

            'recursive-ignore-exceptions-wider-first' => [
                'throwOnException' => false,
                'expectedException' => null,
                'inputSearchingContexts' => [
                    new SearchDirectoryContext(__DIR__ . '/../ScriptClasses/Red/RedLeft3', isRecursive: true),
                    new SearchDirectoryContext(__DIR__ . '/../ScriptClasses/Red/RedRight', true),
                    new SearchDirectoryContext(__DIR__ . '/../ScriptClasses/Red/RedLeft', true),
                    new SearchDirectoryContext(__DIR__ . '/../ScriptClasses/Red/RedLeft3/Subdirectory', true),
                ],
                'expectedFinalSearchingContexts' => [
                    realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft3') => new SearchDirectoryContext(
                        realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft3'),
                        isRecursive: true,
                    ),
                    realpath(__DIR__ . '/../ScriptClasses/Red/RedRight') => new SearchDirectoryContext(
                        realpath(__DIR__ . '/../ScriptClasses/Red/RedRight'),
                        true,
                    ),
                    realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft')  => new SearchDirectoryContext(
                        realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft'),
                        true,
                    ),
                    // __DIR__ . '/../ScriptClasses/Red/RedLeft3/Subdirectory' is absent here.
                ],
                'expectedResult' => [
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedLeft3/Subdirectory/Something6.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedLeft3/Something5.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedRight/Subdirectory/Something8.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedRight/Something7.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedLeft/Something2.php'),
                ],
            ],
            'recursive-ignore-exceptions-wider-last' => [
                'throwOnException' => false,
                'expectedException' => null,
                'inputSearchingContexts' => [
                    new SearchDirectoryContext(__DIR__ . '/../ScriptClasses/Red/RedRight', true),
                    new SearchDirectoryContext(__DIR__ . '/../ScriptClasses/Red/RedLeft', true),
                    new SearchDirectoryContext(__DIR__ . '/../ScriptClasses/Red/RedLeft3/Subdirectory', true),
                    new SearchDirectoryContext(__DIR__ . '/../ScriptClasses/Red/RedLeft3', isRecursive: true),
                ],
                'expectedFinalSearchingContexts' => [
                    realpath(__DIR__ . '/../ScriptClasses/Red/RedRight') => new SearchDirectoryContext(
                        realpath(__DIR__ . '/../ScriptClasses/Red/RedRight'),
                        true,
                    ),
                    realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft')  => new SearchDirectoryContext(
                        realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft'),
                        true,
                    ),
                    // __DIR__ . '/../ScriptClasses/Red/RedLeft3/Subdirectory' is absent here.
                    realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft3') => new SearchDirectoryContext(
                        realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft3'),
                        isRecursive: true,
                    ),
                ],
                'expectedResult' => [
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedRight/Subdirectory/Something8.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedRight/Something7.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedLeft/Something2.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedLeft3/Subdirectory/Something6.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedLeft3/Something5.php'),
                ],
            ],

            'recursive-ignore-exceptions-even-wider-in-middle' => [
                'throwOnException' => false,
                'expectedException' => null,
                'inputSearchingContexts' => [
                    new SearchDirectoryContext(__DIR__ . '/../ScriptClasses/Red/RedLeft', true),
                    new SearchDirectoryContext(__DIR__ . '/../ScriptClasses/Red/RedLeft3/Subdirectory', true),
                    new SearchDirectoryContext(__DIR__ . '/../ScriptClasses/Red', isRecursive: true),
                    new SearchDirectoryContext(__DIR__ . '/../ScriptClasses/Red/RedLeft2', true),
                    new SearchDirectoryContext(__DIR__ . '/../ScriptClasses/Red/RedRight', true),
                ],
                'expectedFinalSearchingContexts' => [
                    realpath(__DIR__ . '/../ScriptClasses/Red') => new SearchDirectoryContext(
                        realpath(__DIR__ . '/../ScriptClasses/Red'),
                        isRecursive: true,
                    ),
                ],
                'expectedResult' => [
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedLeft22/Something4.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedBase.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/SomethingX.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedRight/Subdirectory/Something8.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedRight/Something7.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedLeft/Something2.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/Something1.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedLeft2/Something3.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedLeft3/Subdirectory/Something6.php'),
                    realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedLeft3/Something5.php'),
                ],
            ],
        ];
    }

    #[DataProvider('provideThrowOnException')]
    /**
     * Tests excluded directories' duplicates processing.
     *
     * @see ScriptDetectorAbstract::excludeDirectory()
     */
    public function testDuplicateExclusion(bool $throwOnException): void {
        if ($throwOnException) {
            $this->expectExceptionObject(
                new RuntimeException(
                    sprintf(
                        "Duplicate excluded directory path: %s (raw value: '%s')",
                        realpath(__DIR__ . '/../ScriptClasses/Red/RedRight'),
                        __DIR__ . '/../ScriptClasses/Red/RedRight',
                    ),
                ),
            );
        }

        $detector = (new ScriptDetectorMock($throwOnException))
            ->searchDirectory(__DIR__ . '/../ScriptClasses/Red', isRecursive: true)
            ->excludeDirectories([
                realpath(__DIR__ . '/../ScriptClasses/Red/RedRight'),
                __DIR__ . '/../ScriptClasses/Red/RedLeft3',
                __DIR__ . '/../ScriptClasses/Red/RedLeft22',
                __DIR__ . '/../ScriptClasses/Red/RedRight',  // Duplicate.
                __DIR__ . '/../ScriptClasses/Red/RedLeft',
                __DIR__ . '/../ScriptClasses/Red/RedLeft',   // Duplicate.
            ]);

        // The assertions below should happen only if no exception is thrown during the detector object's setup.

        // Here we ensure that no duplicate entry is added.
        assertSame(
            [
                realpath(__DIR__ . '/../ScriptClasses/Red/RedRight')  => realpath(__DIR__ . '/../ScriptClasses/Red/RedRight'),
                realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft3')  => realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft3'),
                realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft22') => realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft22'),
                realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft')   => realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft'),
            ],
            $detector->getExcludedDirectoryPaths(),
        );

        assertSame(
            [
                realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedBase.php'),
                realpath(__DIR__ . '/' . '../ScriptClasses/Red/SomethingX.php'),
                realpath(__DIR__ . '/' . '../ScriptClasses/Red/Something1.php'),
                realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedLeft2/Something3.php'),
            ],
            $detector->getDetectedData(),
        );
    }

    #[DataProvider('provideDuplicateExclusionSubdirectory')]
    /**
     * Tests the cases when one of excluded directories is a subdirectory of some another excluded directory.
     *
     * @param array<string, string> $inputExcludeDirectories
     * @param array<string, string> $expectedFinalExcludedDirectories
     * @see ScriptDetectorAbstract::excludeDirectory()
     */
    public function testDuplicateExclusionSubdirectory(
        bool $throwOnException,
        ?Exception $expectedException,
        array $inputExcludeDirectories,
        array $expectedFinalExcludedDirectories,
    ): void {
        if ($expectedException) {
            $this->expectExceptionObject($expectedException);
        }

        $detector = (new ScriptDetectorMock($throwOnException))
            ->searchDirectory(__DIR__ . '/../ScriptClasses/Red', isRecursive: true)
            ->excludeDirectories($inputExcludeDirectories);

        // The assertions below should happen only if no exception is thrown during the detector object's setup.

        // Here we ensure (mainly, for "wider" exclusion) that only a "wider" directory is stored eventually
        // in a detector - we should not process affected directories more than once.
        assertSame($expectedFinalExcludedDirectories, $detector->getExcludedDirectoryPaths());

        assertSame(
            [
                realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedLeft22/Something4.php'),
                realpath(__DIR__ . '/' . '../ScriptClasses/Red/RedBase.php'),
                realpath(__DIR__ . '/' . '../ScriptClasses/Red/SomethingX.php'),
                realpath(__DIR__ . '/' . '../ScriptClasses/Red/Something1.php'),
            ],
            $detector->getDetectedData(),
        );
    }

    /**
     * @return array[]
     */
    public static function provideDuplicateExclusionSubdirectory(): array {
        return [
            'throw-wider-first' => [
                'throwOnException' => true,
                'expectedException' => new RuntimeException(
                    sprintf(
                        "Previously excluded directory '%s'"
                        . " incorporates just excluded directory path '%s' (raw value: '%s')",
                        realpath(__DIR__ . '/../ScriptClasses/Red/RedRight'),
                        realpath(__DIR__ . '/../ScriptClasses/Red/RedRight/Subdirectory'),
                        __DIR__ . '/../ScriptClasses/Red/RedRight/Subdirectory',
                    ),
                ),
                'inputExcludeDirectories' => [
                    __DIR__ . '/../ScriptClasses/Red/RedRight',              // A "wider/higher" directory.
                    __DIR__ . '/../ScriptClasses/Red/RedRight/Subdirectory', // A subdirectory.
                    __DIR__ . '/../ScriptClasses/Red/RedLeft',
                    __DIR__ . '/../ScriptClasses/Red/RedLeft2',
                    __DIR__ . '/../ScriptClasses/Red/RedLeft3',
                ],
                'expectedFinalExcludedDirectories' => [
                    // An exception should be thrown.
                    'non-relevant-context',
                ],
            ],
            'throw-wider-last' => [
                'throwOnException' => true,
                'expectedException' => new RuntimeException(
                    sprintf(
                        "Just excluded directory '%s' (raw value: '%s')"
                        . " incorporates previously excluded directory path '%s'",
                        realpath(__DIR__ . '/../ScriptClasses/Red/RedRight'),
                        __DIR__ . '/../ScriptClasses/Red/RedRight',
                        realpath(__DIR__ . '/../ScriptClasses/Red/RedRight/Subdirectory'),
                    ),
                ),
                'inputExcludeDirectories' => [
                    __DIR__ . '/../ScriptClasses/Red/RedRight/Subdirectory', // A subdirectory.
                    __DIR__ . '/../ScriptClasses/Red/RedLeft',
                    __DIR__ . '/../ScriptClasses/Red/RedLeft2',
                    __DIR__ . '/../ScriptClasses/Red/RedLeft3',
                    __DIR__ . '/../ScriptClasses/Red/RedRight', // A "wider/higher" directory.
                ],
                'expectedFinalExcludedDirectories' => [
                    // An exception should be thrown.
                    'non-relevant-context',
                ],
            ],

            'ignore-wider-first' => [
                'throwOnException' => false,
                'expectedException' => null,
                'inputExcludeDirectories' => [
                    __DIR__ . '/../ScriptClasses/Red/RedRight',              // A "wider/higher" directory.
                    __DIR__ . '/../ScriptClasses/Red/RedRight/Subdirectory', // A subdirectory.
                    __DIR__ . '/../ScriptClasses/Red/RedLeft',
                    __DIR__ . '/../ScriptClasses/Red/RedLeft2',
                    __DIR__ . '/../ScriptClasses/Red/RedLeft3',
                ],
                'expectedFinalExcludedDirectories' => [
                    realpath(__DIR__ . '/../ScriptClasses/Red/RedRight') => realpath(
                        __DIR__ . '/../ScriptClasses/Red/RedRight'
                    ),
                    realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft')  => realpath(
                        __DIR__ . '/../ScriptClasses/Red/RedLeft'
                    ),
                    realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft2') => realpath(
                        __DIR__ . '/../ScriptClasses/Red/RedLeft2'
                    ),
                    realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft3') => realpath(
                        __DIR__ . '/../ScriptClasses/Red/RedLeft3'
                    ),
                ],
            ],
            'ignore-wider-last' => [
                'throwOnException' => false,
                'expectedException' => null,
                'inputExcludeDirectories' => [
                    __DIR__ . '/../ScriptClasses/Red/RedRight/Subdirectory', // A subdirectory.
                    __DIR__ . '/../ScriptClasses/Red/RedLeft',
                    __DIR__ . '/../ScriptClasses/Red/RedLeft2',
                    __DIR__ . '/../ScriptClasses/Red/RedLeft3',
                    __DIR__ . '/../ScriptClasses/Red/RedRight', // A "wider/higher" directory.
                ],
                'expectedFinalExcludedDirectories' => [
                    realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft')  => realpath(
                        __DIR__ . '/../ScriptClasses/Red/RedLeft'
                    ),
                    realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft2') => realpath(
                        __DIR__ . '/../ScriptClasses/Red/RedLeft2'
                    ),
                    realpath(__DIR__ . '/../ScriptClasses/Red/RedLeft3') => realpath(
                        __DIR__ . '/../ScriptClasses/Red/RedLeft3'
                    ),
                    realpath(__DIR__ . '/../ScriptClasses/Red/RedRight') => realpath(
                        __DIR__ . '/../ScriptClasses/Red/RedRight'
                    ),
                ],
            ],
        ];
    }

    /**
     * Tests the case when a more "wide" (more levels "higher") directory incorporates excluded subdirectories.
     *
     * @see ScriptDetectorAbstract::excludeDirectory()
     */
    public function testDuplicateEvenWiderExclusionInMiddle(): void {
        $detector = (new ScriptDetectorMock(throwOnException: false))
            ->searchDirectory(__DIR__ . '/../ScriptClasses', isRecursive: true)
            ->excludeDirectories([
                __DIR__ . '/../ScriptClasses/Red/RedRight',              // A "wider/higher" directory.
                __DIR__ . '/../ScriptClasses/Red/RedRight/Subdirectory', // A subdirectory.
                __DIR__ . '/../ScriptClasses/Red',                       // Super-"wide" directory.
                __DIR__ . '/../ScriptClasses/Red/RedLeft',
                __DIR__ . '/../ScriptClasses/Red/RedLeft2',
                __DIR__ . '/../ScriptClasses/Red/RedLeft3',
            ]);

        // The assertions below should happen only if no exception is thrown during the detector object's setup.

        // Here we ensure (mainly, for "wider" exclusion) that only a "wider" directory is stored eventually
        // in a detector - we should not process affected directories more than once.
        assertSame(
            [
                realpath(__DIR__ . '/../ScriptClasses/Red') => realpath(__DIR__ . '/../ScriptClasses/Red'),
            ],
            $detector->getExcludedDirectoryPaths(),
        );

        assertSame(
            [
                realpath(__DIR__ . '/../ScriptClasses/SomethingNoNamespace.php'),
                realpath(__DIR__ . '/../ScriptClasses/AnotherThing.php'),
                realpath(__DIR__ . '/../ScriptClasses/AnotherThingAbstract.php'),
                realpath(__DIR__ . '/../ScriptClasses/SomethingZero.php'),
            ],
            $detector->getDetectedData(),
        );
    }
}
