<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector;

use Exception;
use MagicPush\CliToolkit\Parametizer\Script\ScriptDetector\ScriptDetector;
use MagicPush\CliToolkit\Parametizer\Script\ScriptDetector\SearchDirectoryContext;
use MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Script5;
use MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\ScriptX;
use MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\ScriptZero;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

use function PHPUnit\Framework\assertSame;

class ScriptDetectorDuplicateTest extends ScriptDetectorTestAbstract {
    #[DataProvider('provideThrowOnException')]
    /**
     * Tests duplicate fully qualified names processing.
     *
     * @see ScriptDetector::scriptFQClassName()
     */
    public function testDuplicateFQNames(bool $throwOnException): void {
        if ($throwOnException) {
            $this->expectExceptionObject(
                new RuntimeException('Duplicate fully qualified class name search requested: ' . ScriptX::class),
            );
        }

        $detector = (new ScriptDetectorMock($throwOnException))
            ->scriptFQClassNames([
                ScriptX::class,
                Script5::class,
                ScriptX::class,    // Duplicate (first; is mentioned in the exception thrown).
                ScriptZero::class,
                ScriptZero::class, // Duplicate.
            ]);

        // The assertions below should happen only if no exception is thrown during the detector object's setup.

        // Here we ensure that no duplicate entry is added.
        assertSame(
            [
                ScriptX::class    => ScriptX::class,
                Script5::class    => Script5::class,
                ScriptZero::class => ScriptZero::class,
            ],
            $detector->getSearchedFQClassNames(),
        );

        assertSame(
            [
                'script-x'    => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\ScriptX',
                'red:script5' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Script5',
                'script'      => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\ScriptZero',
            ],
            $detector->getFQClassNamesByScriptNames(),
        );
    }

    #[DataProvider('provideDuplicateSearchSameRecursion')]
    /**
     * Tests duplicate search paths processing with the same recursion state.
     *
     * @param array<string, string> $expectedClasses
     * @see ScriptDetector::searchDirectory()
     */
    public function testDuplicateSearchSameRecursion(
        bool $throwOnException,
        bool $isRecursive,
        array $expectedClasses,
    ): void {
        if ($throwOnException) {
            $this->expectExceptionObject(
                new RuntimeException(
                    sprintf(
                        "Duplicate searching directory path: %s (raw value: '%s')",
                        realpath(__DIR__ . '/ScriptClasses/Red/RedRight'),
                        __DIR__ . '/ScriptClasses/Red/RedRight',
                    ),
                ),
            );
        }

        $detector = (new ScriptDetectorMock($throwOnException))
            ->searchDirectories(
                [
                    __DIR__ . '/ScriptClasses/Red/RedRight',
                    __DIR__ . '/ScriptClasses/Red/RedLeft',
                    realpath(__DIR__ . '/ScriptClasses/Red/RedRight'), // Duplicate.
                    __DIR__ . '/ScriptClasses/Red/RedLeft',  // Duplicate.
                ],
                $isRecursive,
            );

        // The assertions below should happen only if no exception is thrown during the detector object's setup.

        // Here we ensure that no duplicate entry is added.
        $expectedFinalSearchingContexts = [
            realpath(__DIR__ . '/ScriptClasses/Red/RedRight') => new SearchDirectoryContext(
                realpath(__DIR__ . '/ScriptClasses/Red/RedRight'),
                $isRecursive,
            ),
            realpath(__DIR__ . '/ScriptClasses/Red/RedLeft') => new SearchDirectoryContext(
                realpath(__DIR__ . '/ScriptClasses/Red/RedLeft'),
                $isRecursive,
            ),
        ];
        assertSame(serialize($expectedFinalSearchingContexts), serialize($detector->getSearchingDirectories()));

        assertSame($expectedClasses, $detector->getFQClassNamesByScriptNames());
    }

