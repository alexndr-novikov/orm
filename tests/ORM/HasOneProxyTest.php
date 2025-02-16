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
use Cycle\ORM\Promise\PromiseInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Nested;
use Cycle\ORM\Tests\Fixtures\Profile;
use Cycle\ORM\Tests\Fixtures\ProfileMapperWithProxy;
use Cycle\ORM\Tests\Fixtures\ProfilePromise;
use Cycle\ORM\Tests\Fixtures\PromiseFactory;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class HasOneProxyTest extends BaseTest
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

        $this->makeTable('profile', [
            'id'      => 'primary',
            'user_id' => 'integer',
            'image'   => 'string'
        ]);

        $this->makeTable('nested', [
            'id'         => 'primary',
            'profile_id' => 'integer',
            'label'      => 'string'
        ]);

        $this->makeFK('profile', 'user_id', 'user', 'id');
        $this->makeFK('nested', 'profile_id', 'profile', 'id');

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ]
        );

        $this->getDatabase()->table('profile')->insertMultiple(
            ['user_id', 'image'],
            [
                [1, 'image.png'],
            ]
        );


        $this->getDatabase()->table('nested')->insertMultiple(
            ['profile_id', 'label'],
            [
                [1, 'nested-label'],
            ]
        );

        $this->orm = $this->withSchema(new Schema([
            User::class    => [
                Schema::ROLE        => 'user',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'balance'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'profile' => [
                        Relation::TYPE   => Relation::HAS_ONE,
                        Relation::TARGET => Profile::class,
                        Relation::LOAD   => Relation::LOAD_PROMISE,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ],
                    ]
                ]
            ],
            Profile::class => [
                Schema::ROLE         => 'profile',
                Schema::MAPPER       => Mapper::class,
                Schema::DATABASE     => 'default',
                Schema::TABLE        => 'profile',
                Schema::PRIMARY_KEY  => 'id',
                Schema::FIND_BY_KEYS => ['user_id'],
                Schema::COLUMNS      => ['id', 'user_id', 'image'],
                Schema::SCHEMA       => [],
                Schema::RELATIONS    => [
                    'nested' => [
                        Relation::TYPE   => Relation::HAS_ONE,
                        Relation::TARGET => Nested::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'profile_id',
                        ],
                    ]
                ]
            ],
            Nested::class  => [
                Schema::ROLE        => 'nested',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'nested',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'profile_id', 'label'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ]
        ]))->withPromiseFactory(new PromiseFactory());
    }

    public function testFetchRelation()
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('profile');

        $this->assertEquals([
            [
                'id'      => 1,
                'email'   => 'hello@world.com',
                'balance' => 100.0,
                'profile' => [
                    'id'      => 1,
                    'user_id' => 1,
                    'image'   => 'image.png'
                ]
            ],
            [
                'id'      => 2,
                'email'   => 'another@world.com',
                'balance' => 200.0,
                'profile' => null
            ]
        ], $selector->fetchData());
    }


    public function testFetchPromises()
    {
        $selector = new Select($this->orm, User::class);
        $selector->orderBy('user.id');
        list($a, $b) = $selector->fetchAll();

        $this->assertInstanceOf(PromiseInterface::class, $a->profile);
        $this->assertInstanceOf(PromiseInterface::class, $b->profile);

        $this->assertInstanceOf(Profile::class, $a->profile);
        $this->assertInstanceOf(Profile::class, $b->profile);

        $this->assertInstanceOf(ProfilePromise::class, $a->profile);
        $this->assertInstanceOf(ProfilePromise::class, $b->profile);

        $this->assertEquals('image.png', $a->profile->getImage());
    }
}
