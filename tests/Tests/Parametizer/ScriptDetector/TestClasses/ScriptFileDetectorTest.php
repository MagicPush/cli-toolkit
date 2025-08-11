<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\TestClasses;

use MagicPush\CliToolkit\Parametizer\Parametizer;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptDetectorAbstract;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptDetectorRuntimeException;
use MagicPush\CliToolkit\Parametizer\ScriptDetector\ScriptFileDetector;
use MagicPush\CliToolkit\Tests\Tests\Parametizer\ScriptDetector\Mocks\ScriptFileDetectorMock;
use PHPUnit\Framework\Attributes\DataProvider;

use function PHPUnit\Framework\assertSame;

final class ScriptFileDetectorTest extends ScriptDetectorTestAbstract {
    #[DataProvider('provideSearchAndExclude')]
    /**
     * Tests {@see Parametizer} plain scripts detections.
     *
     * @param array<string, string> $expectedScripts
     * @see ScriptDetectorAbstract::detectBySettings()
     * @see ScriptFileDetector::processDetectedFileContents()
     * @see ScriptFileDetector::scriptPath()
     * @see ScriptFileDetector::scriptPaths()
     * @see ScriptFileDetector::processCustomDetections()
     * @see ScriptFileDetector::getDataProcessedAfterDetection()
     * @see ScriptDetectorAbstract::getDetectedData()
     */
    public function testSearchAndExclude(array $expectedScripts, ScriptFileDetector $detector): void {
        assertSame($expectedScripts, $detector->getDetectedData());
    }

    /**
     * @return array[]
     */
    public static function provideSearchAndExclude(): array {
        return [
            'different-script-variants-by-directories' => [
                'expectedScripts' => [
                    // Launcher script with its namespace 'used' + there are
                    // some lines between construction and execution.
                    'somewhat-l' => realpath(__DIR__ . '/../ScriptFiles/Blue/somewhat-l.php'),

                    // Launcher script with fully qualified name and execution in a single line.
                    'somewhat-l-another' => realpath(__DIR__ . '/../ScriptFiles/Blue/somewhat-l-another.php'),

                    /** Similar (as above) {@see Parametizer} pure config launches: */
                    'somewhat-another' => realpath(__DIR__ . '/../ScriptFiles/Blue/somewhat-another.php'),
                    'somewhat'         => realpath(__DIR__ . '/../ScriptFiles/Blue/somewhat.php'),

                    // This script contains a class definition:
                    'somewhat-class' => realpath(__DIR__ . '/../ScriptFiles/Blue/somewhat-class.php'),

                    /**
                     * These files are not (and should not be) detected:
                     *  * 'construction-l-mismatch':   wrong pairs of 'construct' and 'exec' substrings;
                     *  * 'construction-l-mismatch-2': same as above;
                     *  * 'no-config-no-l':            no exact 'construct' substrings detected,
                     *                                     {@see ScriptFileDetector::SUBSTR_*_CONSTRUCT};
                     *  * 'no-run-no-execute':         no exact 'exec' substrings detected,
                     *                                     {@see ScriptFileDetector::SUBSTR_*_EXEC};
                     *  * 'Something':                 lacks at least proper 'exec' substring
                     *                                 (for now, we assume that this is good enough to distinguish
                     *                                 plain scripts and script classes);
                     *  * 'somewhat-wrong-ext':        wrong extension file
                     *                                     (not {@see ScriptDetectorAbstract::FILE_EXTENSION}).
                     */
                ],
                'detector' => ScriptFileDetector::create(throwOnException: true)
                    ->searchDirectory(__DIR__ . '/../ScriptFiles/Blue'),
            ],

            'exact-script-files' => [
                'expectedScripts' => [
                    'somewhat'   => realpath(__DIR__ . '/../ScriptFiles/Red/somewhat.php'),
                    'somewhat-l' => realpath(__DIR__ . '/../ScriptFiles/Blue/somewhat-l.php'),
                ],
                'detector' => ScriptFileDetector::create(throwOnException: true)
                    ->scriptPath(__DIR__ . '/../ScriptFiles/Red/somewhat.php')
                    ->scriptPath(realpath(__DIR__ . '/../ScriptFiles/Blue/somewhat-l.php'))
            ],
            'exact-script-files-arr' => [
                'expectedScripts' => [
                    'somewhat'   => realpath(__DIR__ . '/../ScriptFiles/Red/somewhat.php'),
                    'somewhat-l' => realpath(__DIR__ . '/../ScriptFiles/Blue/somewhat-l.php'),
                ],
                'detector' => ScriptFileDetector::create(throwOnException: true)
                    ->scriptPaths([
                        __DIR__ . '/../ScriptFiles/Red/somewhat.php',
                        realpath(__DIR__) . '/../ScriptFiles/Blue/somewhat-l.php',
                    ]),
            ],
        ];
    }