    /**
     * @return array[]
     */
    public static function provideDuplicateSearchSameRecursion(): array {
        return [
            'throw-recursive' => [
                'throwOnException' => true,
                'isRecursive'      => true,
                'expectedClasses'  => [
                    // No assertion should happen.
                ],
            ],
            'throw-non-recursive' => [
                'throwOnException' => true,
                'isRecursive'      => false,
                'expectedClasses'  => [
                    // No assertion should happen.
                ],
            ],
            'ignore-recursive'  => [
                'throwOnException' => false,
                'isRecursive'      => true,
                'expectedClasses'  => [
                    'red:script8' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight\Subdirectory\Script8',
                    'red:script7' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight\Script7',
                    'red:script2' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft\Script2',
                ],
            ],
            'ignore-non-recursive'  => [
                'throwOnException' => false,
                'isRecursive'      => false,
                'expectedClasses'  => [
                    'red:script7' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight\Script7',
                    'red:script2' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft\Script2',
                ],
            ],
        ];
    }

    #[DataProvider('provideDuplicateSearchDifferentRecursion')]
    /**
     * Tests duplicate search paths processing if different paths have different recursion state - the duplication
     * validation ignores recursion state.
     *
     * @param array<string, string> $expectedClasses
     * @see ScriptDetector::searchDirectory()
     */
    public function testDuplicateSearchDifferentRecursion(
        bool $throwOnException,
        bool $isFirstRecursive,
        array $expectedClasses,
    ): void {
        if ($throwOnException) {
            $this->expectExceptionObject(
                new RuntimeException(
                    sprintf(
                        "Duplicate searching directory path: %s (raw value: '%s')",
                        realpath(__DIR__ . '/ScriptClasses/Red/RedRight'),
                        __DIR__ . '/ScriptClasses/Red/RedRight',
                    ),
                ),
            );
        }

        $detector = (new ScriptDetectorMock($throwOnException))
            ->searchDirectory(__DIR__ . '/ScriptClasses/Red/RedRight', $isFirstRecursive)
            ->searchDirectory(__DIR__ . '/ScriptClasses/Red/RedRight', !$isFirstRecursive);

        // The assertions below should happen only if no exception is thrown during the detector object's setup.

        // Here we ensure that no duplicate entry is added.
        $expectedFinalSearchingContexts = [
            realpath(__DIR__ . '/ScriptClasses/Red/RedRight') => new SearchDirectoryContext(
                realpath(__DIR__ . '/ScriptClasses/Red/RedRight'),
                $isFirstRecursive,
            ),
        ];
        assertSame(serialize($expectedFinalSearchingContexts), serialize($detector->getSearchingDirectories()));

        assertSame($expectedClasses, $detector->getFQClassNamesByScriptNames());
    }

