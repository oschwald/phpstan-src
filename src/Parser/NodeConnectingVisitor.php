<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use function array_pop;
use function count;

final class NodeConnectingVisitor extends NodeVisitorAbstract
{

	/** @var Node[] */
	private array $stack = [];

	/** @var array<int, class-string<Node\Stmt>> */
	private array $typeStack = [];

	private ?Node $previous = null;

	public function __construct(private bool $compatibility)
	{
	}

	public function beforeTraverse(array $nodes)
	{
		$this->typeStack = [];
		if (!$this->compatibility) {
			return;
		}
		$this->stack = [];
		$this->previous = null;

		return null;
	}

	public function enterNode(Node $node)
	{
		if ($node instanceof Node\Stmt) {
			if (count($this->typeStack) > 0) {
				$node->setAttribute('parentStmtTypes', $this->typeStack);
			}
			$this->typeStack[] = get_class($node);
		}

		if (!$this->compatibility) {
			return;
		}

		if (count($this->stack) > 0) {
			$node->setAttribute('parent', $this->stack[count($this->stack) - 1]);
		}

		if ($this->previous !== null && $this->previous->getAttribute('parent') === $node->getAttribute('parent')) {
			$node->setAttribute('previous', $this->previous);
			$this->previous->setAttribute('next', $node);
		}

		$this->stack[] = $node;

		return null;
	}

	public function leaveNode(Node $node)
	{
		if ($node instanceof Node\Stmt) {
			array_pop($this->typeStack);
		}

		if (!$this->compatibility) {
			return;
		}

		$this->previous = $node;
		array_pop($this->stack);

		return null;
	}

}
