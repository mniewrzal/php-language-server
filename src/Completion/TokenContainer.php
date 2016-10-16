<?php
declare(strict_types = 1);

namespace LanguageServer\Completion;

use LanguageServer\Protocol\ {
    Position,
    Range
};
use LanguageServer\PhpDocument;

class TokenContainer
{
    const T_SINGLE_CHAR = 10000;

    /**
     *
     * @var \LanguageServer\Completion\Token[]
     */
    private $tokens;

    /**
     *
     * @var int
     */
    private $index;

    public function __construct(PhpDocument $phpDocument)
    {
        $line = 0;
        $column = 0;
        $tokens = token_get_all($phpDocument->getContent());
        foreach ($tokens as $token) {
            if (\is_string($token)) {
                $range = new Range(new Position($line, $column), new Position($line, $column + 1));
                $this->tokens[] = new Token(self::T_SINGLE_CHAR, $token, $range);
                $column++;
            } else {
                $line = $token[2] - 1;
                $start = new Position($line, $column);
                if ($token[0] == T_WHITESPACE) {
                    for ($i = 0; $i < strlen($token[1]); $i++) {
                        $ch = $token[1][$i];
                        if ($ch === "\n") {
                            $line++;
                            $column = 0;
                        } else {
                            $column++;
                        }
                    }
                } else {
                    $column += strlen($token[1]);
                }
                $end = new Position($line, $column);
                $this->tokens[] = new Token($token[0], $token[1], new Range($start, $end));
            }
        }
    }

    /**
     *
     * @param Position $position
     * @return null|\LanguageServer\Completion\Token
     */
    public function getToken(Position $position)
    {
        $this->index = 0;
        foreach ($this->tokens as $token) {
            if ($token->getRange()->includes($position)) {
                return $token;
            }
            $this->index++;
        }
        return null;
    }

    public function previousToken()
    {
        return $this->tokens[--$this->index];
    }

    public function nextToken()
    {
        return $this->tokens[++$this->index];
    }

    /**
     *
     * @return \LanguageServer\Completion\Token[]
     */
    public function getTokens()
    {
        return $this->tokens;
    }
}