    /**
     * @return array[]
     */
    public static function provideDuplicateSearchDifferentRecursion(): array {
        return [
            'throw-recursive-first' => [
                'throwOnException' => true,
                'isFirstRecursive' => true,
                'expectedClasses'  => [
                    // No assertion should happen.
                ],
            ],
            'throw-recursive-second' => [
                'throwOnException' => true,
                'isFirstRecursive' => false,
                'expectedClasses'  => [
                    // No assertion should happen.
                ],
            ],
            'ignore-recursive-first'  => [
                'throwOnException' => false,
                'isFirstRecursive' => true,
                'expectedClasses'  => [
                    // The first search context is considered only, so the classes are detected recursively.
                    'red:script8' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight\Subdirectory\Script8',
                    'red:script7' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight\Script7',
                ],
            ],
            'ignore-recursive-second'  => [
                'throwOnException' => false,
                'isFirstRecursive' => false,
                'expectedClasses'  => [
                    // The first search context is considered only, so no recursive search happens.
                    'red:script7' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight\Script7',
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
     * @param array<string, string> $expectedClasses
     * @see ScriptDetector::searchDirectory()
     */
    public function testDuplicateSearchSubdirectory(
        bool $throwOnException,
        ?Exception $expectedException,
        array $inputSearchingContexts,
        array $expectedFinalSearchingContexts,
        array $expectedClasses,
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

        assertSame($expectedClasses, $detector->getFQClassNamesByScriptNames());
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
                        realpath(__DIR__ . '/ScriptClasses/Red/RedLeft3'),
                        realpath(__DIR__ . '/ScriptClasses/Red/RedLeft3/Subdirectory'),
                        __DIR__ . '/ScriptClasses/Red/RedLeft3/Subdirectory',
                    ),
                ),
                'inputSearchingContexts' => [
                    new SearchDirectoryContext(__DIR__ . '/ScriptClasses/Red/RedLeft3', isRecursive: true),
                    new SearchDirectoryContext(__DIR__ . '/ScriptClasses/Red/RedRight', true),
                    new SearchDirectoryContext(__DIR__ . '/ScriptClasses/Red/RedLeft', true),
                    new SearchDirectoryContext(__DIR__ . '/ScriptClasses/Red/RedLeft3/Subdirectory', true),
                ],
                'expectedFinalSearchingContexts' => [
                    // An exception should be thrown.
                    'non-existing-script' => 'Non\Existing\ClassName',
                ],
                'expectedClasses'     => [
                    // An exception should be thrown.
                    'non-existing-script' => 'Non\Existing\ClassName',
                ],
            ],
            'recursive-throw-wider-last' => [
                'throwOnException' => true,
                'expectedException' => new RuntimeException(
                    sprintf(
                        "Just added directory's searching recursive scope '%s' (raw value: '%s')"
                            . " includes previously added directory path '%s'",
                        realpath(__DIR__ . '/ScriptClasses/Red/RedLeft3'),
                        __DIR__ . '/ScriptClasses/Red/RedLeft3',
                        realpath(__DIR__ . '/ScriptClasses/Red/RedLeft3/Subdirectory'),
                    ),
                ),
                'inputSearchingContexts' => [
                    new SearchDirectoryContext(__DIR__ . '/ScriptClasses/Red/RedRight', true),
                    new SearchDirectoryContext(__DIR__ . '/ScriptClasses/Red/RedLeft', true),
                    new SearchDirectoryContext(__DIR__ . '/ScriptClasses/Red/RedLeft3/Subdirectory', true),
                    new SearchDirectoryContext(__DIR__ . '/ScriptClasses/Red/RedLeft3', isRecursive: true),
                ],
                'expectedFinalSearchingContexts' => [
                    // An exception should be thrown.
                    'non-existing-script' => 'Non\Existing\ClassName',
                ],
                'expectedClasses'     => [
                    // An exception should be thrown.
                    'non-existing-script' => 'Non\Existing\ClassName',
                ],
            ],

            'non-recursive-ok-wider-first' => [
                'throwOnException' => true,
                'expectedException' => null,
                'inputSearchingContexts' => [
                    new SearchDirectoryContext(__DIR__ . '/ScriptClasses/Red/RedLeft3', isRecursive: false),
                    new SearchDirectoryContext(__DIR__ . '/ScriptClasses/Red/RedRight', true),
                    new SearchDirectoryContext(__DIR__ . '/ScriptClasses/Red/RedLeft', true),
                    new SearchDirectoryContext(__DIR__ . '/ScriptClasses/Red/RedLeft3/Subdirectory', true),
                ],
                'expectedFinalSearchingContexts' => [
                    realpath(__DIR__ . '/ScriptClasses/Red/RedLeft3') => new SearchDirectoryContext(
                        realpath(__DIR__ . '/ScriptClasses/Red/RedLeft3'),
                        isRecursive: false,
                    ),
                    realpath(__DIR__ . '/ScriptClasses/Red/RedRight') => new SearchDirectoryContext(
                        realpath(__DIR__ . '/ScriptClasses/Red/RedRight'),
                        true,
                    ),
                    realpath(__DIR__ . '/ScriptClasses/Red/RedLeft') => new SearchDirectoryContext(
                        realpath(__DIR__ . '/ScriptClasses/Red/RedLeft'),
                        true,
                    ),
                    realpath(__DIR__ . '/ScriptClasses/Red/RedLeft3/Subdirectory') => new SearchDirectoryContext(
                        realpath(__DIR__ . '/ScriptClasses/Red/RedLeft3/Subdirectory'),
                        true,
                    ),
                ],
                'expectedClasses'     => [
                    'red:script5' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Script5',
                    'red:script8' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight\Subdirectory\Script8',
                    'red:script7' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight\Script7',
                    'red:script2' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft\Script2',
                    'red:script6' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Subdirectory\Script6',
                ],
            ],
            'non-recursive-ok-wider-last' => [
                'throwOnException' => true,
                'expectedException' => null,
                'inputSearchingContexts' => [
                    new SearchDirectoryContext(__DIR__ . '/ScriptClasses/Red/RedRight', true),
                    new SearchDirectoryContext(__DIR__ . '/ScriptClasses/Red/RedLeft', true),
                    new SearchDirectoryContext(__DIR__ . '/ScriptClasses/Red/RedLeft3/Subdirectory', true),
                    new SearchDirectoryContext(__DIR__ . '/ScriptClasses/Red/RedLeft3', isRecursive: false),
                ],
                'expectedFinalSearchingContexts' => [
                    realpath(__DIR__ . '/ScriptClasses/Red/RedRight') => new SearchDirectoryContext(
                        realpath(__DIR__ . '/ScriptClasses/Red/RedRight'),
                        true,
                    ),
                    realpath(__DIR__ . '/ScriptClasses/Red/RedLeft') => new SearchDirectoryContext(
                        realpath(__DIR__ . '/ScriptClasses/Red/RedLeft'),
                        true,
                    ),
                    realpath(__DIR__ . '/ScriptClasses/Red/RedLeft3/Subdirectory') => new SearchDirectoryContext(
                        realpath(__DIR__ . '/ScriptClasses/Red/RedLeft3/Subdirectory'),
                        true,
                    ),
                    realpath(__DIR__ . '/ScriptClasses/Red/RedLeft3') => new SearchDirectoryContext(
                        realpath(__DIR__ . '/ScriptClasses/Red/RedLeft3'),
                        isRecursive: false,
                    ),
                ],
                'expectedClasses'     => [
                    'red:script8' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight\Subdirectory\Script8',
                    'red:script7' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight\Script7',
                    'red:script2' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft\Script2',
                    'red:script6' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Subdirectory\Script6',
                    'red:script5' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Script5',
                ],
            ],

            'recursive-ignore-exceptions-wider-first' => [
                'throwOnException' => false,
                'expectedException' => null,
                'inputSearchingContexts' => [
                    new SearchDirectoryContext(__DIR__ . '/ScriptClasses/Red/RedLeft3', isRecursive: true),
                    new SearchDirectoryContext(__DIR__ . '/ScriptClasses/Red/RedRight', true),
                    new SearchDirectoryContext(__DIR__ . '/ScriptClasses/Red/RedLeft', true),
                    new SearchDirectoryContext(__DIR__ . '/ScriptClasses/Red/RedLeft3/Subdirectory', true),
                ],
                'expectedFinalSearchingContexts' => [
                    realpath(__DIR__ . '/ScriptClasses/Red/RedLeft3') => new SearchDirectoryContext(
                        realpath(__DIR__ . '/ScriptClasses/Red/RedLeft3'),
                        isRecursive: true,
                    ),
                    realpath(__DIR__ . '/ScriptClasses/Red/RedRight') => new SearchDirectoryContext(
                        realpath(__DIR__ . '/ScriptClasses/Red/RedRight'),
                        true,
                    ),
                    realpath(__DIR__ . '/ScriptClasses/Red/RedLeft') => new SearchDirectoryContext(
                        realpath(__DIR__ . '/ScriptClasses/Red/RedLeft'),
                        true,
                    ),
                    // __DIR__ . '/ScriptClasses/Red/RedLeft3/Subdirectory' is absent here.
                ],
                'expectedClasses'     => [
                    'red:script6' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Subdirectory\Script6',
                    'red:script5' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Script5',
                    'red:script8' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight\Subdirectory\Script8',
                    'red:script7' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight\Script7',
                    'red:script2' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft\Script2',
                ],
            ],
            'recursive-ignore-exceptions-wider-last' => [
                'throwOnException' => false,
                'expectedException' => null,
                'inputSearchingContexts' => [
                    new SearchDirectoryContext(__DIR__ . '/ScriptClasses/Red/RedRight', true),
                    new SearchDirectoryContext(__DIR__ . '/ScriptClasses/Red/RedLeft', true),
                    new SearchDirectoryContext(__DIR__ . '/ScriptClasses/Red/RedLeft3/Subdirectory', true),
                    new SearchDirectoryContext(__DIR__ . '/ScriptClasses/Red/RedLeft3', isRecursive: true),
                ],
                'expectedFinalSearchingContexts' => [
                    realpath(__DIR__ . '/ScriptClasses/Red/RedRight') => new SearchDirectoryContext(
                        realpath(__DIR__ . '/ScriptClasses/Red/RedRight'),
                        true,
                    ),
                    realpath(__DIR__ . '/ScriptClasses/Red/RedLeft') => new SearchDirectoryContext(
                        realpath(__DIR__ . '/ScriptClasses/Red/RedLeft'),
                        true,
                    ),
                    // __DIR__ . '/ScriptClasses/Red/RedLeft3/Subdirectory' is absent here.
                    realpath(__DIR__ . '/ScriptClasses/Red/RedLeft3') => new SearchDirectoryContext(
                        realpath(__DIR__ . '/ScriptClasses/Red/RedLeft3'),
                        isRecursive: true,
                    ),
                ],
                'expectedClasses'     => [
                    'red:script8' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight\Subdirectory\Script8',
                    'red:script7' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight\Script7',
                    'red:script2' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft\Script2',
                    'red:script6' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Subdirectory\Script6',
                    'red:script5' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Script5',
                ],
            ],

            'recursive-ignore-exceptions-even-wider-in-middle' => [
                'throwOnException' => false,
                'expectedException' => null,
                'inputSearchingContexts' => [
                    new SearchDirectoryContext(__DIR__ . '/ScriptClasses/Red/RedLeft', true),
                    new SearchDirectoryContext(__DIR__ . '/ScriptClasses/Red/RedLeft3/Subdirectory', true),
                    new SearchDirectoryContext(__DIR__ . '/ScriptClasses/Red', isRecursive: true),
                    new SearchDirectoryContext(__DIR__ . '/ScriptClasses/Red/RedLeft2', true),
                    new SearchDirectoryContext(__DIR__ . '/ScriptClasses/Red/RedRight', true),
                ],
                'expectedFinalSearchingContexts' => [
                    realpath(__DIR__ . '/ScriptClasses/Red') => new SearchDirectoryContext(
                        realpath(__DIR__ . '/ScriptClasses/Red'),
                        isRecursive: true,
                    ),
                ],
                'expectedClasses'     => [
                    'red:script4' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft22\Script4',
                    'script-x'    => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\ScriptX',
                    'red:script8' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight\Subdirectory\Script8',
                    'red:script7' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight\Script7',
                    'red:script2' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft\Script2',
                    'red:script3' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft2\Script3',
                    'red:script6' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Subdirectory\Script6',
                    'red:script5' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Script5',
                    'red:script1' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\Script1',
                ],
            ],
        ];
    }

