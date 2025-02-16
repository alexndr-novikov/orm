<?php
/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Parser\OutputNode;
use Cycle\ORM\Select\ConstrainInterface;
use Cycle\ORM\Select\QueryBuilder;
use Cycle\ORM\Select\RootLoader;
use Spiral\Database\Query\SelectQuery;
use Spiral\Pagination\PaginableInterface;

/**
 * Query builder and entity selector. Mocks SelectQuery. Attention, Selector does not mount RootLoader scope by default.
 *
 * Trait provides the ability to transparently configure underlying loader query.
 *
 * @method $this distinct()
 * @method $this where(...$args);
 * @method $this andWhere(...$args);
 * @method $this orWhere(...$args);
 * @method $this having(...$args);
 * @method $this andHaving(...$args);
 * @method $this orHaving(...$args);
 * @method $this orderBy($expression, $direction = 'ASC');
 *
 * @method int avg($identifier) Perform aggregation (AVG) based on column or expression value.
 * @method int min($identifier) Perform aggregation (MIN) based on column or expression value.
 * @method int max($identifier) Perform aggregation (MAX) based on column or expression value.
 * @method int sum($identifier) Perform aggregation (SUM) based on column or expression value.
 */
final class Select implements \IteratorAggregate, \Countable, PaginableInterface
{
    /** @var ORMInterface @internal */
    private $orm;

    /** @var RootLoader */
    private $loader;

    /** @var QueryBuilder */
    private $builder;

    /**
     * @param ORMInterface $orm
     * @param string       $role
     */
    public function __construct(ORMInterface $orm, string $role)
    {
        $this->orm = $orm;
        $this->loader = new RootLoader($orm, $this->orm->resolveRole($role));
        $this->builder = new QueryBuilder($this->orm, $this->getLoader()->getQuery(), $this->loader);
    }

    /**
     * Create new Selector with applied scope. By default no constrain used.
     *
     * @param ConstrainInterface|null $constrain
     * @return Select
     */
    public function constrain(ConstrainInterface $constrain = null): self
    {
        $this->loader->setConstrain($constrain);

        return $this;
    }

    /**
     * Get Query proxy.
     *
     * @return QueryBuilder
     */
    public function getBuilder(): QueryBuilder
    {
        return $this->builder;
    }

    /**
     * Compiled SQL query, changes in this query would not affect Selector state (but binded parameters will).
     *
     * @return SelectQuery
     */
    public function buildQuery(): SelectQuery
    {
        return $this->getLoader()->buildQuery();
    }

    /**
     * Shortcut to where method to set AND condition for entity primary key.
     *
     * @param string|int $id
     * @return $this|self
     */
    public function wherePK($id): self
    {
        return $this->__call('where', [$this->getLoader()->getPK(), $id]);
    }

    /**
     * Attention, column will be quoted by driver!
     *
     * @param string|null $column When column is null DISTINCT(PK) will be generated.
     * @return int
     */
    public function count(string $column = null): int
    {
        if ($column === null) {
            // @tuneyourserver solves the issue with counting on queries with joins.
            $column = sprintf("DISTINCT(%s)", $this->getLoader()->getPK());
        }

        return (int)$this->__call('count', [$column]);
    }

