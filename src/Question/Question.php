<?php

declare(strict_types=1);

namespace MagicPush\CliToolkit\Question;

use LogicException;
use RuntimeException;

class Question {
    protected string $answer;

    /** @var string[] * */
    protected array $possibleAnswers = [];

    protected string  $questionPostfix        = ': ';
    protected string  $defaultAnswer          = '';
    protected bool    $isAnswerCaseSensitive  = false;
    protected ?string $answerValidatorPattern = null;
    protected string  $validationErrorMessage;

    protected readonly QuestionFormatter $formatter;


    public static function create(string $question): static {
        return new static($question);
    }

    protected function __construct(protected readonly string $question) {
        $this->formatter = QuestionFormatter::createForStdOut();
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
     * A shortcut to ask any question without answer validation and any other additional setup.
     */
    public static function askAway(string $question): string {
        return static::create($question)->ask();
    }

    /**
     * Ask a question with possible yes/no answers.
     */
    public static function confirm(string $question): bool {
        $answer = static::create($question)
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
        string $errorMessage = 'Invalid answer.',
    ): static {
        if ($this->answerValidatorPattern) {
            throw new LogicException('Can not set possible answers: answer validator pattern is set');
        }

        $this->possibleAnswers        = $possibleAnswers;
        $this->isAnswerCaseSensitive  = $isCaseSensitive;
        $this->validationErrorMessage = $errorMessage;

        return $this;
    }

    public function answerValidatorPattern(
        string $answerValidatorPattern,
        string $errorMessage = 'Invalid answer.',
    ): static {
        if ($this->possibleAnswers) {
            throw new LogicException('Can not set answer validator pattern: a list of possible answers is set');
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
        $input = $this->getInput();

        return '' === $input ? $this->defaultAnswer : $input;
    }

    /**
     * Read a user input. Returns a trimmed line.
     */
    protected function getInput(): string {
        return trim(fgets(STDIN));
    }

    /**
     * Validate (and possibly modify) an answer by a pattern or a list of possible values (if anything of that is set).
     *
     * The answer case is modified (if it is one of {@see static::possibleAnswers()} with case sensitive mode
     * disabled - the originally expected case is enforced. For instance, if one of expected answers is 'YES',
     * but you provide `yes`, then {@see ask()} will return `YES` (an answer with original case).
     */
    protected function validateAnswer(string &$answer): void {
        if ($this->answerValidatorPattern && !preg_match($this->answerValidatorPattern, $answer)) {
            throw new RuntimeException(QuestionFormatter::createForStdErr()->error($this->validationErrorMessage));
        }

        if ($this->possibleAnswers) {
            if ($this->isAnswerCaseSensitive) {
                $answerToCompare = $answer;
                $possibleAnswers = array_flip($this->possibleAnswers);
            } else {
                $answerToCompare = mb_strtolower($answer);
                $possibleAnswers = array_combine(
                    array_map(
                        function ($possibleAnswer) {
                            return mb_strtolower($possibleAnswer);
                        },
                        $this->possibleAnswers
                    ),
                    $this->possibleAnswers,
                );
            }

            if (array_key_exists($answerToCompare, $possibleAnswers)) {
                if (!$this->isAnswerCaseSensitive) {
                    $answer = $possibleAnswers[$answerToCompare];
                }

                return;
            }

            $formatter = QuestionFormatter::createForStdErr();
            $message   = $formatter->error(ltrim("{$this->validationErrorMessage} Possible answers: "))
                . $formatter->value(implode(', ', $this->possibleAnswers));

            throw new RuntimeException($message);
        }
    }
}
