<?php

declare(strict_types=1);

/*
 * This file is part of the drewlabs namespace.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\Laravel\Query\Tests\Unit;

use function Drewlabs\Laravel\Query\Proxy\CreateQueryFilters;
use function Drewlabs\Laravel\Query\Proxy\DMLManager;

use Drewlabs\Laravel\Query\Tests\Stubs\Person;
use Drewlabs\Laravel\Query\Tests\Stubs\PostType;
use Drewlabs\Laravel\Query\Tests\Stubs\Profil;
use Drewlabs\Laravel\Query\Tests\TestCase;
use Drewlabs\Query\AggregationMethods;
use Drewlabs\Query\Contracts\EnumerableResultInterface;
use Drewlabs\Query\PreparesFiltersArray;
use Illuminate\Contracts\Pagination\Paginator;

use Illuminate\Support\Collection;

class QueryLanguageTest extends TestCase
{
    public function test_create_method()
    {
        $dmlManager = DMLManager(Person::class);
        $person = $dmlManager->create([
            'firstname' => 'FERA ADEVOU',
            'lastname' => 'EKPEH',
            'phonenumber' => '+22892002345',
            'age' => 33,
            'sex' => 'M',
        ]);
        $this->assertTrue('EKPEH' === $person->lastname, 'Expect the created person lastname to equals EKPEH');
    }

    public function test_create_post_types_with_posts_and_comments()
    {
        $result = DMLManager(PostType::class)->create(
            [
                'label' => 'MyPostType',
                'posts' => [
                    [
                        'title' => 'Environments',
                        'body' => 'Environment Lorem Ipsum',
                        'comments' => [
                            [
                                'body' => 'Enviroments comments',
                            ],
                            [
                                'body' => 'I Love environments',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'relations' => [
                    'posts',
                    'posts.comments',
                ],
            ]
        );
        $this->assertTrue(1 === $result->posts->count());
        $this->assertTrue(2 === $result->posts->first()->comments->count());
    }

    public function test_create_post_types_with_post_and_comment()
    {
        $result = DMLManager(PostType::class)->create(
            [
                'label' => 'MyPostType',
                'posts' => [
                    'title' => 'Environments',
                    'body' => 'Environment Lorem Ipsum',
                    'comments' => [
                        'body' => 'Enviroments comments',
                    ],
                ],
            ],
            [
                'relations' => [
                    'posts',
                    'posts.comments',
                ],
            ]
        );
        $this->assertTrue(1 === $result->posts->count());
        $this->assertTrue(1 === $result->posts->first()->comments->count());
    }

    public function test_create_with_params_method()
    {
        $dmlManager = DMLManager(Person::class);
        $person = $dmlManager->create(
            [
                'firstname' => 'FERA ADEVOU',
                'lastname' => 'EKPEH',
                'phonenumber' => '+22892002345',
                'age' => 33,
                'sex' => 'M',
                'addresses' => [
                    [
                        'postal_code' => 'BP 228 LOME - TOGO',
                        'country' => 'TOGO',
                        'city' => 'LOME',
                        'email' => 'lordfera@gmail.com',
                    ],
                    [
                        'postal_code' => 'BP 229 LOME - TOGO',
                        'country' => 'BENIN',
                        'city' => 'COTONOU',
                        'email' => 'lordfera2@gmail.com',
                    ],
                ],
                'profile' => [
                    'url' => 'https://i.picsum.photos/id/733/200/300.jpg?hmac=JYkTVVdGOo8BnLPxu1zWliHFvwXKurY-uTov5YiuX2s',
                ],
            ],
            [
                'relations' => [
                    'addresses',
                    'profile',
                ],
            ]
        );
        $this->assertInstanceOf(Collection::class, $person->addresses, 'Expect list of addresses to be an instance of laravel collection');
        $this->assertSame((int) $person->addresses->count(), (int) 2, 'Expect the inserted person total addresses to equal 2');
        $this->assertTrue('EKPEH' === $person->lastname, 'Expect the created person lastname to equals EKPEH');
    }

    public function test_create_with_closure_method()
    {
        $dmlManager = DMLManager(Person::class);
        $person = $dmlManager->create(
            [
                'firstname' => 'FERA ADEVOU',
                'lastname' => 'EKPEH',
                'phonenumber' => '+22892002345',
                'age' => 33,
                'sex' => 'M',
                'profile' => [
                    'url' => 'https://i.picsum.photos/id/733/200/300.jpg?hmac=JYkTVVdGOo8BnLPxu1zWliHFvwXKurY-uTov5YiuX2s',
                ],
            ],
            [
                'relations' => [
                    'profile',
                ],
            ],
            static function ($model) {
                return $model->toArray();
            }
        );
        $this->assertIsArray($person, 'Expect the create returned value to be an array');
        $this->assertTrue('EKPEH' === $person['lastname'], 'Expect the created person lastname to equals EKPEH');
    }

    public function test_create_upsert_method()
    {
        $dmlManager = DMLManager(Person::class);
        $dmlManager->create(
            [
                'firstname' => 'FERA',
                'lastname' => 'E. PAYARO',
                'phonenumber' => '+22892002345',
                'age' => 33,
                'sex' => 'M',
                'profile' => [
                    'url' => 'https://i.picsum.photos/id/733/200/300.jpg?hmac=JYkTVVdGOo8BnLPxu1zWliHFvwXKurY-uTov5YiuX2s',
                ],
            ],
            [
                'upsert' => true,
                'upsert_conditions' => [
                    'lastname' => 'PAYARO',
                ],
                'relations' => [
                    'profile',
                ],
            ]
        );
        $this->assertTrue(3 === $dmlManager->select()->count(), 'Expect the database to contain only 2 items after insertion');
        $this->assertTrue(0 === Person::where('lastname', 'PAYARO')->count(), 'Expect person having lastname == PAYARO to be modified');
    }

    public function test_create_many_method()
    {
        $dmlManager = DMLManager(Person::class);
        $result = $dmlManager->createMany([
            [
                'firstname' => 'AZADE',
                'lastname' => 'E. FERAH',
                'phonenumber' => '+22891002345',
                'age' => 29,
                'sex' => 'F',
            ],
            [
                'firstname' => 'DANKE',
                'lastname' => 'AMEKE',
                'phonenumber' => '+22892002345',
                'age' => 32,
                'sex' => 'F',
            ],
        ]);
        $this->assertTrue($result);
        $this->assertTrue(5 === $dmlManager->select()->count(), 'Expect the database to contain only 4 items after insertion');
    }

    public function test_select_method()
    {
        $manager = DMLManager(Person::class);
        $person = $manager->select('1', ['*'], static function ($model) {
            return $model->toArray();
        });
        $this->assertIsArray($person, 'Expect the returned person to be and array');
        $person = $manager->select(1);
        $this->assertInstanceOf(Person::class, $person, 'Expect $person to be an instance of '.Person::class);
        $list = $manager->select(PreparesFiltersArray::new(['where' => ['firstname', 'BENJAMIN'], 'orWhere' => ['lastname', 'AZOMEDOH']], ['firstname', 'addresses'])->call());
        $this->assertInstanceOf(EnumerableResultInterface::class, $list, 'Expect the returned result to be an instance of '.EnumerableResultInterface::class);
        $this->assertSame($list->count(), 2, 'Expect the total returned row to equals 2');
        $list = $manager->select(PreparesFiltersArray::new(['where' => ['firstname', 'BENJAMIN'], 'orWhere' => ['lastname', 'AZOMEDOH']])->call(), 15, ['addresses', 'profile'], 1);
        $this->assertInstanceOf(Paginator::class, $list, 'Expect the result of the query to be an instance of the paginator class');
        $this->assertSame($list->count(), 2, 'Expect the total returned row to equals 2');
        $this->assertTrue(
            $list->getCollection()->first()->relationLoaded('addresses'),
            'Expect the addresses relation to be loaded'
        );
    }

    public function test_update_method()
    {
        $manager = DMLManager(Person::class);

        // Update by ID
        $person = $manager->update(1, ['firstname' => 'BENBOSS']);
        $this->assertTrue('BENBOSS' === $person->firstname, 'Expect the modified person firstname to equal BENBOSS');
        $person = $manager->select(1);
        $this->assertTrue('BENBOSS' === $person->firstname, 'Expect the selected person firstname to equal BENBOSS');

        // Update by ID String
        $person = $manager->update('1', ['firstname' => 'AZANDREW']);
        $this->assertTrue('AZANDREW' === $person->firstname, 'Expect the modified person firstname to equal AZANDREW');

        // Update using query without mass update
        $count = $manager->update(PreparesFiltersArray::new(['where' => ['firstname', 'SIDOINE']])->call(), ['firstname' => 'AZANDREW']);
        $this->assertTrue(1 === $count, 'Expect the total updated items to equals 1');
        $list = $manager->select(PreparesFiltersArray::new(['where' => ['firstname', 'SIDOINE']])->call());
        $this->assertTrue(0 === $list->count(), 'Expect Not to find person with firstname === SIDOINE in the database');
    }

    public function test_delete_methods()
    {
        $manager = DMLManager(Person::class);
        // Update by ID
        $result = $manager->delete(1);
        $this->assertTrue($result, 'Expect the delete operation to return TRUE');
        $this->assertNull($manager->select(1), 'Expect database to not have person with id == 1');

        // Update by array query
        $result = $manager->delete(PreparesFiltersArray::new(['where' => ['firstname', 'SIDOINE']])->call(), true);
        $this->assertTrue(1 === $result, 'Expect the delete operation to return TRUE');
        $this->assertEquals(1, $manager->select()->count(), 'Expect the database person table to contains only 1 item');
    }

    public function test_select_aggregate()
    {
        $manager = DMLManager(Person::class);
        $this->assertSame(3, $manager->selectAggregate([], AggregationMethods::COUNT));
    }

    public function test_select_aggregate_sum()
    {
        $manager = DMLManager(Person::class);
        $this->assertSame(74, $manager->selectAggregate([], AggregationMethods::SUM, 'age'));
    }

    public function test_select_relations_as_columns()
    {
        $person = $this->personFactory()->create([
            'firstname' => 'Person Firstname',
            'lastname' => 'Person Lastname',
            'phonenumber' => '+22899001122',
            'age' => 10,
            'sex' => 'M',
            'is_active' => true,
        ]);
        $this->addressFactory()->create([
            'postal_code' => 'BP TEST LOME - TOGO',
            'country' => 'TG',
            'city' => 'LOME',
            'email' => 'address@example.com',
            'person_id' => $person->getKey(),
        ]);
        $profil = $this->profilFactory()->create([
            'person_id' => $person->getKey(),
            'url' => 'https://picsum.photos/id/1/200/300',
        ]);
        $profiles = DMLManager(Profil::class)->select($profil->getKey(), ['*', 'person.addresses']);
        $this->assertSame($profiles->person->getKey(), $person->getKey());
    }

    public function test_dml_create_model_and_parent_relation()
    {
        $profil = DMLManager(Profil::class)->create([
            'url' => 'https://picsum.photos/id/1/700/600',
            'person' => [
                'firstname' => 'Laura',
                'lastname' => 'R. Clifford',
                'phonenumber' => '509-733-6988',
                'age' => 74,
                'sex' => 'F',
            ],
        ], [
            'relations' => ['person', 'addresses'],
        ]);
        $this->assertNotNull(DMLManager(Profil::class)->select($profil->getKey()));
        $this->assertNotNull(Person::where('firstname', 'Laura')->where('lastname', 'R. Clifford')->first());
    }


    public function test_eloquent_query_filters_exists()
    {
        DMLManager(Person::class)->create(
            [
                'firstname' => 'JOHN',
                'lastname' => 'DUMELO',
                'phonenumber' => '+22892002345',
                'age' => 44,
                'sex' => 'M',
                'profile' => [
                    'url' => 'https://i.picsum.photos/id/733/200/300.jpg',
                ],
            ],
            ['relations' => ['profile']]
        );
        $result = DMLManager(Person::class)->select(CreateQueryFilters(['and' => [['firstname', 'JOHN'], ['lastname', 'DUMELO']], 'notExists' => ['column' => 'profile', 'match' => 'where(url,https://i.picsum.photos/id/733/200/300.jpg)']]))->first();
        $this->assertFalse(null !== $result);
        $result = DMLManager(Person::class)->select(CreateQueryFilters(['and' => [['firstname', 'JOHN'], ['lastname', 'DUMELO']], 'exists' => ['column' => 'profile', 'match' => 'where(url,https://i.picsum.photos/id/733/200/300.jpg)']]))->first();
        $this->assertTrue(null !== $result);
    }

    public function test_eloquent_query_filters_or_exists()
    {
        DMLManager(Person::class)->create(
            [
                'firstname' => 'JOHN',
                'lastname' => 'DUMELO',
                'phonenumber' => '+22892002345',
                'age' => 44,
                'sex' => 'M',
                'profile' => [
                    'url' => 'https://i.picsum.photos/id/733/200/300.jpg',
                ],
            ],
            ['relations' => ['profile']]
        );
        $result = DMLManager(Person::class)->select(CreateQueryFilters(['and' => [['firstname', 'JOHN'], ['lastname', 'DUMELO']], 'orNotExists' => ['column' => 'profile', 'match' => 'where(url,https://i.picsum.photos/id/733/200/300.jpg)']]))->first();
        $this->assertNotNull($result);
        $result = DMLManager(Person::class)->select(CreateQueryFilters(['and' => [['firstname', 'JOHN'], ['lastname', 'DUMELO']], 'orExists' => ['column' => 'profile', 'match' => 'where(url,https://i.picsum.photos/id/733/200/300.jpg)']]))->first();
        $this->assertNotNull($result);
    }
}
