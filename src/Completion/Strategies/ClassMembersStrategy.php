<?php
declare(strict_types = 1);

namespace LanguageServer\Completion\Strategies;

use LanguageServer\Completion\ {
    CompletionContext,
    CompletionReporter,
    ICompletionStrategy,
    Token
};
use LanguageServer\Protocol\Range;

class ClassMembersStrategy implements ICompletionStrategy
{

    /**
     * {@inheritdoc}
     */
    public function apply(CompletionContext $context, CompletionReporter $reporter)
    {
        $range = $this->getReplacementRange($context);
        if (!$range) {
            return;
        }

        $nodes = $context->getPhpDocument()->getDefinitions();
        foreach ($nodes as $node) {
            if ($node instanceof \PhpParser\Node\Stmt\ClassLike) {
                $nodeRange = Range::fromNode($node);
                if ($nodeRange->includes($context->getPosition())) {
                    foreach ($node->stmts as $child) {
                        $reporter->reportByNode($child, $range);
                    }
                    return;
                }
            }
        }
    }

    protected function getReplacementRange(CompletionContext $context)
    {
        $container = $context->getTokenContainer();
        $token = $container->getToken($context->getPosition());

        if (!$token) {
            return null;
        }

        if ($token->getId() == T_STRING) {
            $second = $container->previousToken();
            $first = $container->previousToken();
            if ($this->isSelf($first, $second) || $this->isThis($first, $second)) {
                return $context->getReplacementRange();
            }
        } else {
            $first = $container->previousToken();
            if ($this->isSelf($first, $token) || $this->isThis($first, $token)) {
                if ($token->getRange()->end->compare($context->getPosition()) == 0) {
                    return new Range($context->getPosition(), $context->getPosition());
                }
            }
        }
        return null;
    }

    protected function isSelf(Token $first, Token $second)
    {
        return $first->getValue() === 'self' && $second->getId() == T_PAAMAYIM_NEKUDOTAYIM;
    }

    protected function isThis(Token $first, Token $second)
    {
        return $first->getValue() == '$this' && $second->getId() == T_OBJECT_OPERATOR;
    }
}
