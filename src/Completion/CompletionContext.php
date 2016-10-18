<?php
declare(strict_types = 1);

namespace LanguageServer\Completion;

use LanguageServer\PhpDocument;
use LanguageServer\Protocol\ {
    Range,
    Position
};
use LanguageServer\NodeVisitor\NodeAtPositionFinder;
use PhpParser\NodeTraverser;

class CompletionContext
{
    /**
     *
     * @var \LanguageServer\Protocol\Position
     */
    private $position;

    /**
     *
     * @var \LanguageServer\PhpDocument
     */
    private $phpDocument;

    /**
     *
     * @var \LanguageServer\Completion\TokenContainer
     */
    private $tokenContainer;

    private $node;

    public function __construct(Position $position, PhpDocument $phpDocument)
    {
        $this->position = $position;
        $this->phpDocument = $phpDocument;
        $this->tokenContainer = new TokenContainer($phpDocument);

        $traverser = new NodeTraverser();
        $finder = new NodeAtPositionFinder($position);
        $traverser->addVisitor($finder);
        $traverser->traverse($phpDocument->getStmts());
        $this->node = $finder->node;
    }

    public function getReplacementRange(): Range
    {
        $token = $this->getTokenContainer()->getToken($this->position);
        if ($token && $token->getId() == T_STRING) {
            return $token->getRange();
        }
        return new Range($this->position, $this->position);
    }


    public function getPosition()
    {
        return $this->position;
    }

    public function getTokenContainer()
    {
        return $this->tokenContainer;
    }

    public function getPhpDocument()
    {
        return $this->phpDocument;
    }

    public function getNode()
    {
        return $this->node;
    }
}
