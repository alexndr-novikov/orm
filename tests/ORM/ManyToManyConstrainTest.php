<?php
/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Cycle\ORM\Tests;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Tag;
use Cycle\ORM\Tests\Fixtures\TagContext;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class ManyToManyConstrainTest extends BaseTest
{
    use TableTrait;

    public function setUp()
    {
        parent::setUp();

        $this->makeTable('user', [
            'id'      => 'primary',
            'email'   => 'string',
            'balance' => 'float'
        ]);

        $this->makeTable('tag', [
            'id'    => 'primary',
            'level' => 'integer',
            'name'  => 'string'
        ]);

        $this->makeTable('tag_user_map', [
            'id'      => 'primary',
            'user_id' => 'integer',
            'tag_id'  => 'integer',
            'as'      => 'string,nullable'
        ]);

        $this->makeFK('tag_user_map', 'user_id', 'user', 'id');
        $this->makeFK('tag_user_map', 'tag_id', 'tag', 'id');

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ]
        );

        $this->getDatabase()->table('tag')->insertMultiple(
            ['name', 'level'],
            [
                ['tag a', 1],
                ['tag b', 2],
                ['tag c', 3],
                ['tag d', 4],
                ['tag e', 5],
                ['tag f', 6],
            ]
        );

        $this->getDatabase()->table('tag_user_map')->insertMultiple(
            ['user_id', 'tag_id'],
            [
                [1, 1],
                [1, 2],
                [2, 3],

                [1, 4],
                [1, 5],

                [2, 4],
                [2, 6],
            ]
        );
    }

    public function testOrdered()
    {
        $this->orm = $this->withTagSchema([
            Schema::CONSTRAIN => new Select\QueryConstrain([], ['@.level' => 'ASC']),
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        list($a, $b) = $selector->load('tags')->fetchAll();

        $this->assertCount(4, $a->tags);
        $this->assertCount(3, $b->tags);

        $this->assertSame("tag a", $a->tags[0]->name);
        $this->assertSame("tag b", $a->tags[1]->name);
        $this->assertSame("tag d", $a->tags[2]->name);
        $this->assertSame("tag e", $a->tags[3]->name);

        $this->assertSame("tag c", $b->tags[0]->name);
        $this->assertSame("tag d", $b->tags[1]->name);
        $this->assertSame("tag f", $b->tags[2]->name);
    }

    public function testOrderedDESC()
    {
        $this->orm = $this->withTagSchema([
            Schema::CONSTRAIN => new Select\QueryConstrain([], ['@.level' => 'DESC']),
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        list($a, $b) = $selector->load('tags')->fetchAll();

        $this->assertCount(4, $a->tags);
        $this->assertCount(3, $b->tags);

        $this->assertSame("tag a", $a->tags[3]->name);
        $this->assertSame("tag b", $a->tags[2]->name);
        $this->assertSame("tag d", $a->tags[1]->name);
        $this->assertSame("tag e", $a->tags[0]->name);

        $this->assertSame("tag c", $b->tags[2]->name);
        $this->assertSame("tag d", $b->tags[1]->name);
        $this->assertSame("tag f", $b->tags[0]->name);
    }

    public function testOrderedInload()
    {
        $this->orm = $this->withTagSchema([
            Schema::CONSTRAIN => new Select\QueryConstrain([], ['@.level' => 'ASC']),
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        list($a, $b) = $selector->load('tags', [
            'method' => Select\JoinableLoader::INLOAD
        ])->orderBy('user.id')->fetchAll();

        $this->assertCount(4, $a->tags);
        $this->assertCount(3, $b->tags);

        $this->assertSame("tag a", $a->tags[0]->name);
        $this->assertSame("tag b", $a->tags[1]->name);
        $this->assertSame("tag d", $a->tags[2]->name);
        $this->assertSame("tag e", $a->tags[3]->name);

        $this->assertSame("tag c", $b->tags[0]->name);
        $this->assertSame("tag d", $b->tags[1]->name);
        $this->assertSame("tag f", $b->tags[2]->name);
    }

    public function testOrderedDESCInload()
    {
        $this->orm = $this->withTagSchema([
            Schema::CONSTRAIN => new Select\QueryConstrain([], ['@.level' => 'DESC']),
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        list($a, $b) = $selector->load('tags', [
            'method' => Select\JoinableLoader::INLOAD
        ])->orderBy('user.id')->fetchAll();

        $this->assertCount(4, $a->tags);
        $this->assertCount(3, $b->tags);

        $this->assertSame("tag a", $a->tags[3]->name);
        $this->assertSame("tag b", $a->tags[2]->name);
        $this->assertSame("tag d", $a->tags[1]->name);
        $this->assertSame("tag e", $a->tags[0]->name);

        $this->assertSame("tag c", $b->tags[2]->name);
        $this->assertSame("tag d", $b->tags[1]->name);
        $this->assertSame("tag f", $b->tags[0]->name);
    }

    public function testOrderedPromisedASC()
    {
        $this->orm = $this->withTagSchema([
            Schema::CONSTRAIN => new Select\QueryConstrain([], ['@.level' => 'ASC']),
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        list($a, $b) = $selector->orderBy('user.id')->fetchAll();

        $this->captureReadQueries();
        $this->assertCount(4, $a->tags);
        $this->assertCount(3, $b->tags);
        $this->assertNumReads(2);

        $this->assertSame("tag a", $a->tags[0]->name);
        $this->assertSame("tag b", $a->tags[1]->name);
        $this->assertSame("tag d", $a->tags[2]->name);
        $this->assertSame("tag e", $a->tags[3]->name);

        $this->assertSame("tag c", $b->tags[0]->name);
        $this->assertSame("tag d", $b->tags[1]->name);
        $this->assertSame("tag f", $b->tags[2]->name);
    }

    public function testOrderedPromisedDESC()
    {
        $this->orm = $this->withTagSchema([
            Schema::CONSTRAIN => new Select\QueryConstrain([], ['@.level' => 'DESC']),
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        list($a, $b) = $selector->orderBy('user.id')->fetchAll();

        $this->captureReadQueries();
        $this->assertCount(4, $a->tags);
        $this->assertCount(3, $b->tags);
        $this->assertNumReads(2);

        $this->assertSame("tag a", $a->tags[3]->name);
        $this->assertSame("tag b", $a->tags[2]->name);
        $this->assertSame("tag d", $a->tags[1]->name);
        $this->assertSame("tag e", $a->tags[0]->name);

        $this->assertSame("tag c", $b->tags[2]->name);
        $this->assertSame("tag d", $b->tags[1]->name);
        $this->assertSame("tag f", $b->tags[0]->name);
    }

    public function testOrderedAndWhere()
    {
        $this->orm = $this->withTagSchema([
            Schema::CONSTRAIN => new Select\QueryConstrain([], ['@.level' => 'ASC']),
            Relation::WHERE   => ['@.level' => ['>=' => 3]]
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        list($a, $b) = $selector->load('tags')->orderBy('user.id')->fetchAll();

        $this->assertCount(2, $a->tags);
        $this->assertCount(3, $b->tags);

        $this->assertSame("tag d", $a->tags[0]->name);
        $this->assertSame("tag e", $a->tags[1]->name);

        $this->assertSame("tag c", $b->tags[0]->name);
        $this->assertSame("tag d", $b->tags[1]->name);
        $this->assertSame("tag f", $b->tags[2]->name);
    }

    public function testOrderedDESCAndWhere()
    {
        $this->orm = $this->withTagSchema([
            Schema::CONSTRAIN => new Select\QueryConstrain([], ['@.level' => 'DESC']),
            Relation::WHERE   => ['@.level' => ['>=' => 3]]
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        list($a, $b) = $selector->load('tags')->orderBy('user.id')->fetchAll();

        $this->assertCount(2, $a->tags);
        $this->assertCount(3, $b->tags);

        $this->assertSame("tag d", $a->tags[1]->name);
        $this->assertSame("tag e", $a->tags[0]->name);

        $this->assertSame("tag c", $b->tags[2]->name);
        $this->assertSame("tag d", $b->tags[1]->name);
        $this->assertSame("tag f", $b->tags[0]->name);
    }

    public function testOrderedAndWhereInload()
    {
        $this->orm = $this->withTagSchema([
            Schema::CONSTRAIN => new Select\QueryConstrain([], ['@.level' => 'ASC']),
            Relation::WHERE   => ['@.level' => ['>=' => 3]]
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        list($a, $b) = $selector->load('tags', [
            'method' => Select\JoinableLoader::INLOAD
        ])->orderBy('user.id')->fetchAll();

        $this->assertCount(2, $a->tags);
        $this->assertCount(3, $b->tags);

        $this->assertSame("tag d", $a->tags[0]->name);
        $this->assertSame("tag e", $a->tags[1]->name);

        $this->assertSame("tag c", $b->tags[0]->name);
        $this->assertSame("tag d", $b->tags[1]->name);
        $this->assertSame("tag f", $b->tags[2]->name);
    }

    public function testOrderedDESCAndWhereInload()
    {
        $this->orm = $this->withTagSchema([
            Schema::CONSTRAIN => new Select\QueryConstrain([], ['@.level' => 'DESC']),
            Relation::WHERE   => ['@.level' => ['>=' => 3]]
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        list($a, $b) = $selector->load('tags', [
            'method' => Select\JoinableLoader::INLOAD
        ])->orderBy('user.id')->fetchAll();

        $this->assertCount(2, $a->tags);
        $this->assertCount(3, $b->tags);

        $this->assertSame("tag d", $a->tags[1]->name);
        $this->assertSame("tag e", $a->tags[0]->name);

        $this->assertSame("tag c", $b->tags[2]->name);
        $this->assertSame("tag d", $b->tags[1]->name);
        $this->assertSame("tag f", $b->tags[0]->name);
    }

    public function testOrderedAndWherePromise()
    {
        $this->orm = $this->withTagSchema([
            Schema::CONSTRAIN => new Select\QueryConstrain([], ['@.level' => 'ASC']),
            Relation::WHERE   => ['@.level' => ['>=' => 3]]
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        list($a, $b) = $selector->orderBy('user.id')->fetchAll();

        $this->captureReadQueries();
        $this->assertCount(2, $a->tags);
        $this->assertCount(3, $b->tags);
        $this->assertNumReads(2);

        $this->assertSame("tag d", $a->tags[0]->name);
        $this->assertSame("tag e", $a->tags[1]->name);

        $this->assertSame("tag c", $b->tags[0]->name);
        $this->assertSame("tag d", $b->tags[1]->name);
        $this->assertSame("tag f", $b->tags[2]->name);
    }

    public function testOrderedDESCAndWherePromise()
    {
        $this->orm = $this->withTagSchema([
            Schema::CONSTRAIN => new Select\QueryConstrain([], ['@.level' => 'DESC']),
            Relation::WHERE   => ['@.level' => ['>=' => 3]]
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        list($a, $b) = $selector->orderBy('user.id')->fetchAll();

        $this->captureReadQueries();
        $this->assertCount(2, $a->tags);
        $this->assertCount(3, $b->tags);
        $this->assertNumReads(2);

        $this->assertSame("tag d", $a->tags[1]->name);
        $this->assertSame("tag e", $a->tags[0]->name);

        $this->assertSame("tag c", $b->tags[2]->name);
        $this->assertSame("tag d", $b->tags[1]->name);
        $this->assertSame("tag f", $b->tags[0]->name);
    }

    public function testCustomWhere()
    {
        $this->orm = $this->withTagSchema([
            Schema::CONSTRAIN => new Select\QueryConstrain([], ['@.level' => 'DESC']),
            Relation::WHERE   => ['@.level' => ['>=' => 3]]
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        list($a, $b) = $selector->load('tags', [
            'where' => ['@.level' => 1]
        ])->orderBy('user.id')->fetchAll();

        $this->assertCount(1, $a->tags);
        $this->assertCount(0, $b->tags);

        $this->assertSame("tag a", $a->tags[0]->name);
    }

    public function testCustomWhereInload()
    {
        $this->orm = $this->withTagSchema([
            Schema::CONSTRAIN => new Select\QueryConstrain([], ['@.level' => 'DESC']),
            Relation::WHERE   => ['@.level' => ['>=' => 3]]
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        list($a) = $selector->load('tags', [
            'method' => Select\JoinableLoader::INLOAD,
            'where'  => ['@.level' => 1]
        ])->orderBy('user.id')->fetchAll();

        $this->assertCount(1, $a->tags);

        $this->assertSame("tag a", $a->tags[0]->name);
    }

    public function testWithWhere()
    {
        $this->orm = $this->withTagSchema([
            Relation::WHERE => ['@.level' => ['>=' => 6]]
        ]);

        $selector = new Select($this->orm, User::class);

        $res = $selector->with('tags')->orderBy('user.id')->fetchAll();

        $this->assertCount(1, $res);
        $this->assertSame('another@world.com', $res[0]->email);
    }

    public function testWithWhereAltered()
    {
        $this->orm = $this->withTagSchema([
            Relation::WHERE => ['@.level' => ['>=' => 6]]
        ]);

        $selector = new Select($this->orm, User::class);

        $res = $selector->with('tags', [
            'where' => ['@.level' => ['>=' => 5]]
        ])->orderBy('user.id')->fetchAll();

        $this->assertCount(2, $res);
        $this->assertSame('hello@world.com', $res[0]->email);
        $this->assertSame('another@world.com', $res[1]->email);
    }

    public function testLimitParentSelection()
    {
        $this->orm = $this->withTagSchema([]);

        $selector = new Select($this->orm, User::class);

        // second user has been filtered out
        $res = $selector
            ->load('tags')
            ->limit(1)
            ->orderBy('user.id')->fetchAll();

        $this->assertCount(1, $res);
        $this->assertSame('hello@world.com', $res[0]->email);
        $this->assertCount(4, $res[0]->tags);
    }

    /**
     * @expectedException \Cycle\ORM\Exception\LoaderException
     */
    public function testLimitParentSelectionError()
    {
        $this->orm = $this->withTagSchema([]);

        $selector = new Select($this->orm, User::class);

        // second user has been filtered out
        $res = $selector
            ->load('tags', ['method' => Select\JoinableLoader::INLOAD])
            ->limit(1)
            ->orderBy('user.id')->fetchAll();
    }

    /**
     * @expectedException \Spiral\Database\Exception\StatementException
     */
    public function testInvalidOrderBy()
    {
        $this->orm = $this->withTagSchema([
            Schema::CONSTRAIN => new Select\QueryConstrain([], ['@.column' => 'ASC']),
        ]);

        $selector = new Select($this->orm, User::class);

        $res = $selector->with('tags')->orderBy('user.id')->fetchAll();
    }

    protected function withTagSchema(array $relationSchema)
    {
        $eSchema = [];
        $rSchema = [];

        if (isset($relationSchema[Schema::CONSTRAIN])) {
            $eSchema[Schema::CONSTRAIN] = $relationSchema[Schema::CONSTRAIN];
        }

        if (isset($relationSchema[Relation::WHERE])) {
            $rSchema[Relation::WHERE] = $relationSchema[Relation::WHERE];
        }

        return $this->withSchema(new Schema([
            User::class       => [
                Schema::ROLE        => 'user',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'balance'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'tags' => [
                        Relation::TYPE   => Relation::MANY_TO_MANY,
                        Relation::TARGET => Tag::class,
                        Relation::LOAD   => Relation::LOAD_PROMISE,
                        Relation::SCHEMA => [
                                Relation::CASCADE          => true,
                                Relation::THOUGH_ENTITY    => TagContext::class,
                                Relation::INNER_KEY        => 'id',
                                Relation::OUTER_KEY        => 'id',
                                Relation::THOUGH_INNER_KEY => 'user_id',
                                Relation::THOUGH_OUTER_KEY => 'tag_id',
                            ] + $rSchema,
                    ]
                ]
            ],
            Tag::class        => [
                    Schema::ROLE        => 'tag',
                    Schema::MAPPER      => Mapper::class,
                    Schema::DATABASE    => 'default',
                    Schema::TABLE       => 'tag',
                    Schema::PRIMARY_KEY => 'id',
                    Schema::COLUMNS     => ['id', 'name', 'level'],
                    Schema::SCHEMA      => [],
                    Schema::RELATIONS   => []
                ] + $eSchema,
            TagContext::class => [
                Schema::ROLE        => 'tag_context',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'tag_user_map',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'user_id', 'tag_id', 'as'],
                Schema::TYPECAST    => ['id' => 'int', 'user_id' => 'int', 'tag_id' => 'int'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ]
        ]));
    }
}
