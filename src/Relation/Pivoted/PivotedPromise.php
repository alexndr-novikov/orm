<?php
/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Cycle\ORM\Relation\Pivoted;

use Cycle\ORM\Exception\ORMException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Iterator;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Parser\RootNode;
use Cycle\ORM\Promise\PromiseInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Select;
use Cycle\ORM\Select\JoinableLoader;
use Cycle\ORM\Select\Loader\ManyToManyLoader;

/**
 * Promise use loader to configure query and it's scope.
 */
final class PivotedPromise implements PromiseInterface
{
    /** @var ORMInterface @internal */
    private $orm;

    /** @var string */
    private $target;

    /** @var array */
    private $relationSchema = [];

    /** @var mixed */
    private $innerKey;

    /** @var null|PivotedStorage */
    private $resolved;

    /**
     * @param ORMInterface $orm
     * @param string       $target
     * @param array        $relationSchema
     * @param mixed        $innerKey
     */
    public function __construct(ORMInterface $orm, string $target, array $relationSchema, $innerKey)
    {
        $this->orm = $orm;
        $this->target = $target;
        $this->relationSchema = $relationSchema;
        $this->innerKey = $innerKey;
    }

    /**
     * @inheritdoc
     */
    public function __loaded(): bool
    {
        return $this->orm === null;
    }

    /**
     * @inheritdoc
     */
    public function __role(): string
    {
        return $this->target;
    }

    /**
     * @inheritdoc
     */
    public function __scope(): array
    {
        return [
            $this->relationSchema[Relation::INNER_KEY] => $this->innerKey
        ];
    }

    /**
     * @return PivotedStorage
     */
    public function __resolve()
    {
        if ($this->orm === null) {
            return $this->resolved;
        }

        if (!$this->orm instanceof Select\SourceProviderInterface) {
            throw new ORMException("PivotedPromise require ORM to implement SourceFactoryInterface");
        }

        $table = $this->orm->getSource($this->target)->getTable();

        // getting scoped query
        $root = new Select\RootLoader($this->orm, $this->target);
        $query = $root->buildQuery();

        // responsible for all the scoping
        $loader = new ManyToManyLoader($this->orm, $table, $this->target, $this->relationSchema);

        /** @var ManyToManyLoader $loader */
        $loader = $loader->withContext($loader, [
            'constrain' => $this->orm->getSource($this->target)->getConstrain(),
            'as'        => $table,
            'method'    => JoinableLoader::POSTLOAD
        ]);

        $query = $loader->configureQuery($query, [$this->innerKey]);

        // we are going to add pivot node into virtual root node to aggregate the results
        $root = new RootNode([$this->relationSchema[Relation::INNER_KEY]], $this->relationSchema[Relation::INNER_KEY]);

        $node = $loader->createNode();
        $root->linkNode('output', $node);

        // emulate presence of parent entity
        $root->parseRow(0, [$this->innerKey]);

        $iterator = $query->getIterator();
        foreach ($iterator as $row) {
            $node->parseRow(0, $row);
        }
        $iterator->close();

        $elements = [];
        $pivotData = new \SplObjectStorage();
        foreach (new Iterator($this->orm, $this->target, $root->getResult()[0]['output']) as $pivot => $entity) {
            $pivotData[$entity] = $this->orm->make(
                $this->relationSchema[Relation::THOUGH_ENTITY],
                $pivot,
                Node::MANAGED
            );

            $elements[] = $entity;
        }

        $this->resolved = new PivotedStorage($elements, $pivotData);
        $this->orm = null;

        return $this->resolved;
    }
}
