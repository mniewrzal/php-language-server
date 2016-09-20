<?php

namespace LanguageServer\Server;

use PhpParser\{Error, Comment, Node, ParserFactory, NodeTraverser, Lexer};
use PhpParser\PrettyPrinter\Standard as PrettyPrinter;
use PhpParser\NodeVisitor\NameResolver;
use LanguageServer\{LanguageClient, ColumnCalculator, SymbolFinder};
use LanguageServer\Protocol\{
    TextDocumentItem,
    TextDocumentIdentifier,
    VersionedTextDocumentIdentifier,
    Diagnostic,
    DiagnosticSeverity,
    Range,
    Position,
    FormattingOptions,
    TextEdit,
    CompletionItem,
    CompletionItemKind,
    CompletionList
};
use LanguageServer\Server\Completion\PHPKeywords;

/**
 * Provides method handlers for all textDocument/* methods
 */
class TextDocument
{
    /**
     * @var \PhpParser\Parser
     */
    private $parser;
   
    /**
     * A map from file URIs to ASTs
     *
     * @var Node[]
     */
    private $asts;

    /**
     * The lanugage client object to call methods on the client
     *
     * @var \LanguageServer\LanguageClient
     */
    private $client;

    public function __construct(LanguageClient $client)
    {
        $this->client = $client;
        $lexer = new Lexer(['usedAttributes' => ['comments', 'startLine', 'endLine', 'startFilePos', 'endFilePos']]);
        $this->parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7, $lexer, ['throwOnError' => false]);
    }

    /**
     * The document symbol request is sent from the client to the server to list all symbols found in a given text
     * document.
     *
     * @param \LanguageServer\Protocol\TextDocumentIdentifier $textDocument
     * @return SymbolInformation[]
     */
    public function documentSymbol(TextDocumentIdentifier $textDocument): array
    {
        $stmts = $this->asts[$textDocument->uri];
        if (!$stmts) {
            return [];
        }
        $finder = new SymbolFinder($textDocument->uri);
        $traverser = new NodeTraverser;
        $traverser->addVisitor($finder);
        $traverser->traverse($stmts);
        return $finder->symbols;
    }

    /**
     * The document open notification is sent from the client to the server to signal newly opened text documents. The
     * document's truth is now managed by the client and the server must not try to read the document's truth using the
     * document's uri.
     *
     * @param \LanguageServer\Protocol\TextDocumentItem $textDocument The document that was opened.
     * @return void
     */
    public function didOpen(TextDocumentItem $textDocument)
    {
        $this->updateAst($textDocument->uri, $textDocument->text);
    }

    /**
     * The document change notification is sent from the client to the server to signal changes to a text document.
     *
     * @param \LanguageServer\Protocol\VersionedTextDocumentIdentifier $textDocument
     * @param \LanguageServer\Protocol\TextDocumentContentChangeEvent[] $contentChanges
     * @return void
     */
    public function didChange(VersionedTextDocumentIdentifier $textDocument, array $contentChanges)
    {
        $this->updateAst($textDocument->uri, $contentChanges[0]->text);
    }
    
    /**
     * Re-parses a source file, updates the AST and reports parsing errors that may occured as diagnostics
     *
     * @param string $uri     The URI of the source file
     * @param string $content The new content of the source file
     * @return void
     */
    private function updateAst(string $uri, string $content)
    {
        $ast = $this->parser->parse($content);
        $diagnostics = [];
        foreach ($this->parser->getErrors() as $error) {
            $diagnostic = new Diagnostic();
            $diagnostic->range = new Range(
                new Position($error->getStartLine() - 1, $error->hasColumnInfo() ? $error->getStartColumn($content) - 1 : 0),
                new Position($error->getEndLine() - 1, $error->hasColumnInfo() ? $error->getEndColumn($content) : 0)
            );
            $diagnostic->severity = DiagnosticSeverity::ERROR;
            $diagnostic->source = 'php';
            // Do not include "on line ..." in the error message
            $diagnostic->message = $error->getRawMessage();
            $diagnostics[] = $diagnostic;
        }
        $this->client->textDocument->publishDiagnostics($uri, $diagnostics);
        // $stmts can be null in case of a fatal parsing error
        if ($ast) {
            $traverser = new NodeTraverser;
            $traverser->addVisitor(new NameResolver);
            $traverser->addVisitor(new ColumnCalculator($content));
            $traverser->traverse($ast);
            $this->asts[$uri] = $ast;
        }
    }

    /**
     * The document close notification is sent from the client to the server when the document got closed in the client.
     * The document's truth now exists where the document's uri points to (e.g. if the document's
     * uri is a file uri the truth now exists on disk).
     *
     * @param \LanguageServer\Protocol\TextDocumentIdentifier $textDocument
     * @return void
     */
    public function didClose(TextDocumentIdentifier $textDocument)
    {
        unset($this->asts[$textDocument->uri]);
    }
    
    /**
     * The document formatting request is sent from the server to the client to format a whole document.
     *
     * @param TextDocumentIdentifier $textDocument The document to format
     * @param FormattingOptions $options The format options
     * @return TextEdit[]
     */
    public function formatting(TextDocumentIdentifier $textDocument, FormattingOptions $options)
    {
        $nodes = $this->asts[$textDocument->uri];
        if (empty($nodes)) {
            return [];
        }
        $prettyPrinter = new PrettyPrinter();
        $edit = new TextEdit();
        $edit->range = new Range(new Position(0, 0), new Position(PHP_INT_MAX, PHP_INT_MAX));
        $edit->newText = $prettyPrinter->prettyPrintFile($nodes);
        return [$edit];
    }
    
    public function completion(TextDocumentIdentifier $textDocument, Position $position)
    {
        $node = $this->findAstNode($textDocument, $position);
        $editRange = $this->getCompletionEditRange($node, $position);
        
        $list = new CompletionList();
        $list->isIncomplete = false;
        $list->items = [];
        
        $keywords = new PHPKeywords();
        foreach ($keywords->getKeywords() as $keyword){
            $item = new CompletionItem();
            $item->label = $keyword->getLabel();
            $item->kind = CompletionItemKind::KEYWORD;
            $item->textEdit = new TextEdit($editRange, $keyword->getInsertText());
            $item->detail = "PHP Language Server";
            $list->items[] = $item;
        }
        return $list;
    }
    
    private function findAstNode(TextDocumentIdentifier $textDocument, Position $position) {
        $ast = $this->asts[$textDocument->uri];
        if ($ast != null) {
            foreach ($ast as $node) {
                if ($node->getAttribute('startLine') - 1 <= $position->line &&
                    $node->getAttribute('endLine') - 1 >= $position->line &&
                    $node->getAttribute('startColumn') - 1 <= $position->character &&
                    $node->getAttribute('endColumn') >= $position->character) {
                    return $node;        
                }
            }
        }
        return null;
    }
    
    private function getCompletionEditRange(Node $node = null, Position $position = null): Range {
        if ($node == null) {
            $start = $position;
            $end = $position;
        } else {
            $start = new Position($node->getAttribute('startLine') - 1, $node->getAttribute('startColumn') - 1);
            $end = new Position($node->getAttribute('endLine') - 1, $node->getAttribute('endColumn'));
        }
        return new Range($start, $end);
    }
    
}
