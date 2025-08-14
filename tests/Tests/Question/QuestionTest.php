<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Tests\Tests\Question;

use MagicPush\CliToolkit\Question\Question;
use MagicPush\CliToolkit\Tests\Tests\TestCaseAbstract;
use PHPUnit\Framework\Attributes\DataProvider;

use function PHPUnit\Framework\assertSame;

final class QuestionTest extends TestCaseAbstract {
    /**
     * Tests a trivial case with a question and any value.
     * A script continues its execution anyway whatever input is given.
     *
     * @see Question::askAway()
     * @see Question::create()
     * @see Question::ask()
     */
    #[DataProvider('provideQuestionAnyValue')]
    public function testQuestionAnyValue(string $inputValue, string $expectedOutputSubstring): void {
        assertSame(
            "Type something here: Your input: '{$expectedOutputSubstring}'",
            static::assertNoErrorsOutput(__DIR__ . '/scripts/simple-question.php', stdinLines: [$inputValue])
                ->getStdOut(),
        );
    }

    /**
     * @return array[]
     */
    public static function provideQuestionAnyValue(): array {
        return [
            'empty' => [
                'inputValue'              => '',
                'expectedOutputSubstring' => '',
            ],
            'space' => [
                'inputValue'              => ' ',
                'expectedOutputSubstring' => '',
            ],
            'symbol' => [
                'inputValue'              => '5',
                'expectedOutputSubstring' => '5',
            ],
            'phrase-trimmed' => [
                'inputValue'              => ' cool story, bro ',
                'expectedOutputSubstring' => 'cool story, bro',
            ],
        ];
    }

    /**
     * Tests the possibility to ask more than a single question and treat different input accordingly.
     *
     * @see Question::getInput()
     */
    public function testMultipleQuestions(): void {
        assertSame(
            <<<TEXT
            Q1: A1: a
            Q2: A2: b
            Q3: A3: c

            TEXT,
            static::assertNoErrorsOutput(__DIR__ . '/scripts/multiple-questions.php', stdinLines: ['a', 'b', 'c'])
                ->getStdOut(),
        );
    }

    /**
     * Tests changing a substring after a question text.
     *
     * @see Question::substringAfterQuestion()
     * @see Question::showQuestion()
     */
    #[DataProvider('provideQuestionPostfix')]
    public function testQuestionPostfix(string $questionPostfix, string $expectedOutputSubstring): void {
        assertSame(
            "The ultimate question of life, universe and everything {$expectedOutputSubstring}: 42",
            static::assertNoErrorsOutput(__DIR__ . '/scripts/question-postfix.php', "'{$questionPostfix}'", ['42'])
                ->getStdOut(),
        );
    }

    /**
     * @return array[]
     */
    public static function provideQuestionPostfix(): array {
        return [
            'symbol' => [
                'questionPostfix'         => ':',
                'expectedOutputSubstring' => 'else.:Answer',
            ],
            'substring-with-new-line' => [
                'questionPostfix'         => PHP_EOL . '> ', // Ends with a space character intentionally.
                'expectedOutputSubstring' => 'else.' . PHP_EOL . '> Answer',
            ],
        ];
    }

    /**
     * Tests providing a default answer if there is empty input.
     *
     * @see Question::defaultAnswer()
     * @see Question::getAnswerOrDefault()
     * @see Question::showQuestion()
     */
    public function testDefaultAnswer(): void {
        // If there is no answer (just 'new line' input), we should see a default answer:
        assertSame(
            'What would you like to drink? (tea): Answer: tea',
            static::assertNoErrorsOutput(__DIR__ . '/scripts/default-answer.php', stdinLines: [''])
                ->getStdOut(),
        );

        // ... But if an answer is provided from input, it takes place:
        assertSame(
            'What would you like to drink? (tea): Answer: vodka',
            static::assertNoErrorsOutput(__DIR__ . '/scripts/default-answer.php', stdinLines: ['vodka'])
                ->getStdOut(),
        );
    }

