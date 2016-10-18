<?php
declare(strict_types = 1);

namespace LanguageServer\Completion\Strategies;

use LanguageServer\Completion\ {
    CompletionContext,
    CompletionReporter,
    ICompletionStrategy
};

class GlobalElementsStrategy implements ICompletionStrategy
{

    /**
     * {@inheritdoc}
     */
    public function apply(CompletionContext $context, CompletionReporter $reporter)
    {
        $token = $context->getTokenContainer()->getToken($context->getPosition());
        if ($token == null || $token->getId() != T_STRING) {
            return;
        }

        $range = $context->getReplacementRange($context);
        $project = $context->getPhpDocument()->project;
        foreach ($project->getDefinitionUris() as $fqn => $uri) {
            if (strpos($fqn, '::') === false) {
                $phpDocument = $project->getDefinitionDocument($fqn);
                if ($phpDocument) {
                    $node = $phpDocument->getDefinitionByFqn($fqn);
                    $reporter->reportByNode($node, $range, $fqn);
                }
            }
        }
    }
}
