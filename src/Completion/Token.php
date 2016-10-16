<?php
declare(strict_types = 1);

namespace LanguageServer\Completion;

use LanguageServer\Protocol\Range;

class Token
{
    /**
     *
     * @var int
     */
    private $id;

    /**
     *
     * @var string
     */
    private $value;

    /**
     *
     * @var \LanguageServer\Protocol\Range
     */
    private $range;

    public function __construct(int $id, string $value, Range $range)
    {
        $this->id = $id;
        $this->value = $value;
        $this->range = $range;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getRange(): Range
    {
        return $this->range;
    }

}