    #[DataProvider('provideThrowOnException')]
    /**
     * Tests excluded directories' duplicates processing.
     *
     * @see ScriptDetector::excludeDirectory()
     */
    public function testDuplicateExclusion(bool $throwOnException): void {
        if ($throwOnException) {
            $this->expectExceptionObject(
                new RuntimeException(
                    sprintf(
                        "Duplicate excluded directory path: %s (raw value: '%s')",
                        realpath(__DIR__ . '/ScriptClasses/Red/RedRight'),
                        __DIR__ . '/ScriptClasses/Red/RedRight',
                    ),
                ),
            );
        }

        $detector = (new ScriptDetectorMock($throwOnException))
            ->searchDirectory(__DIR__ . '/ScriptClasses/Red', isRecursive: true)
            ->excludeDirectories([
                __DIR__ . '/ScriptClasses/Red/RedRight',
                __DIR__ . '/ScriptClasses/Red/RedLeft3',
                __DIR__ . '/ScriptClasses/Red/RedLeft22',
                realpath(__DIR__ . '/ScriptClasses/Red/RedRight'),  // Duplicate.
                __DIR__ . '/ScriptClasses/Red/RedLeft',
                __DIR__ . '/ScriptClasses/Red/RedLeft',   // Duplicate.
            ]);

        // The assertions below should happen only if no exception is thrown during the detector object's setup.

        // Here we ensure that no duplicate entry is added.
        assertSame(
            [
                realpath(__DIR__ . '/ScriptClasses/Red/RedRight')  => realpath(__DIR__ . '/ScriptClasses/Red/RedRight'),
                realpath(__DIR__ . '/ScriptClasses/Red/RedLeft3')  => realpath(__DIR__ . '/ScriptClasses/Red/RedLeft3'),
                realpath(__DIR__ . '/ScriptClasses/Red/RedLeft22') => realpath(__DIR__ . '/ScriptClasses/Red/RedLeft22'),
                realpath(__DIR__ . '/ScriptClasses/Red/RedLeft')   => realpath(__DIR__ . '/ScriptClasses/Red/RedLeft'),
            ],
            $detector->getExcludedDirectoryPaths(),
        );

        assertSame(
            [
                'script-x'    => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\ScriptX',
                'red:script3' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft2\Script3',
                'red:script1' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\Script1',
            ],
            $detector->getFQClassNamesByScriptNames(),
        );
    }

