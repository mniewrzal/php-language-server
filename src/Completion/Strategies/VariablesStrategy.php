<?php
declare(strict_types = 1);

namespace LanguageServer\Completion\Strategies;

use LanguageServer\Protocol\ {
    CompletionItemKind,
    Range
};
use LanguageServer\Completion\ {
    ICompletionStrategy,
    CompletionContext,
    CompletionReporter
};

class VariablesStrategy implements ICompletionStrategy
{

    /**
     * {@inheritdoc}
     */
    public function apply(CompletionContext $context, CompletionReporter $reporter)
    {
        $range = $context->getReplacementRange();
//         $node = $context->getNode();
//         if ($node) {
//             $parent = $node->getAttribute('parentNode');
//             if ($parent instanceof \PhpParser\Node\Stmt\ClassLike) {
//                 return;
//             }

//             if ($parent instanceof \PhpParser\Node\Stmt\ClassMethod) {
//                 $reporter->report('$this', CompletionItemKind::VARIABLE, '$this', $range);
//             }
//         }

        $container = $context->getTokenContainer();
        $tokens = $container->getTokens();
        $variables = [];
        foreach ($tokens as $tmp) {
            if ($tmp->getId() == T_VARIABLE && !$tmp->getRange()->includes($context->getPosition())) {
                $variables[$tmp->getValue()] = $tmp->getValue();
            }
        }

        foreach ($variables as $label => $value) {
            $reporter->report($label, CompletionItemKind::VARIABLE, $value, $range);
        }
    }
}
