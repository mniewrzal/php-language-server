<?php
declare(strict_types = 1);

namespace LanguageServer\Server;

use LanguageServer\Protocol\ {
    CompletionItem,
    TextEdit
};
use PhpParser\Node;
use LanguageServer\Project;

class CompletionItemResolver
{
    /**
     * @var \LanguageServer\Project
     */
    private $project;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    /**
     * The request is sent from the client to the server to resolve additional information for a given completion item.
     *
     * @param string $label
     * @param int $kind
     * @param TextEdit $textEdit
     * @param string $data
     *
     * @return \LanguageServer\Protocol\CompletionItem
     */
    public function resolve($label, $kind, $textEdit, $data)
    {
        if (!$data) {
            return;
        }

        $fqn = $data;

        $item = new CompletionItem();
        $item->label = $label;
        $item->kind = $kind;
        $item->textEdit = $textEdit;
        $phpDocument = $this->project->getDefinitionDocument($fqn);
        if (!$phpDocument) {
            return $item;
        }
        $node = $phpDocument->getDefinitionByFqn($fqn);
        if (!$node) {
            return $item;
        }
        $item->detail = $this->generateItemDetails($node);
        $item->documentation = $this->getNodePhpDoc($node);
        return $item;
    }

    private function generateItemDetails(Node $node)
    {
        if ($node instanceof \PhpParser\Node\FunctionLike) {
            return $this->generateFunctionSignature($node);
        }
        if (isset($node->namespacedName)) {
            return '\\' . ((string) $node->namespacedName);
        }
        return '';
    }

    private function generateFunctionSignature(\PhpParser\Node\FunctionLike $node)
    {
        $params = [];
        foreach ($node->getParams() as $param) {
            $label = $param->type ? ((string) $param->type) . ' ' : '';
            $label .= '$' . $param->name;
            $params[] = $label;
        }
        $signature = '(' . implode(', ', $params) . ')';
        if ($node->getReturnType()) {
            $signature .= ': ' . $node->getReturnType();
        }
        return $signature;
    }

    private function getNodePhpDoc(Node $node)
    {
        if ($node->getDocComment()) {
            return $node->getDocComment()->getReformattedText();
        }

        $parent = $node->getAttribute('parentNode');
        if ($parent instanceof \PhpParser\Node\Stmt\ClassConst || $parent instanceof \PhpParser\Node\Stmt\Property) {
            if ($parent->getDocComment()) {
                return $parent->getDocComment()->getReformattedText();
            }
        }
        return '';
    }
}
