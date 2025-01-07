<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\ORM\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

use function class_exists;

class Cast extends FunctionNode
{
    private Node $field;

    private Node $type;

    public function parse(Parser $parser): void
    {
        if (class_exists(TokenType::class)) {
            $parser->match(TokenType::T_IDENTIFIER);
            $parser->match(TokenType::T_OPEN_PARENTHESIS);

            $this->field = $parser->StringPrimary();

            $parser->match(TokenType::T_AS);

            $this->type = $parser->StringPrimary();

            $parser->match(TokenType::T_CLOSE_PARENTHESIS);
        } else {
            $parser->match(Lexer::T_IDENTIFIER); /** @phpstan-ignore-line */
            $parser->match(Lexer::T_OPEN_PARENTHESIS); /** @phpstan-ignore-line */

            $this->field = $parser->StringPrimary();

            $parser->match(Lexer::T_AS); /** @phpstan-ignore-line */

            $this->type = $parser->StringPrimary();

            $parser->match(Lexer::T_CLOSE_PARENTHESIS); /** @phpstan-ignore-line */
        }
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return 'CAST(' . $this->field->dispatch($sqlWalker) . ' AS ' . $this->type->dispatch($sqlWalker) . ')';
    }
}
