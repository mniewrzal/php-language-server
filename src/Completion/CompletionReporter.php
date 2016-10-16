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
    VariablesStrategy
};
use LanguageServer\PhpDocument;

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
            new VariablesStrategy()
        ];
    }

    public function complete(Position $position)
    {
        $context = new CompletionContext($position, $this->phpDocument);
        foreach ($this->strategies as $strategy) {
            $strategy->apply($context, $this);
        }
    }

    public function report(string $label, int $kind, string $insertText, Range $editRange)
    {
        $item = new CompletionItem();
        $item->label = $label;
        $item->kind = $kind;
        $item->textEdit = new TextEdit($editRange, $insertText);
        $item->detail = 'PHP Language Server';

        $this->completionItems[] = $item;
    }

    public function getCompletionItems(): array
    {
        return $this->completionItems;
    }
}
