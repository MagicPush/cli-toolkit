<?php declare(strict_types=1);

namespace MagicPush\CliToolkit\Parametizer\Config\Completion;

final class Token {
    /**
     * @param string $arg  The arg in raw form, as it was written in the command.
     * @param string $word The word that is parsed out of the arg.
     */
    public function __construct(public readonly string $arg, public readonly string $word) { }

    /**
     * Retrieves a part of the arg according to given word breaks.
     *
     * Completion engine (at least in bash) uses different word breaks than the shell.
     * In shell 'a:b' is (usually) one word, while compwords are 'a' and 'b' here.
     * To make correct prefix for completion results (e.g. '//domain' instead of 'http://domain'),
     * we need to split the arg the way completion wants it.
     *
     * Google 'bash IFS' and 'COMP_WORDBREAKS' for the rest of the lore.
     */
    public function getArgTail(string $wordBreaks = PHP_EOL . " \t"): string {
        $quotedWordBreaks = preg_quote($wordBreaks);
        if (preg_match('{[' . $quotedWordBreaks . ']([^' . $quotedWordBreaks . ']*)$}', $this->arg, $matches)) {
            return $matches[1];
        }

        return $this->arg;
    }
}
