<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\TestClasses;

use MagicPush\CliToolkit\Parametizer\Script\ScriptAbstract;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptClassDetector;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptDetectorAbstract;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptDetectorRuntimeException;
use MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\Mocks\ScriptClassDetectorMock;
use MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Blue\SomethingZeroShadow;
use MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedBase;
use MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Something5;
use MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\SomethingX;
use MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\SomethingZero;
use MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\AnotherThing;
use PHPUnit\Framework\Attributes\DataProvider;

use function PHPUnit\Framework\assertSame;

class ScriptClassDetectorTest extends ScriptDetectorTestAbstract {
    #[DataProvider('provideSearchAndExclude')]
    /**
     * Tests {@see ScriptAbstract} classes detections.
     *
     * @param array<string, string> $expectedClasses
     * @see ScriptClassDetector::detectBySettings()
     * @see ScriptClassDetector::processDetectedFileContents()
     * @see ScriptClassDetector::scriptClassName()
     * @see ScriptClassDetector::scriptClassNames()
     * @see ScriptClassDetector::processCustomDetections()
     * @see ScriptClassDetector::getDataProcessedAfterDetection()
     * @see ScriptDetectorAbstract::getDetectedData()
     */
    public function testSearchAndExclude(array $expectedClasses, ScriptClassDetector $detector): void {
        assertSame($expectedClasses, $detector->getDetectedData());
    }

    /**
     * @return array[]
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public static function provideSearchAndExclude(): array {
        return [
            'different-classes-types-by-directories' => [
                'expectedClasses' => [
                    // Other class(es) detected along the way:
                    'red:something4' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft22\Something4',

                    /**
                     * The script class extended from {@see ScriptAbstract}, but located in another namespace,
                     * near a related abstract subclass {@see RedBase}:
                     */
                    'something-x' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\SomethingX',

                    // Other class(es) detected along the way:
                    'red:something8' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight\Subdirectory\Something8',
                    'red:something7' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedRight\Something7',
                    'red:something2' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft\Something2',
                    'red:something1' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\Something1',
                    'red:something3' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft2\Something3',
                    'red:something6' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Subdirectory\Something6',
                    'red:something5' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Something5',

                    // The script class without a namespace:
                    'something-no-namespace' => 'SomethingNoNamespace',

                    // The "final" script class:
                    'something' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\SomethingZero',

