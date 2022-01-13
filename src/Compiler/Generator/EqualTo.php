<?php
declare(strict_types=1);

namespace Octopush\Plumbok\Compiler\Generator;

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use phpDocumentor\Reflection\Types\Boolean;
use phpDocumentor\Reflection\Types\Object_;
use PhpParser\Node;
use Octopush\Plumbok\Compiler;
use Octopush\Plumbok\Compiler\Statements;

/**
 * Class EqualTo
 * @package Octopush\Plumbok\Compiler\Generator
 * @author Michał Brzuchalski <michal.brzuchalski@gmail.com>
 */
class EqualTo extends GeneratorBase
{
    use WithClassName, WithTypeResolver, WithProperties;

    /**
     * @return Compiler\Statements
     */
    public function generate(): Compiler\Statements {
        $docBlock = new DocBlock(
            'Compares two ' . $this->className . ' objects are equalTo',
            null,
            [new Param('other', new Object_()), new Return_(new Boolean())],
            $this->typeContext
        );
        $result = new Statements();
        $result->add(new Node\Stmt\ClassMethod(
            'equalTo',
            [
                'flags' => Node\Stmt\Class_::MODIFIER_PUBLIC,
                'params' => [new Node\Param(new Node\Expr\Variable('other'))],
                'stmts' => [
                    new Node\Stmt\Return_(new Node\Expr\BinaryOp\BooleanAnd(
                        new Node\Expr\BinaryOp\Equal(
                            new Node\Expr\FuncCall(new Node\Name('get_class'), [new Node\Arg(new Node\Scalar\String_('other'))]),
                            new Node\Expr\ConstFetch(new Node\Name('self::class'))
                        ),
                        $this->createPropertyCompare()
                    )),
                ],
                'returnType' => 'bool',
            ], [
                'comments' => [$this->createComment($docBlock)],
            ]
        ));

        return $result;
    }

    /**
     * @return Node\Expr\BinaryOp\Equal|Node\Expr\BinaryOp\BooleanAnd|null
     */
    private function createPropertyCompare(): Node\Expr\BinaryOp\Equal|Node\Expr\BinaryOp\BooleanAnd|null {
        $comparison = null;
        foreach ($this->properties as $property) {
            if (is_null($comparison)) {
                $comparison = new Node\Expr\BinaryOp\Equal(
                    new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $property->getName()),
                    new Node\Expr\PropertyFetch(new Node\Expr\Variable('other'), $property->getName())
                );
            } else {
                $comparison = new Node\Expr\BinaryOp\BooleanAnd(
                    $comparison,
                    new Node\Expr\BinaryOp\Equal(
                        new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $property->getName()),
                        new Node\Expr\PropertyFetch(new Node\Expr\Variable('other'), $property->getName())
                    )
                );
            }
        }

        return $comparison;
    }
}