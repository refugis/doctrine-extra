<?php

declare(strict_types=1);

namespace Refugis\DoctrineExtra\ORM\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * Example Usage:
 * $query = $this->getEntityManager()->createQuery('SELECT A FROM Entity A WHERE REGEXP(A.stringField, :reg_exp) = 1');
 * $query->setParameter('reg_exp', '^[ABC]');
 * $results = $query->getArrayResult();.
 */
class RegExp extends FunctionNode
{
    public Node $value;
    public Node $regExp;

    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->value = $parser->StringPrimary();

        $parser->match(Lexer::T_COMMA);

        /* @phpstan-ignore-next-line */
        $this->regExp = $parser->StringExpression();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return '(' . $this->value->dispatch($sqlWalker) . ' REGEXP ' . $this->regExp->dispatch($sqlWalker) . ')';
    }
}
