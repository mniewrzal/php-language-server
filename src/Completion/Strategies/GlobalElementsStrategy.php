<?php
declare(strict_types = 1);

namespace LanguageServer\Completion\Strategies;

use LanguageServer\Completion\ {
    CompletionContext,
    CompletionReporter,
    ICompletionStrategy
};
use LanguageServer\Protocol\CompletionItemKind;

class GlobalElementsStrategy implements ICompletionStrategy
{

    /**
     * {@inheritdoc}
     */
    public function apply(CompletionContext $context, CompletionReporter $reporter)
    {
        $token = $context->getTokenContainer()->getToken($context->getPosition());
        if ($token->getId() != T_STRING) {
            return;
        }
        $range = $context->getReplacementRange($context);
        $query = $token->getValue();
        $project = $context->getPhpDocument()->project;
        foreach ($project->getDefinitionUris() as $fqn => $uri) {
            $index = strrpos($fqn, '\\');
            $name = $index ? substr($fqn, $index + 1) : $fqn;
            if (strpos($name, '::') === false) {
                $ns = '\\' . ($index ? substr($fqn, 0, $index) : '');
                if (stripos($name, $query) !== false) {
                    if (strpos($name, '()') !== false) {
                        $reporter->report($name, CompletionItemKind::FUNCTION, $name, $range, $ns);
                    } else {
                        $reporter->report($name, CompletionItemKind::_CLASS, $name, $range, '');
                        $reporter->report($name, CompletionItemKind::_CLASS, '\\' . $fqn, $range, $ns);
                    }
                }
            }
        }
    }
}