    #[DataProvider('provideDuplicateExclusionSubdirectory')]
    /**
     * Tests the cases when one of excluded directories is a subdirectory of some another excluded directory.
     *
     * @param array<string, string> $inputExcludeDirectories
     * @param array<string, string> $expectedFinalExcludedDirectories
     * @see ScriptDetector::excludeDirectory()
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
            ->searchDirectory(__DIR__ . '/ScriptClasses/Red', isRecursive: true)
            ->excludeDirectories($inputExcludeDirectories);

        // The assertions below should happen only if no exception is thrown during the detector object's setup.

        // Here we ensure (mainly, for "wider" exclusion) that only a "wider" directory is stored eventually
        // in a detector - we should not process affected directories more than once.
        assertSame($expectedFinalExcludedDirectories, $detector->getExcludedDirectoryPaths());

        assertSame(
            [
                'red:script4' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft22\Script4',
                'script-x'    => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\ScriptX',
                'red:script1' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\Script1',
            ],
            $detector->getFQClassNamesByScriptNames(),
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
                        realpath(__DIR__ . '/ScriptClasses/Red/RedRight'),
                        realpath(__DIR__ . '/ScriptClasses/Red/RedRight/Subdirectory'),
                        __DIR__ . '/ScriptClasses/Red/RedRight/Subdirectory',
                    ),
                ),
                'inputExcludeDirectories' => [
                    __DIR__ . '/ScriptClasses/Red/RedRight', // A "wider/higher" directory.
                    __DIR__ . '/ScriptClasses/Red/RedRight/Subdirectory', // A subdirectory.
                    __DIR__ . '/ScriptClasses/Red/RedLeft',
                    __DIR__ . '/ScriptClasses/Red/RedLeft2',
                    __DIR__ . '/ScriptClasses/Red/RedLeft3',
                ],
                'expectedFinalExcludedDirectories' => [
                    // An exception should be thrown.
                    'non-existing-script' => 'Non\Existing\ClassName',
                ],
            ],
            'throw-wider-last' => [
                'throwOnException' => true,
                'expectedException' => new RuntimeException(
                    sprintf(
                        "Just excluded directory '%s' (raw value: '%s')"
                            . " incorporates previously excluded directory path '%s'",
                        realpath(__DIR__ . '/ScriptClasses/Red/RedRight'),
                        __DIR__ . '/ScriptClasses/Red/RedRight',
                        realpath(__DIR__ . '/ScriptClasses/Red/RedRight/Subdirectory'),
                    ),
                ),
                'inputExcludeDirectories' => [
                    __DIR__ . '/ScriptClasses/Red/RedRight/Subdirectory', // A subdirectory.
                    __DIR__ . '/ScriptClasses/Red/RedLeft',
                    __DIR__ . '/ScriptClasses/Red/RedLeft2',
                    __DIR__ . '/ScriptClasses/Red/RedLeft3',
                    __DIR__ . '/ScriptClasses/Red/RedRight', // A "wider/higher" directory.
                ],
                'expectedFinalExcludedDirectories' => [
                    // An exception should be thrown.
                    'non-existing-script' => 'Non\Existing\ClassName',
                ],
            ],

            'ignore-wider-first' => [
                'throwOnException' => false,
                'expectedException' => null,
                'inputExcludeDirectories' => [
                    __DIR__ . '/ScriptClasses/Red/RedRight', // A "wider/higher" directory.
                    __DIR__ . '/ScriptClasses/Red/RedRight/Subdirectory', // A subdirectory.
                    __DIR__ . '/ScriptClasses/Red/RedLeft',
                    __DIR__ . '/ScriptClasses/Red/RedLeft2',
                    __DIR__ . '/ScriptClasses/Red/RedLeft3',
                ],
                'expectedFinalExcludedDirectories' => [
                    realpath(__DIR__ . '/ScriptClasses/Red/RedRight') => realpath(__DIR__ . '/ScriptClasses/Red/RedRight'),
                    realpath(__DIR__ . '/ScriptClasses/Red/RedLeft')  => realpath(__DIR__ . '/ScriptClasses/Red/RedLeft'),
                    realpath(__DIR__ . '/ScriptClasses/Red/RedLeft2') => realpath(__DIR__ . '/ScriptClasses/Red/RedLeft2'),
                    realpath(__DIR__ . '/ScriptClasses/Red/RedLeft3') => realpath(__DIR__ . '/ScriptClasses/Red/RedLeft3'),
                ],
            ],
            'ignore-wider-last' => [
                'throwOnException' => false,
                'expectedException' => null,
                'inputExcludeDirectories' => [
                    __DIR__ . '/ScriptClasses/Red/RedRight/Subdirectory', // A subdirectory.
                    __DIR__ . '/ScriptClasses/Red/RedLeft',
                    __DIR__ . '/ScriptClasses/Red/RedLeft2',
                    __DIR__ . '/ScriptClasses/Red/RedLeft3',
                    __DIR__ . '/ScriptClasses/Red/RedRight', // A "wider/higher" directory.
                ],
                'expectedFinalExcludedDirectories' => [
                    realpath(__DIR__ . '/ScriptClasses/Red/RedLeft')  => realpath(__DIR__ . '/ScriptClasses/Red/RedLeft'),
                    realpath(__DIR__ . '/ScriptClasses/Red/RedLeft2') => realpath(__DIR__ . '/ScriptClasses/Red/RedLeft2'),
                    realpath(__DIR__ . '/ScriptClasses/Red/RedLeft3') => realpath(__DIR__ . '/ScriptClasses/Red/RedLeft3'),
                    realpath(__DIR__ . '/ScriptClasses/Red/RedRight') => realpath(__DIR__ . '/ScriptClasses/Red/RedRight'),
                ],
            ],
        ];
    }

    /**
     * Tests the case when a more "wide" (more levels "higher") directory incorporates excluded subdirectories.
     *
     * @see ScriptDetector::excludeDirectory()
     */
    public function testDuplicateEvenWiderExclusionInMiddle(): void {
        $detector = (new ScriptDetectorMock(throwOnException: false))
            ->searchDirectory(__DIR__ . '/ScriptClasses', isRecursive: true)
            ->excludeDirectories([
                __DIR__ . '/ScriptClasses/Red/RedRight', // A "wider/higher" directory.
                __DIR__ . '/ScriptClasses/Red/RedRight/Subdirectory', // A subdirectory.
                __DIR__ . '/ScriptClasses/Red', // Super-"wide" directory.
                __DIR__ . '/ScriptClasses/Red/RedLeft',
                __DIR__ . '/ScriptClasses/Red/RedLeft2',
                __DIR__ . '/ScriptClasses/Red/RedLeft3',
            ]);

        // The assertions below should happen only if no exception is thrown during the detector object's setup.

        // Here we ensure (mainly, for "wider" exclusion) that only a "wider" directory is stored eventually
        // in a detector - we should not process affected directories more than once.
        assertSame(
            [
                realpath(__DIR__ . '/ScriptClasses/Red') => realpath(__DIR__ . '/ScriptClasses/Red'),
            ],
            $detector->getExcludedDirectoryPaths(),
        );

        assertSame(
            [
                'script'              => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\ScriptZero',
                'script-no-namespace' => 'ScriptNoNamespace',
            ],
            $detector->getFQClassNamesByScriptNames(),
        );
    }
}
