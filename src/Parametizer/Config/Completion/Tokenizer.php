<?php declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Config\Completion;

/**
 * Reads args (words) from raw command line string (bash shell syntax).
 */
final class Tokenizer {
    private int $offset = 0;


    public function __construct(private string $string) { }

    /**
     * Fetches next arg from the string.
     */
    public function read(int $stopAtOffset = -1): ?Token {
        if (!mb_strlen($this->string) || ($stopAtOffset > -1 && $this->offset >= $stopAtOffset)) {
            return null;
        }

        $arg                 = '';
        $word                = '';
        $openQuote           = '';
        $isHeadingWhitespace = true;
        while (mb_strlen($this->string)) {
            if ($this->offset == $stopAtOffset) {
                break;
            }

            $firstChar = mb_substr($this->string, 0, 1);

            $stepLength = 1;
            $skipChar   = false;

            switch ($firstChar) {
                case ' ':
                    if ($isHeadingWhitespace) {
                        $skipChar = true; // ignore it, continue reading

                        break;
                    }

                    if ($openQuote) {
                        $word .= $firstChar;

                        break;
                    }

                    break 2; // The arg processing is finished, stop reading (the space char remains in the string).

                case '"':
                case "'":
                    $isHeadingWhitespace = false;

                    if ($openQuote == $firstChar) {
                        $openQuote = ''; // Quote closed.
                    } elseif ($openQuote) {
                        $word .= $firstChar; // Other quote inside: "it's".
                    } else {
                        $openQuote = $firstChar; // Quote start.
                    }

                    break;

                case '\\':
                    $isHeadingWhitespace = false;

                    $nextChar = mb_substr($this->string, 1, 1);

                    // If we should stop at the next char, the slash cannot escape
                    // anything and should be treated as a regular char.
                    if ($this->offset + 1 == $stopAtOffset) {
                        $word .= $firstChar;

                        break;
                    }

                    // Backslash does not work (treated as a regular char) within single quotes.

                    // ... But outside of quotes the slash is stripped from any char, except for the newline.
                    if (!$openQuote && $nextChar != "\n" && $nextChar != "\r") {
                        $word .= $nextChar;
                        $stepLength++;

                        break;
                    }

                    // Backslash escapes itself and newline in double quotes and outside.
                    if (!$openQuote || '"' == $openQuote) {
                        if ('\\' == $nextChar) {
                            // Word receives one slash.
                            $word .= $nextChar;
                            $stepLength++;

                            break;
                        }

                        if ("\n" === $nextChar || "\r" === $nextChar) {
                            // Word receives nothing, the newline gets eaten.
                            $stepLength++;

                            break;
                        }
                    }

                    // In double quotes some extra chars can be escaped.
                    if ('"' == $openQuote && '' != $nextChar && mb_strstr('"`$', $nextChar)) {
                        $word .= $nextChar;
                        $stepLength++;

                        break;
                    }

                    $word .= $firstChar;
                    break;

                default:
                    $isHeadingWhitespace = false;

                    $word .= $firstChar;
                    break;
            }

            if (!$skipChar) {
                $arg .= mb_substr($this->string, 0, $stepLength);
            }

            $this->string = mb_substr($this->string, $stepLength);
            $this->offset += $stepLength;
        }

        return new Token($arg, $word);
    }
}
