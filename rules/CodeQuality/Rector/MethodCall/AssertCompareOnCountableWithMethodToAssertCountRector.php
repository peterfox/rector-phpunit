<?php

declare(strict_types=1);

namespace Rector\PHPUnit\CodeQuality\Rector\MethodCall;

use Countable;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Type\ObjectType;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see \Rector\PHPUnit\Tests\CodeQuality\Rector\MethodCall\AssertCompareOnCountableWithMethodToAssertCountRector\AssertCompareOnCountableWithMethodToAssertCountRectorTest
 */
class AssertCompareOnCountableWithMethodToAssertCountRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('', [
            new CodeSample(
                <<<'CODE_SAMPLE'
$this->assertSame(1, $countable->count());
CODE_SAMPLE
                ,
                <<<'CODE_SAMPLE'
$this->assertCount(1, $countable);
CODE_SAMPLE
            ),
        ]);
    }

    /**
     * @return array<class-string<MethodCall|StaticCall>>
     */
    public function getNodeTypes(): array
    {
        return [MethodCall::class, StaticCall::class];
    }

    /**
     * @param MethodCall|StaticCall $node
     */
    public function refactor(Node $node): MethodCall|StaticCall|null
    {
        $class = $node instanceof StaticCall ? $node->class : $node->var;

        if ($this->getType($class)->isSuperTypeOf(new ObjectType('PHPUnit\Framework\TestCase'))->no()) {
            return null;
        }

        if (
            ! $node->name instanceof Identifier ||
            ($node->name->toLowerString() !== 'assertsame' && $node->name->toLowerString() !== 'assertequals')
        ) {
            return null;
        }

        $right = $node->getArgs()[1]
->value;

        if (
            ($right instanceof MethodCall)
            && $right->name instanceof Identifier
            && $right->name->toLowerString() === 'count'
            && $right->getArgs() === []
        ) {
            $type = $this->getType($right->var);

            if ((new ObjectType(Countable::class))->isSuperTypeOf($type)->yes()) {
                $args = $node->getArgs();
                $args[1] = $right->var;

                if ($node instanceof MethodCall) {
                    return $this->nodeFactory->createMethodCall($node->var, 'assertCount', $args);
                }

                if ($node->class instanceof Name) {
                    return $this->nodeFactory->createStaticCall($node->class->toString(), 'assertCount', $args);
                }
            }
        }

        return null;
    }
}
