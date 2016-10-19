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
        if ($token == null) {
            return;
        }

        $range = $context->getReplacementRange($context);
        $project = $context->getPhpDocument()->project;
        foreach ($project->getDefinitionUris() as $fqn => $uri) {
            $index = strrpos($fqn, '\\');
            $name = $index ? substr($fqn, $index + 1) : $fqn;
            if (strpos($name, ':') === false) {
                $index = strpos($name, '()');
                if ($index !== false) {
                    $name = substr($name, 0, $index);
                    $reporter->report($name, CompletionItemKind::FUNCTION, $name, $range, $fqn);
                } else {
                    $reporter->report($name, CompletionItemKind::_CLASS, $name, $range, $fqn);
                }
            }
        }
    }
}
