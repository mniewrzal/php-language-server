<?php
declare(strict_types = 1);

namespace LanguageServer\Completion;

use LanguageServer\Protocol\ {
    CompletionItem,
    Range,
    Position,
    TextEdit
};
use LanguageServer\Completion\Strategies\ {
    KeywordsStrategy,
    VariablesStrategy,
    ClassMembersStrategy
};
use LanguageServer\PhpDocument;
use PhpParser\Node;
use LanguageServer\Protocol\CompletionItemKind;
use LanguageServer\Completion\Strategies\GlobalElementsStrategy;

class CompletionReporter
{

    /**
     * @var \LanguageServer\PhpDocument
     */
    private $phpDocument;

    /**
     * @var \LanguageServer\Protocol\CompletionItem
     */
    private $completionItems = [];

    /**
     * @var \LanguageServer\Completion\ICompletionStrategy
     */
    private $strategies;

    public function __construct(PhpDocument $phpDocument)
    {
        $this->phpDocument = $phpDocument;
        $this->strategies = [
            new KeywordsStrategy(),
            new VariablesStrategy(),
            new ClassMembersStrategy(),
            new GlobalElementsStrategy()
        ];
    }

    public function complete(Position $position)
    {
        $context = new CompletionContext($position, $this->phpDocument);
        foreach ($this->strategies as $strategy) {
            $strategy->apply($context, $this);
        }
    }

    public function reportByNode(Node $node, Range $editRange, $doc = '')
    {
        if ($node instanceof \PhpParser\Node\Stmt\ClassMethod) {
            $this->report($node->name, CompletionItemKind::METHOD, $node->name, $editRange, $this->generateMethodSignature($node), $this->getNodePhpDoc($node));
        } else if ($node instanceof \PhpParser\Node\Stmt\Property) {
            $doc = $this->getNodePhpDoc($node);
            foreach ($node->props as $prop) {
                $this->reportByNode($prop, $editRange, $doc);
            }
        } else if ($node instanceof \PhpParser\Node\Stmt\PropertyProperty) {
            $this->report($node->name, CompletionItemKind::FIELD, $node->name, $editRange, $doc);
        } else if ($node instanceof \PhpParser\Node\Stmt\ClassConst) {
            $doc = $this->getNodePhpDoc($node);
            foreach ($node->consts as $const) {
                $this->reportByNode($const, $editRange, $doc);
            }
        } else if ($node instanceof \PhpParser\Node\Const_) {
            $this->report($node->name, CompletionItemKind::FIELD, $node->name, $editRange, $doc);
        }
    }

    private function generateMethodSignature(\PhpParser\Node\Stmt\ClassMethod $node)
    {
        $params = [];
        foreach ($node->params as $param) {
            $label = $param->type ? ((string) $param->type) . ' ' : '';
            $label .= '$' . $param->name;
            $params[] = $label;
        }
        $signature = '(' . implode(', ', $params) . ')';
        if ($node->returnType) {
            $signature .= ': ' . $node->returnType;
        }
        return $signature;
    }

    private function getNodePhpDoc(Node $node)
    {
        if ($node->getDocComment()) {
            return $node->getDocComment()->getReformattedText();
        }
        return '';
    }

    public function report(string $label, int $kind, string $insertText, Range $editRange, string $detail = 'PHP LS', string $doc = '')
    {
        $item = new CompletionItem();
        $item->label = $label;
        $item->kind = $kind;
        $item->textEdit = new TextEdit($editRange, $insertText);
        $item->detail = $detail;
        $item->documentation = $doc;

        $this->completionItems[] = $item;
    }

    public function getCompletionItems(): array
    {
        return $this->completionItems;
    }
}
