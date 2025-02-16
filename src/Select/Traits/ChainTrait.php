<?php
/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Cycle\ORM\Select\Traits;

use Cycle\ORM\Exception\LoaderException;
use Cycle\ORM\Select\AbstractLoader;
use Cycle\ORM\Select\LoaderInterface;

trait ChainTrait
{
    /**
     * Check if given relation points to the relation chain.
     *
     * @param string $relation
     * @return bool
     */
    protected function isChain(string $relation): bool
    {
        return strpos($relation, '.') !== false;
    }

    /**
     * @see loadRelation()
     * @see joinRelation()
     *
     * @param string $chain
     * @param array  $options Final loader options.
     * @param bool   $join    See loadRelation().
     * @return LoaderInterface
     *
     * @throws LoaderException When one of the elements can not be chained.
     */
    protected function loadChain(string $chain, array $options, bool $join, bool $load): LoaderInterface
    {
        $position = strpos($chain, '.');

        // chain of relations provided (relation.nestedRelation)
        $child = $this->loadRelation(substr($chain, 0, $position), [], $join, $load);

        if (!$child instanceof AbstractLoader) {
            throw new LoaderException(
                sprintf("Loader '%s' does not support chain relation loading", get_class($child))
            );
        }

        // load nested relation thought chain (chainOptions prior to user options)
        return $child->loadRelation(substr($chain, $position + 1), $options, $join, $load);
    }

    /**
     * @inheritdoc
     */
    abstract public function loadRelation(
        string $relation,
        array $options,
        bool $join = false,
        bool $load = false
    ): LoaderInterface;
}