    #[DataProvider('provideCustomSearchSettings')]
    /**
     * Tests the case when a detector is initialized or not with exceptions enabled / disabled.
     *
     * @see ScriptFileDetector::hasMinimalCustomSearchSettings()
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

        $detector = ScriptFileDetector::create($throwOnException);
        if ($isSearchDirectorySet) {
            $detector->searchDirectory(__DIR__ . '/../ScriptFiles/Red', isRecursive: true);
        }
        if ($isCustomSearchConditionSet) {
            $detector->scriptPath(__DIR__ . '/../ScriptFiles/Blue/somewhat-another.php');
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
                    'somewhat-l' => realpath(__DIR__ . '/../ScriptFiles/Red/Subdirectory/somewhat-l.php'),
                    'somewhat'   => realpath(__DIR__ . '/../ScriptFiles/Red/somewhat.php'),
                ],
            ],
            'has-custom-condition' => [
                'throwOnException'           => true,
                'isSearchDirectorySet'       => false,
                'isCustomSearchConditionSet' => true,
                'isExceptionExpected'        => false,
                'expectedData'               => [
                    'somewhat-another' => realpath(__DIR__ . '/../ScriptFiles/Blue/somewhat-another.php'),
                ],
            ],
            'has-both-conditions' => [
                'throwOnException'           => true,
                'isSearchDirectorySet'       => true,
                'isCustomSearchConditionSet' => true,
                'isExceptionExpected'        => false,
                'expectedData'               => [
                    'somewhat-another' => realpath(__DIR__ . '/../ScriptFiles/Blue/somewhat-another.php'),
                    'somewhat-l'       => realpath(__DIR__ . '/../ScriptFiles/Red/Subdirectory/somewhat-l.php'),
                    'somewhat'         => realpath(__DIR__ . '/../ScriptFiles/Red/somewhat.php'),
                ],
            ],
        ];
    }

    #[DataProvider('provideThrowOnException')]
    /**
     * Tests exact script {@see realpath()} tries.
     *
     * @see ScriptFileDetector::scriptPath()
     */
    public function testFailedRealPath(bool $throwOnException): void {
        if ($throwOnException) {
            $this->expectExceptionObject(
                new ScriptDetectorRuntimeException("Unable to retrieve the absolute path for '../asd'"),
            );
        }

        // The assertion below should happen only if no exception is thrown during the detector object's setup.
        assertSame(
            [
                'somewhat' => realpath(__DIR__ . '/../ScriptFiles/Blue/somewhat.php'),
            ],
            ScriptFileDetector::create($throwOnException)
                ->scriptPaths([
                    '../asd',
                    __DIR__ . '/../ScriptFiles/Blue/somewhat.php',
                ])
                ->getDetectedData(),
        );
    }

