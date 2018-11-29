<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation\Morphed;

use Spiral\ORM\Command\ContextualInterface;
use Spiral\ORM\ORMInterface;
use Spiral\ORM\Relation;
use Spiral\ORM\Relation\HasManyRelation;
use Spiral\ORM\State;
use Spiral\ORM\Util\Collection\CollectionPromise;
use Spiral\ORM\Util\Promise;

class MorphedHasManyRelation extends HasManyRelation
{
    /** @var mixed|null */
    private $morphKey;

    /**
     * @param ORMInterface $orm
     * @param string       $class
     * @param string       $relation
     * @param array        $schema
     */
    public function __construct(ORMInterface $orm, string $class, string $relation, array $schema)
    {
        parent::__construct($orm, $class, $relation, $schema);
        $this->morphKey = $this->define(Relation::MORPH_KEY);
    }

    public function initPromise(State $state, $data): array
    {
        // todo: here we need paths (!)
        if (empty($innerKey = $this->fetchKey($state, $this->innerKey))) {
            return [null, null];
        }

        $pr = new Promise(
            [
                $this->outerKey => $innerKey,
                $this->morphKey => $state->getAlias()
            ],
            function (array $scope) use ($innerKey) {
                return $this->orm->getMapper($this->class)->getRepository()->findAll($scope);
            }
        );

        return [new CollectionPromise($pr), $pr];
    }

    /**
     * Persist related object.
     *
     * @param State  $parent
     * @param object $related
     * @return ContextualInterface
     */
    protected function queueStore(State $parent, $related): ContextualInterface
    {
        $store = parent::queueStore($parent, $related);

        if ($this->fetchKey($this->getState($related), $this->morphKey) != $parent->getAlias()) {
            $store->setContext($this->morphKey, $parent->getAlias());
        }

        return $store;
    }
}