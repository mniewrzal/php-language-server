<?php
namespace LanguageServer\Server\Completion;

class PHPKeywords
{

    /**
     *
     * @var KeywordData[]
     */
    private $keywords;

    public function __construct()
    {
        $this->keywords = [
            new KeywordData("abstract"),
            new KeywordData("and"),
            new KeywordData("array"),
            new KeywordData("as"),
            new KeywordData("break"),
            new KeywordData("callable"),
            new KeywordData("case"),
            new KeywordData("catch"),
            new KeywordData("class"),
            new KeywordData("clone"),
            new KeywordData("const"),
            new KeywordData("continue"),
            new KeywordData("declare"),
            new KeywordData("default"),
            new KeywordData("die"),
            new KeywordData("do"),
            new KeywordData("echo"),
            new KeywordData("else"),
            new KeywordData("elseif"),
            new KeywordData("empty"),
            new KeywordData("enddeclare"),
            new KeywordData("endfor"),
            new KeywordData("endforeach"),
            new KeywordData("endif"),
            new KeywordData("endswitch"),
            new KeywordData("endwhile"),
            new KeywordData("eval"),
            new KeywordData("exit"),
            new KeywordData("extends"),
            new KeywordData("false"),
            new KeywordData("final"),
            new KeywordData("finally"),
            new KeywordData("for", "for ()"),
            new KeywordData("foreach", "foreach ()"),
            new KeywordData("function"),
            new KeywordData("global"),
            new KeywordData("goto"),
            new KeywordData("if", "if ()"),
            new KeywordData("implements"),
            new KeywordData("include"),
            new KeywordData("include_once"),
            new KeywordData("instanceof"),
            new KeywordData("insteadof"),
            new KeywordData("interface"),
            new KeywordData("isset"),
            new KeywordData("list"),
            new KeywordData("namespace"),
            new KeywordData("new"),
            new KeywordData("null"),
            new KeywordData("or"),
            new KeywordData("parent"),
            new KeywordData("print"),
            new KeywordData("private"),
            new KeywordData("protected"),
            new KeywordData("public"),
            new KeywordData("require"),
            new KeywordData("require_once"),
            new KeywordData("return"),
            new KeywordData("self"),
            new KeywordData("static"),
            new KeywordData("switch", "switch ()"),
            new KeywordData("throw"),
            new KeywordData("trait"),
            new KeywordData("true"),
            new KeywordData("try"),
            new KeywordData("unset"),
            new KeywordData("use"),
            new KeywordData("var"),
            new KeywordData("while"),
            new KeywordData("xor"),
            new KeywordData("yield")
        ];
    }

    /**
     * List of supported PHP keywords
     *
     * @return KeywordData[]
     */
    public function getKeywords()
    {
        return $this->keywords;
    }
}