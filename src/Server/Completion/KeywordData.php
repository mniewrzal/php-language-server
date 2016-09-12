<?php
namespace LanguageServer\Server\Completion;

class KeywordData
{

    private $label;

    private $insertText;

    public function __construct(string $label, string $insertText = null)
    {
        $this->label = $label;
        if ($insertText == null) {
            $this->insertText = $label;
        } else {
            $this->insertText = $insertText;
        }
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function getInsertText()
    {
        return $this->insertText;
    }
}