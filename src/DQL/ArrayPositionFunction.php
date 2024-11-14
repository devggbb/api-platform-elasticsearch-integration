<?php

declare(strict_types=1);

namespace Ggbb\ApiPlatformElasticsearchIntegrationBundle\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\TokenType;

class ArrayPositionFunction extends FunctionNode
{
    public $array = null;
    public $value = null;

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->array = $parser->ArithmeticExpression();
        $parser->match(TokenType::T_COMMA);
        $this->value = $parser->ArithmeticExpression();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return sprintf(
            'array_position(array[%s]::integer[], %s)',
            $this->array->dispatch($sqlWalker),
            $this->value->dispatch($sqlWalker)
        );
    }
}