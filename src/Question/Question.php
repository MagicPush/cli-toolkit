<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Question;

use LogicException;
use RuntimeException;

class Question {
    protected const string QUESTION_POSTFIX = ': ';

    protected string $answer;

    /** @var string[] * */
    protected array $possibleAnswers = [];

    protected string  $questionPostfix;
    protected string  $defaultAnswer          = '';
    protected bool    $isAnswerCaseSensitive  = false;
    protected ?string $answerValidatorPattern = null;
    protected string  $validationErrorMessage;

    protected readonly QuestionFormatter $formatter;


    /**
     * Just a handy alternative for {@see __construct()}.
     */
    public static function create(string $question): static {
        return new static($question);
    }

    public function __construct(protected readonly string $question) {
        $this->questionPostfix = static::QUESTION_POSTFIX;
        $this->formatter       = QuestionFormatter::createForStdOut();
    }

    /**
     * Ask user to enter the answer from the keyboard and get the answer.
     */
    public function ask(): string {
        $this->showQuestion();
        $answer = $this->getAnswerOrDefault();

        try {
            $this->validateAnswer($answer);
            $this->answer = $answer;
        } catch (RuntimeException $e) {
            echo $e->getMessage() . PHP_EOL . PHP_EOL;

            $this->ask();
        }

        return $this->answer;
    }

    /**
     * Ask a question with possible yes/no answers.
     */
    public static function confirm(string $question): bool {
        $answer = (new static($question))
            ->possibleAnswers(['Y', 'N'])
            ->defaultAnswer('N')
            ->ask();

        return 'Y' === mb_strtoupper($answer);
    }

    /**
     * Ask a question with possible yes/no answers, exit if got "no" answer.
     *
     * @param string $dieMessage Optional message to output before script's execution is interrupted
     *                           (if no "confirmation" happened).
     */
    public static function confirmOrDie(string $question, string $dieMessage = ''): void {
        if (!static::confirm($question)) {
            if ($dieMessage) {
                echo $dieMessage;
            }

            exit(1);
        }
    }

    /**
     * What to add after the question string: `My question%SUBSTRING% `. Like a new line character, colon, etc.
     *
     * By default the substring is {@see QUESTION_POSTFIX}.
     */
    public function substringAfterQuestion(string $substring): static {
        $this->questionPostfix = $substring;

        return $this;
    }

    public function defaultAnswer(string $defaultAnswer): static {
        $this->defaultAnswer = $defaultAnswer;

        return $this;
    }

    /**
     * @param string[] $possibleAnswers
     */
    public function possibleAnswers(
        array $possibleAnswers,
        bool $isCaseSensitive = false,
        string $errorMessage = 'Invalid answer',
    ): static {
        if ($this->answerValidatorPattern) {
            throw new LogicException('Can`t set possible answers when answer validator pattern is set');
        }

        $this->possibleAnswers        = $possibleAnswers;
        $this->isAnswerCaseSensitive  = $isCaseSensitive;
        $this->validationErrorMessage = $errorMessage;

        return $this;
    }

    public function answerValidatorPattern(
        string $answerValidatorPattern,
        string $errorMessage = 'Invalid answer',
    ): static {
        if ($this->possibleAnswers) {
            throw new LogicException('Can`t set answer validator pattern when possible answers are set');
        }

        $this->answerValidatorPattern = $answerValidatorPattern;
        $this->validationErrorMessage = $errorMessage;

        return $this;
    }

    /**
     * Asking the question.
     */
    protected function showQuestion(): void {
        echo $this->formatter->question($this->question)
            . ($this->possibleAnswers ? ' ' . $this->formatter->value(implode(' / ', $this->possibleAnswers)) : '')
            . ($this->defaultAnswer ? $this->formatter->defaultValue(" ({$this->defaultAnswer})") : '')
            . $this->questionPostfix;
    }

    /**
     * Return a user answer or default value.
     */
    protected function getAnswerOrDefault(): string {
        $input = trim($this->getInput());

        return '' === $input ? $this->defaultAnswer : $input;
    }

    /**
     * Get a user input.
     */
    protected function getInput(): string {
        return fgets(STDIN);
    }

    /**
     * Validate an answer by a pattern or a list of possible values (if anything of that is set).
     */
    protected function validateAnswer(string $answer): void {
        if ($this->answerValidatorPattern && !preg_match($this->answerValidatorPattern, $answer)) {
            throw new RuntimeException(QuestionFormatter::createForStdErr()->error($this->validationErrorMessage));
        }

        if ($this->possibleAnswers) {
            if ($this->isAnswerCaseSensitive) {
                $answerToCompare = $answer;
                $possibleAnswers = $this->possibleAnswers;
            } else {
                $answerToCompare = mb_strtolower($answer);
                $possibleAnswers = array_map(function ($possibleAnswer) {
                    return mb_strtolower($possibleAnswer);
                }, $this->possibleAnswers);
            }

            if (in_array($answerToCompare, $possibleAnswers)) {
                return;
            }

            $formatter = QuestionFormatter::createForStdErr();
            $message   = $formatter->error($this->validationErrorMessage . '. Possible answers: ')
                . $formatter->value(implode(', ', $this->possibleAnswers));

            throw new RuntimeException($message);
        }
    }
}