                    /**
                     * {@see RedBase} class itself is not detected, because it is abstract.
                     *
                     * Classes not connected to {@see ScriptAbstract} are ignored,
                     * specifically {@see AnotherThing} that has the same methods, but a completely different parent.
                     */
                ],
                'detector' => (new ScriptClassDetector(throwOnException: true))
                    ->searchDirectory(__DIR__ . '/../ScriptClasses', isRecursive: true)
                    ->excludeDirectory(__DIR__ . '/../ScriptClasses/Blue'),
            ],

            'exact-script-classes' => [
                'expectedClasses' => [
                    'red:something1' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\Something1',
                    'red:something4' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft22\Something4',
                    'something-x'    => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\SomethingX',
                ],
                'detector' => (new ScriptClassDetector(throwOnException: true))
                    ->scriptClassName(
                        \MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\Something1::class,
                    )
                    ->scriptClassName(
                        \MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft22\Something4::class,
                    )
                    ->scriptClassName(
                        SomethingX::class,
                    ),
            ],
            'exact-script-classes-arr' => [
                'expectedClasses' => [
                    'red:something1' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\Something1',
                    'red:something4' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft22\Something4',
                    'something-x'    => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\SomethingX',
                ],
                'detector' => (new ScriptClassDetector(throwOnException: true))
                    ->scriptClassNames([
                        \MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\Something1::class,
                        \MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft22\Something4::class,
                        SomethingX::class,
                    ]),
            ],
        ];
    }

    #[DataProvider('provideCustomSearchSettings')]
    /**
     * Tests the case when a detector is initialized or not with exceptions enabled / disabled.
     *
     * @see ScriptClassDetector::hasMinimalCustomSearchSettings()
     * @see ScriptDetectorAbstract::detectBySettings()
     */
    public function testCustomSearchSettings(
        bool $throwOnException,
        bool $isSearchDirectorySet,
        bool $isCustomSearchConditionSet,
        bool $isExceptionExpected,
        array $expectedData,
    ): void {
        if ($isExceptionExpected) {
            $this->expectExceptionObject(new ScriptDetectorRuntimeException('There are no search settings specified.'));
        }

        $detector = new ScriptClassDetector($throwOnException);
        if ($isSearchDirectorySet) {
            $detector->searchDirectory(__DIR__ . '/../ScriptClasses/Red/RedLeft3', isRecursive: true);
        }
        if ($isCustomSearchConditionSet) {
            $detector->scriptClassName(SomethingX::class);
        }

        // This assertion should happen only if no exception is thrown during the detector object's setup:
        assertSame($expectedData, $detector->getDetectedData());
    }

    /**
     * @return array[]
     */
    public static function provideCustomSearchSettings(): array {
        return [
            'no-condition-throw' => [
                'throwOnException'           => true,
                'isSearchDirectorySet'       => false,
                'isCustomSearchConditionSet' => false,
                'isExceptionExpected'        => true,
                'expectedData'               => ['an-exception-should-be-thrown'],
            ],
            'no-condition-ignore' => [
                'throwOnException'           => false,
                'isSearchDirectorySet'       => false,
                'isCustomSearchConditionSet' => false,
                'isExceptionExpected'        => false,
                'expectedData'               => [
                    // Nothing should be detected in this case.
                ],
            ],
            'has-standard-condition' => [
                'throwOnException'           => true,
                'isSearchDirectorySet'       => true,
                'isCustomSearchConditionSet' => false,
                'isExceptionExpected'        => false,
                'expectedData'               => [
                    'red:something6' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Subdirectory\Something6',
                    'red:something5' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Something5',
                ],
            ],
            'has-custom-condition' => [
                'throwOnException'           => true,
                'isSearchDirectorySet'       => false,
                'isCustomSearchConditionSet' => true,
                'isExceptionExpected'        => false,
                'expectedData'               => [
                    'something-x' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\SomethingX',
                ],
            ],
            'has-both-conditions' => [
                'throwOnException'           => true,
                'isSearchDirectorySet'       => true,
                'isCustomSearchConditionSet' => true,
                'isExceptionExpected'        => false,
                'expectedData'               => [
                    'something-x'    => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\SomethingX',
                    'red:something6' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Subdirectory\Something6',
                    'red:something5' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Something5',
                ],
            ],
        ];
    }

    #[DataProvider('provideThrowOnException')]
    /**
     * Tests the subclass validation happening while loading exact script classes.
     *
     * @see ScriptClassDetector::processCustomDetections()
     */
    public function testFQNameNotSubclass(bool $throwOnException): void {
        if ($throwOnException) {
            $this->expectExceptionObject(
                new ScriptDetectorRuntimeException(
                    sprintf(
                        "'%s' must be a subclass of '%s'",
                        AnotherThing::class,
                        ScriptAbstract::class,
                    ),
                ),
            );
        }

        // The assertion below should happen only if no exception is thrown during the detector object's setup.
        assertSame(
            [
                'something-x' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\SomethingX',
            ],
            (new ScriptClassDetector($throwOnException))
                ->scriptClassNames([
                    SomethingX::class,
                    AnotherThing::class,
                ])
                ->getDetectedData(),
        );
    }

    #[DataProvider('provideThrowOnException')]
    /**
     * Tests duplicate fully qualified names processing.
     *
     * @see ScriptClassDetector::scriptClassName()
     */
    public function testDuplicateFQNames(bool $throwOnException): void {
        if ($throwOnException) {
            $this->expectExceptionObject(
                new ScriptDetectorRuntimeException(
                    'Duplicate fully qualified class name search requested: ' . SomethingX::class,
                ),
            );
        }

        $detector = (new ScriptClassDetectorMock($throwOnException))
            ->scriptClassNames([
                SomethingX::class,
                Something5::class,
                SomethingX::class,    // Duplicate (first; is mentioned in the exception being thrown).
                SomethingZero::class,
                SomethingZero::class, // Duplicate.
            ]);

        // The assertions below should happen only if no exception is thrown during the detector object's setup.

        // Here we ensure that no duplicate entry is added.
        assertSame(
            [
                SomethingX::class    => SomethingX::class,
                Something5::class    => Something5::class,
                SomethingZero::class => SomethingZero::class,
            ],
            $detector->getSearchedFQClassNames(),
        );

        assertSame(
            [
                'something-x'    => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\SomethingX',
                'red:something5' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Something5',
                'something'      => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\SomethingZero',
            ],
            $detector->getDetectedData(),
        );
    }

    /**
     * Tests internal properties are cleared correctly each time the detection process is launched.
     *
     * @see ScriptClassDetector::clearMemoryCache()
     * @see ScriptDetectorAbstract::detect()
     */
    public function testDuplicateCallUniqueDetectedEntries(): void {
        $detector = (new ScriptClassDetectorMock(throwOnException: true))
            ->scriptClassName(SomethingX::class)
            ->searchDirectory(__DIR__ . '/../ScriptClasses/Red/RedLeft3', isRecursive: true);

        // Launch the detection process for the first time:
        assertSame(
            [
                'something-x'    => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\SomethingX',
                'red:something6' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Subdirectory\Something6',
                'red:something5' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Something5',
            ],
            $detector->getDetectedData(),
        );
        // Assert the state of the internal property:
        assertSame(
            [
                'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\SomethingX',
                'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Subdirectory\Something6',
                'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Something5',
            ],
            $detector->getDetectedFQClassNames(),
        );

        // Alter the search setup. Launch the detection again and observe that the internal property was filled from
        // scratch - no duplication happens and no 'outdated' elements exist:
        $detector->excludeDirectory(__DIR__ . '/../ScriptClasses/Red/RedLeft3/Subdirectory');
        assertSame(
            [
                'something-x'    => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\SomethingX',
                'red:something5' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Something5',
            ],
            $detector->getDetectedData(),
        );
        assertSame(
            [
                'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\SomethingX',
                'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\Red\RedLeft3\Something5',
            ],
            $detector->getDetectedFQClassNames(),
        );
    }

    #[DataProvider('provideThrowOnException')]
    /**
     * Tests script name duplicates validation.
     *
     * @see ScriptClassDetector::getDataProcessedAfterDetection()
     */
    public function testDuplicateScriptName(bool $throwOnException): void {
        if ($throwOnException) {
            $this->expectExceptionObject(
                new ScriptDetectorRuntimeException(
                    sprintf(
                        "Duplicate script name 'something' detected in class '%s'. Already registered class: %s",
                        SomethingZeroShadow::class,
                        SomethingZero::class,
                    ),
                ),
            );
        }

        // The assertion below should happen only if no exception is thrown during the detector object's setup.
        assertSame(
            [
                'something' => 'MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\ScriptClasses\SomethingZero',
            ],
            (new ScriptClassDetector($throwOnException))
                ->scriptClassNames([
                    SomethingZero::class,
                    SomethingZeroShadow::class,
                ])
                ->getDetectedData(),
        );
    }
}
