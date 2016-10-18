<?php
declare(strict_types = 1);

namespace LanguageServer\Completion;

use LanguageServer\Protocol\ {
    CompletionItem,
    Range,
    Position,
    TextEdit,
    CompletionItemKind,
    CompletionList
};
use LanguageServer\Completion\Strategies\ {
    KeywordsStrategy,
    VariablesStrategy,
    ClassMembersStrategy,
    GlobalElementsStrategy
};
use LanguageServer\PhpDocument;
use PhpParser\Node;

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

    public function reportByNode(Node $node, Range $editRange, string $fqn = null)
    {
        if (!$node) {
            return;
        }

        if ($node instanceof \PhpParser\Node\Stmt\Class_) {
            $this->report($node->name, CompletionItemKind::_CLASS, $node->name, $editRange, $fqn);
        } else if ($node instanceof \PhpParser\Node\Stmt\Interface_) {
            $this->report($node->name, CompletionItemKind::INTERFACE, $node->name, $editRange, $fqn);
        } else if ($node instanceof \PhpParser\Node\Stmt\Trait_) {
            $this->report($node->name, CompletionItemKind::_CLASS, $node->name, $editRange, $fqn);
        } else if ($node instanceof \PhpParser\Node\Stmt\Function_) {
            $this->report($node->name, CompletionItemKind::FUNCTION, $node->name, $editRange, $fqn);
        } else if ($node instanceof \PhpParser\Node\Stmt\ClassMethod) {
            $this->report($node->name, CompletionItemKind::METHOD, $node->name, $editRange, $fqn);
        } else if ($node instanceof \PhpParser\Node\Stmt\Property) {
            foreach ($node->props as $prop) {
                $this->reportByNode($prop, $editRange, $fqn);
            }
        } else if ($node instanceof \PhpParser\Node\Stmt\PropertyProperty) {
            $this->report($node->name, CompletionItemKind::FIELD, $node->name, $editRange, $fqn);
        } else if ($node instanceof \PhpParser\Node\Stmt\ClassConst) {
            foreach ($node->consts as $const) {
                $this->reportByNode($const, $editRange, $fqn);
            }
        } else if ($node instanceof \PhpParser\Node\Const_) {
            $this->report($node->name, CompletionItemKind::FIELD, $node->name, $editRange, $fqn);
        }
    }

    public function report(string $label, int $kind, string $insertText, Range $editRange, string $fqn = null)
    {
        $item = new CompletionItem();
        $item->label = $label;
        $item->kind = $kind;
        $item->textEdit = new TextEdit($editRange, $insertText);
        $item->data = $fqn;

        $this->completionItems[] = $item;
    }

    /**
     *
     * @return CompletionList
     */
    public function getCompletionList(): CompletionList
    {
        $completionList = new CompletionList();
        $completionList->isIncomplete = false;
        $completionList->items = $this->completionItems;
        return $completionList;
    }
}