    /**
     * Tests possible answers various settings.
     * Also, tests that a script will ask a question again and again until the right answer is received.
     *
     * @param string[] $possibleAnswers
     * @param bool     $isCaseSensitive
     * @param string   $errorMessage
     * @param string[] $inputValues
     * @param string   $expectedOutput
     * @see Question::possibleAnswers()
     * @see Question::showQuestion() Here possible answers are shown.
     * @see Question::validateAnswer()
     * @see Question::ask() Here the question is repeated indefinitely until a valid answer is provided.
     */
    #[DataProvider('providePossibleAnswers')]
    public function testPossibleAnswers(
        array $possibleAnswers,
        bool $isCaseSensitive,
        string $errorMessage,
        array $inputValues,
        string $expectedOutput,
    ): void {
        assertSame(
            $expectedOutput,
            static::assertNoErrorsOutput(
                __DIR__ . '/scripts/possible-answers.php',
                sprintf("'%s' %d '%s'", serialize($possibleAnswers), $isCaseSensitive, $errorMessage),
                $inputValues,
            )
                ->getStdOut(),
        );
    }

    /**
     * @return array[]
     */
    public static function providePossibleAnswers(): array {
        return [
            'case-sensitive-several-tries' => [
                'possibleAnswers' => ['Red', 'Blue'],
                'isCaseSensitive' => true,
                'errorMessage'    => 'Unavailable team.',
                'inputValues'     => ['red', 'RED', 'ReD', 'reD', '    Red'],
                'expectedOutput'  => <<<TEXT
                                     Pick your team: Red / Blue > Unavailable team. Possible answers: Red, Blue

                                     Pick your team: Red / Blue > Unavailable team. Possible answers: Red, Blue

                                     Pick your team: Red / Blue > Unavailable team. Possible answers: Red, Blue

                                     Pick your team: Red / Blue > Unavailable team. Possible answers: Red, Blue

                                     Pick your team: Red / Blue > Chosen team: Red
                                     TEXT,
            ],
            'case-insensitive' => [
                'possibleAnswers' => ['Red', 'Blue'],
                'isCaseSensitive' => false,
                'errorMessage'    => 'Unavailable team.',
                'inputValues'     => ['RED'],
                'expectedOutput'  => 'Pick your team: Red / Blue > Chosen team: Red',
            ],
            'empty-as-settable-error-message' => [
                'possibleAnswers' => ['Red', 'Blue'],
                'isCaseSensitive' => false,
                'errorMessage'    => '',
                'inputValues'     => ['zxc', 'blue'],
                'expectedOutput'  => <<<TEXT
                                     Pick your team: Red / Blue > Possible answers: Red, Blue

                                     Pick your team: Red / Blue > Chosen team: Blue
                                     TEXT,
            ],
            'single-possible-answer' => [
                'possibleAnswers' => ['Red'],
                'isCaseSensitive' => false,
                'errorMessage'    => 'Actually, you do not have any choice :)',
                'inputValues'     => ['Blue', 'red'],
                'expectedOutput'  => <<<TEXT
                                     Pick your team: Red > Actually, you do not have any choice :) Possible answers: Red

                                     Pick your team: Red > Chosen team: Red
                                     TEXT,
            ],
        ];
    }

    /**
     * Tests answer validator pattern settings.
     * Also, tests that a script will ask a question again and again until the right answer is received.
     *
     * @see Question::answerValidatorPattern()
     * @see Question::validateAnswer()
     * @see Question::ask() Here the question is repeated indefinitely until a valid answer is provided.
     */
    public function testAnswerValidatorPattern(): void {
        assertSame(
            <<<TEXT
            Type a natural number (integer, >= 1): Not a natural number.

            Type a natural number (integer, >= 1): Not a natural number.

            Type a natural number (integer, >= 1): Not a natural number.

            Type a natural number (integer, >= 1): Your choice: 2
            TEXT,
            static::assertNoErrorsOutput(
                __DIR__ . '/scripts/answer-validator-pattern.php',
                stdinLines: ['one', '2.0', '0', '2'],
            )
                ->getStdOut(),
        );
    }

    /**
     * Tests cases of validators conflicts - only a single validator is allowed.
     *
     * @see Question::possibleAnswers()
     * @see Question::answerValidatorPattern()
     */
    #[DataProvider('provideValidatorConflict')]
    public function testValidatorConflict(bool $isListBeforePattern, string $expectedError): void {
        static::assertAnyErrorOutput(
            __DIR__ . '/scripts/validator-conflict.php',
            $expectedError,
            (string) (int) $isListBeforePattern,
            shouldAssertExitCode: false,
            shouldAssertStdErr: false,
        );
    }

    /**
     * @return array[]
     */
    public static function provideValidatorConflict(): array {
        return [
            'list-before-pattern' => [
                'isListBeforePattern' => false,
                'expectedError'       => 'Can not set possible answers: answer validator pattern is set',
            ],
            'pattern-before-list' => [
                'isListBeforePattern' => true,
                'expectedError'       => 'Can not set answer validator pattern: a list of possible answers is set',
            ],
        ];
    }

    /**
     * Tests standard "yes/no" confirmation.
     *
     * @see Question::confirm()
     * @see Question::possibleAnswers()
     * @see Question::defaultAnswer()
     * @see Question::ask()
     */
    #[DataProvider('provideConfirm')]
    public function testConfirm(string $input, string $expectedOutput): void {
        assertSame(
            $expectedOutput,
            static::assertNoErrorsOutput(__DIR__ . '/scripts/confirm.php', stdinLines: [$input])
                ->getStdOut(),
        );
    }

    /**
     * @return array[]
     */
    public static function provideConfirm(): array {
        return [
            'Y' => [
                'input'          => 'Y',
                'expectedOutput' => 'Do you confirm it? Y / N (N): Confirmed: yes',
            ],
            'y' => [
                'input'          => 'y',
                'expectedOutput' => 'Do you confirm it? Y / N (N): Confirmed: yes',
            ],
            'N' => [
                'input'          => 'N',
                'expectedOutput' => 'Do you confirm it? Y / N (N): Confirmed: no',
            ],
            'n' => [
                'input'          => 'n',
                'expectedOutput' => 'Do you confirm it? Y / N (N): Confirmed: no',
            ],
            'default' => [
                'input'          => '', // Empty line input yields a default (negative) answer.
                'expectedOutput' => 'Do you confirm it? Y / N (N): Confirmed: no',
            ],
            'invalid' => [
                'input'          => 'Yes, sir!' . PHP_EOL . 'y', // Each "line" is considered as a separate user input.
                'expectedOutput' => <<<TEXT
                                    Do you confirm it? Y / N (N): Invalid answer. Possible answers: Y, N

                                    Do you confirm it? Y / N (N): Confirmed: yes
                                    TEXT,
            ]
        ];
    }

    /**
     * Tests a ready-to-use method to confirm a script further execution or interrupt it with `exit(1)`.
     * Also, tests an optional message shown before a script interruption.
     *
     * @see Question::confirmOrDie()
     * @see Question::possibleAnswers()
     * @see Question::defaultAnswer()
     * @see Question::ask()
     */
    #[DataProvider('provideConfirmOrDie')]
    public function testConfirmOrDie(
        string $input,
        string $dieMessage,
        string $expectedOutput,
        int $expectedExitCode,
    ): void {
        assertSame(
            $expectedOutput,
            static::assertNoErrorsOutput(
                __DIR__ . '/scripts/confirm-or-die.php',
                "'{$dieMessage}'",
                [$input],
                $expectedExitCode,
            )
                ->getStdOut(),
        );
    }

    public static function provideConfirmOrDie(): array {
        return [
            'yes-continue' => [
                'input'            => 'Yup' . PHP_EOL . 'y', // Each "line" is considered as a separate user input.
                'dieMessage'       => 'Does not matter',
                'expectedOutput'   => <<<TEXT
                                    Confirm to proceed Y / N (N): Invalid answer. Possible answers: Y, N

                                    Confirm to proceed Y / N (N): Execution commencing...
                                    TEXT,
                'expectedExitCode' => 0,
            ],
            'no-die' => [
                'input'            => 'n',
                'dieMessage'       => '',
                'expectedOutput'   => 'Confirm to proceed Y / N (N): ',
                'expectedExitCode' => 1,
            ],
            'empty-die' => [
                'input'            => '',
                'dieMessage'       => '',
                'expectedOutput'   => 'Confirm to proceed Y / N (N): ',
                'expectedExitCode' => 1,
            ],
            'no-die-message' => [
                'input'            => 'N',
                'dieMessage'       => 'Execution aborted!',
                'expectedOutput'   => 'Confirm to proceed Y / N (N): Execution aborted!',
                'expectedExitCode' => 1,
            ],
        ];
    }
}