    #[DataProvider('provideThrowOnException')]
    /**
     * Tests duplicate exact script path requested.
     *
     * @see ScriptFileDetector::scriptPath()
     */
    public function testDuplicateExactScriptPath(bool $throwOnException): void {
        if ($throwOnException) {
            $this->expectExceptionObject(
                new ScriptDetectorRuntimeException(
                    sprintf(
                        "Duplicate script path requested: %s",
                        realpath(__DIR__ . '/../ScriptFiles/Blue/somewhat.php'),
                    ),
                ),
            );
        }

        // The assertion below should happen only if no exception is thrown during the detector object's setup.
        assertSame(
            [
                'somewhat'   => realpath(__DIR__ . '/../ScriptFiles/Blue/somewhat.php'),
                'somewhat-l' => realpath(__DIR__ . '/../ScriptFiles/Blue/somewhat-l.php'),
            ],
            ScriptFileDetector::create($throwOnException)
                ->scriptPaths([
                    realpath(__DIR__ . '/../ScriptFiles/Blue/somewhat.php'), // Original by an absolute path
                    __DIR__ . '/../ScriptFiles/Blue/somewhat-l.php',
                    __DIR__ . '/../ScriptFiles/Blue/somewhat.php',           // Duplicate by a relative path
                ])
                ->getDetectedData(),
        );
    }

    #[DataProvider('provideThrowOnException')]
    /**
     * Tests script file extension's validation.
     *
     * @see ScriptFileDetector::processCustomDetections()
     */
    public function testWrongExtension(bool $throwOnException): void {
        if ($throwOnException) {
            $this->expectExceptionObject(
                new ScriptDetectorRuntimeException(
                    sprintf(
                        "Missing extension 'php' for the script file: %s",
                        realpath(__DIR__ . '/../ScriptFiles/Blue/somewhat-wrong-ext.txt')
                    ),
                ),
            );
        }

        // The assertion below should happen only if no exception is thrown during the detector object's setup.
        assertSame(
            [
                'somewhat' => realpath(__DIR__ . '/../ScriptFiles/Blue/somewhat.php'),
            ],
            ScriptFileDetector::create($throwOnException)
                ->scriptPaths([
                    __DIR__ . '/../ScriptFiles/Blue/somewhat-wrong-ext.txt',
                    __DIR__ . '/../ScriptFiles/Blue/somewhat.php',
                ])
                ->getDetectedData(),
        );
    }

    #[DataProvider('provideThrowOnException')]
    /**
     * Tests failure to read script file's contents.
     *
     * @see ScriptFileDetector::processCustomDetections()
     */
    public function testFailedReadingFile(bool $throwOnException): void {
        if ($throwOnException) {
            $this->expectExceptionObject(
                new ScriptDetectorRuntimeException('Script file is not readable: /etc/shadow'),
            );
        }

        // The assertion below should happen only if no exception is thrown during the detector object's setup.
        assertSame(
            [
                'somewhat' => realpath(__DIR__ . '/../ScriptFiles/Blue/somewhat.php'),
            ],
            ScriptFileDetector::create($throwOnException)
                ->scriptPaths([
                    '/etc/shadow',
                    __DIR__ . '/../ScriptFiles/Blue/somewhat.php',
                ])
                ->getDetectedData(),
        );
    }

    #[DataProvider('provideScriptParseException')]
    /**
     * Tests different exception processing depending on exact script or directory search.
     *
     * @see ScriptFileDetector::processDetectedFileContentsInternal()
     * @see ScriptFileDetector::processDetectedFileContents()
     * @see ScriptFileDetector::processCustomDetections()
     */
    public function testScriptParseException(
        bool $throwOnException,
        bool $isExactFileSearch,
        bool $isExceptionExpected,
    ): void {
        if ($isExceptionExpected) {
            $this->expectExceptionObject(
                new ScriptDetectorRuntimeException(
                    sprintf(
                        "Script file '%s' should contain one of these pairs of substrings:"
                            . " 'Parametizer::newConfig(' + '->run()' OR 'ScriptClassLauncher::create(' + '->execute()'",
                        realpath(__DIR__ . '/../ScriptFiles/Red/no-run-no-execute.php'),
                    ),
                ),
            );
        }

        $detector = ScriptFileDetector::create($throwOnException);
        if ($isExactFileSearch) {
            $detector->scriptPaths([
                __DIR__ . '/../ScriptFiles/Red/no-run-no-execute.php',
                __DIR__ . '/../ScriptFiles/Red/somewhat.php',
            ]);
        } else {
            $detector->searchDirectory(__DIR__ . '/../ScriptFiles/Red', isRecursive: false);
        }

        // The assertion below should happen only if no exception is thrown during the detector object's setup.
        assertSame(
            [
                'somewhat' => realpath(__DIR__ . '/../ScriptFiles/Red/somewhat.php'),
            ],
            $detector->getDetectedData(),
        );
    }