    /**
     * @inheritdoc
     */
    public function limit(int $limit): self
    {
        $this->loader->getQuery()->distinct();
        $this->loader->getQuery()->limit($limit);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function offset(int $offset): self
    {
        $this->loader->getQuery()->offset($offset);

        return $this;
    }

    /**
     * Bypassing call to primary select query.
     *
     * @param string $name
     * @param array  $arguments
     * @return $this|mixed
     */
    public function __call(string $name, array $arguments)
    {
        if (in_array(strtoupper($name), ['AVG', 'MIN', 'MAX', 'SUM', 'COUNT'])) {
            $proxy = new QueryBuilder($this->orm, $this->loader->buildQuery(), $this->loader);

            // aggregations
            return $proxy->__call($name, $arguments);
        }

        $result = $this->getBuilder()->__call($name, $arguments);
        if ($result instanceof QueryBuilder) {
            return $this;
        }

        return $result;
    }

    /**
     * Request primary selector loader to pre-load relation name. Any type of loader can be used
     * for data pre-loading. ORM loaders by default will select the most efficient way to load
     * related data which might include additional select query or left join. Loaded data will
     * automatically pre-populate record relations. You can specify nested relations using "."
     * separator.
     *
     * Examples:
     *
     * // Select users and load their comments (will cast 2 queries, HAS_MANY comments)
     * User::find()->with('comments');
     *
     * // You can load chain of relations - select user and load their comments and post related to
     * //comment
     * User::find()->with('comments.post');
     *
     * // We can also specify custom where conditions on data loading, let's load only public
     * // comments.
     * User::find()->load('comments', [
     *      'where' => ['{@}.status' => 'public']
     * ]);
     *
     * Please note using "{@}" column name, this placeholder is required to prevent collisions and
     * it will be automatically replaced with valid table alias of pre-loaded comments table.
     *
     * // In case where your loaded relation is MANY_TO_MANY you can also specify pivot table
     * // conditions, let's pre-load all approved user tags, we can use same placeholder for pivot
     * // table alias
     * User::find()->load('tags', [
     *      'wherePivot' => ['{@}.approved' => true]
     * ]);
     *
     * // In most of cases you don't need to worry about how data was loaded, using external query
     * // or left join, however if you want to change such behaviour you can force load method to
     * // INLOAD
     * User::find()->load('tags', [
     *      'method'     => Loader::INLOAD,
     *      'wherePivot' => ['{@}.approved' => true]
     * ]);
     *
     * Attention, you will not be able to correctly paginate in this case and only ORM loaders
     * support different loading types.
     *
     * You can specify multiple loaders using array as first argument.
     *
     * Example:
     * User::find()->load(['posts', 'comments', 'profile']);
     *
     * Attention, consider disabling entity map if you want to use recursive loading (i.e
     * post.tags.posts), but first think why you even need recursive relation loading.
     *
     * @param string|array $relation
     * @param array        $options
     * @return $this|self
     * @see with()
     */
    public function load($relation, array $options = []): self
    {
        if (is_string($relation)) {
            $this->loader->loadRelation($relation, $options, false, true);

            return $this;
        }

        foreach ($relation as $name => $subOption) {
            if (is_string($subOption)) {
                // array of relation names
                $this->load($subOption, $options);
            } else {
                // multiple relations or relation with addition load options
                $this->load($name, $subOption + $options);
            }
        }

        return $this;
    }

    /**
     * With method is very similar to load() one, except it will always include related data to
     * parent query using INNER JOIN, this method can be applied only to ORM loaders and relations
     * using same database as parent record.
     *
     * Method generally used to filter data based on some relation condition. Attention, with()
     * method WILL NOT load relation data, it will only make it accessible in query.
     *
     * By default joined tables will be available in query based on relation name, you can change
     * joined table alias using relation option "alias".
     *
     * Do not forget to set DISTINCT flag while including HAS_MANY and MANY_TO_MANY relations. In
     * other scenario you will not able to paginate data well.
     *
     * Examples:
     *
     * // Find all users who have comments comments
     * User::find()->with('comments');
     *
     * // Find all users who have approved comments (we can use comments table alias in where
     * statement). User::find()->with('comments')->where('comments.approved', true);
     *
     * // Find all users who have posts which have approved comments
     * User::find()->with('posts.comments')->where('posts_comments.approved', true);
     *
     * // Custom join alias for post comments relation
     * $user->with('posts.comments', [
     *      'as' => 'comments'
     * ])->where('comments.approved', true);
     *
     * // If you joining MANY_TO_MANY relation you will be able to use pivot table used as relation
     * // name plus "_pivot" postfix. Let's load all users with approved tags.
     * $user->with('tags')->where('tags_pivot.approved', true);
     *
     * // You can also use custom alias for pivot table as well
     * User::find()->with('tags', [
     *      'pivotAlias' => 'tags_connection'
     * ])
     * ->where('tags_connection.approved', false);
     *
     * You can safely combine with() and load() methods.
     *
     * // Load all users with approved comments and pre-load all their comments
     * User::find()->with('comments')->where('comments.approved', true)->load('comments');
     *
     * // You can also use custom conditions in this case, let's find all users with approved
     * // comments and pre-load such approved comments
     * User::find()->with('comments')->where('comments.approved', true)
     *             ->load('comments', [
     *                  'where' => ['{@}.approved' => true]
     *              ]);
     *
     * // As you might notice previous construction will create 2 queries, however we can simplify
     * // this construction to use already joined table as source of data for relation via "using"
     * // keyword
     * User::find()->with('comments')
     *             ->where('comments.approved', true)
     *             ->load('comments', ['using' => 'comments']);
     *
     * // You will get only one query with INNER JOIN, to better understand this example let's use
     * // custom alias for comments in with() method.
     * User::find()->with('comments', ['as' => 'commentsR'])
     *             ->where('commentsR.approved', true)
     *             ->load('comments', ['using' => 'commentsR']);
     *
     * @param string|array $relation
     * @param array        $options
     * @return $this|self
     * @see load()
     */
    public function with($relation, array $options = []): self
    {
        if (is_string($relation)) {
            $this->loader->loadRelation($relation, $options, true, false);

            return $this;
        }

        foreach ($relation as $name => $options) {
            if (is_string($options)) {
                //Array of relation names
                $this->with($options, []);
            } else {
                //Multiple relations or relation with addition load options
                $this->with($name, $options);
            }
        }

        return $this;
    }

    /**
     * Find one entity or return null. Method provides the ability to configure custom query
     * parameters. Attention, method does not set a limit on selection (to avoid underselection of
     * joined tables), make sure to set the constrain in the query.
     *
     * @param array|null $query
     * @return object|null
     */
    public function fetchOne(array $query = null)
    {
        $data = (clone $this)->where($query)->fetchData();

        if (empty($data[0])) {
            return null;
        }

        return $this->orm->make($this->loader->getTarget(), $data[0], Node::MANAGED);
    }

    /**
     * Fetch all records in a form of array.
     *
     * @return object[]
     */
    public function fetchAll(): array
    {
        return iterator_to_array($this->getIterator());
    }

    /**
     * @return Iterator
     */
    public function getIterator(): Iterator
    {
        return new Iterator($this->orm, $this->loader->getTarget(), $this->fetchData());
    }

    /**
     * Load data tree from database and linked loaders in a form of array.
     *
     * @param OutputNode $node When empty node will be created automatically by root relation
     *                         loader.
     * @return array
     */
    public function fetchData(OutputNode $node = null): array
    {
        $node = $node ?? $this->loader->createNode();
        $this->loader->loadData($node);

        return $node->getResult();
    }

    /**
     * Compiled SQL statement.
     *
     * @return string
     */
    public function sqlStatement(): string
    {
        return $this->buildQuery()->sqlStatement();
    }

    /**
     * Cloning with loader tree cloning.
     *
     * @attention at this moment binded query parameters would't be cloned!
     */
    public function __clone()
    {
        $this->loader = clone $this->loader;
        $this->builder = new QueryBuilder($this->orm, $this->getLoader()->getQuery(), $this->loader);
    }

    /**
     * Remove nested loaders and clean ORM link.
     */
    public function __destruct()
    {
        $this->orm = null;
        $this->loader = null;
        $this->builder = null;
    }

    /**
     * Return base loader associated with the selector.
     *
     * @return RootLoader
     */
    private function getLoader(): RootLoader
    {
        return $this->loader;
    }
}