    /**
     * @return array[]
     */
    public static function provideScriptParseException(): array {
        return [
            /**
             * During directory search, if a detected file is not considered as {@see Parametizer}-powered script,
             * then such a script is always silently ignored.
             */
            'directory-ignore-not-file' => [
                'throwOnException'    => true,
                'isExactFileSearch'   => false,
                'isExceptionExpected' => false,
            ],
            'directory-ignore-disabled' => [
                'throwOnException'    => false,
                'isExactFileSearch'   => false,
                'isExceptionExpected' => false,
            ],

            // ... However during exact file search invalid scripts are mentioned by an exception thrown,
            // until the throwing itself is not disabled.
            'file-throw' => [
                'throwOnException'    => true,
                'isExactFileSearch'   => true,
                'isExceptionExpected' => true,
            ],
            'file-ignore' => [
                'throwOnException'    => false,
                'isExactFileSearch'   => true,
                'isExceptionExpected' => false,
            ],
        ];
    }

    #[DataProvider('provideThrowOnException')]
    /**
     * Tests duplicate exception for different script paths detection with the same basename.
     *
     * @see ScriptFileDetector::processDetectedFileContentsInternal()
     */
    public function testDuplicateBasenameByDifferentPaths(bool $throwOnException): void {
        if ($throwOnException) {
            $this->expectExceptionObject(
                new ScriptDetectorRuntimeException(
                    sprintf(
                        "Duplicate script basename 'somewhat' for path '%s'. Already detected: %s",
                        realpath(__DIR__ . '/../ScriptFiles/Yellow/somewhat.php'),
                        realpath(__DIR__ . '/../ScriptFiles/Red/somewhat.php'),
                    ),
                ),
            );
        }

        // The assertion below should happen only if no exception is thrown during the detector object's setup.
        assertSame(
            [
                'somewhat'         => realpath(__DIR__ . '/../ScriptFiles/Red/somewhat.php'),
                'somewhat-another' => realpath(__DIR__ . '/../ScriptFiles/Green/somewhat-another.php'),
            ],
            ScriptFileDetector::create($throwOnException)
                ->searchDirectories(
                    [
                        __DIR__ . '/../ScriptFiles/Red',
                        __DIR__ . '/../ScriptFiles/Yellow', // Duplicate basename inside.
                        __DIR__ . '/../ScriptFiles/Green',
                    ],
                    isRecursive: false,
                )
                ->getDetectedData(),
        );
    }

    /**
     * Tests internal properties are cleared correctly each time the detection process is launched.
     *
     * @see ScriptFileDetector::clearMemoryCache()
     * @see ScriptDetectorAbstract::detect()
     */
    public function testDuplicateCallUniqueDetectedEntries(): void {
        $detector = ScriptFileDetectorMock::create(throwOnException: true)
            ->searchDirectory(__DIR__ . '/../ScriptFiles', isRecursive: true)
            ->excludeDirectories([
                __DIR__ . '/../ScriptFiles/Red',
                __DIR__ . '/../ScriptFiles/Blue',
            ]);

        // Launch the detection process for the first time. Assert the state of the internal property:
        $detector->getDetectedData();
        assertSame(
            [
                'somewhat'         => realpath(__DIR__ . '/../ScriptFiles/Yellow/somewhat.php'),
                'somewhat-another' => realpath(__DIR__ . '/../ScriptFiles/Green/somewhat-another.php'),
            ],
            $detector->_getDetectedFilePathsByAliases(),
        );

        // Alter the search setup. Launch the detection again and observe that the internal property was filled from
        // scratch - no duplication happens and no 'outdated' elements exist:
        $detector->excludeDirectory(__DIR__ . '/../ScriptFiles/Green');
        $detector->getDetectedData();
        assertSame(
            [
                'somewhat' => realpath(__DIR__ . '/../ScriptFiles/Yellow/somewhat.php'),
            ],
            $detector->_getDetectedFilePathsByAliases(),
        );
    }
}
