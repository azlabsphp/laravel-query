<?php

declare(strict_types=1);

/*
 * This file is part of the Drewlabs package.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Drewlabs\Contracts\Data\EnumerableQueryResult;
use Drewlabs\Contracts\Data\Model\Model;
use Drewlabs\Packages\Database\AggregationMethods;
use Drewlabs\Packages\Database\Tests\Stubs\Person;
use Drewlabs\Packages\Database\Tests\Stubs\PersonViewModelStub;
use Drewlabs\Packages\Database\Tests\TestCase;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;

class QueryableTest extends TestCase
{
    public function test_create_method()
    {
        $person = $this->getQueryable()->create([
            'firstname' => 'FERA ADEVOU',
            'lastname' => 'EKPEH',
            'phonenumber' => '+22892002345',
            'age' => 33,
            'sex' => 'M',
        ]);
        $this->assertTrue('EKPEH' === $person->lastname, 'Expect the created person lastname to equals EKPEH');
    }

    public function test_create_with_params_method()
    {
        $person = $this->getQueryable()->create(
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
        $person = $this->getQueryable()->create(
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
        $this->getQueryable()->create(
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
        $this->assertTrue(2 === $this->getQueryable()->select()->count(), 'Expect the database to contain only 2 items after insertion');
        $this->assertTrue(0 === Person::where('lastname', 'PAYARO')->count(), 'Expect person having lastname == PAYARO to be modified');
    }

    public function test_select_method()
    {
        $person = $this->getQueryable()->select('1', ['*'], static function ($model) {
            return $model->toArray();
        });
        $this->assertIsArray($person, 'Expect the returned person to be and array');
        $person = $this->getQueryable()->select(1);
        $this->assertInstanceOf(Model::class, $person, 'Expect $person to be an instance of '.Model::class);
        $list = $this->getQueryable()->select(
            [
                'where' => [
                    'firstname', 'BENJAMIN',
                ],
                'orWhere' => [
                    'lastname', 'AZOMEDOH',
                ],
            ],
            ['firstname', 'addresses']
        );
        $this->assertInstanceOf(EnumerableQueryResult::class, $list, 'Expect the returned result to be an instance of '.EnumerableQueryResult::class);
        $this->assertSame($list->count(), 2, 'Expect the total returned row to equals 2');
        $list = $this->getQueryable()->select(
            [
                'where' => [
                    'firstname', 'BENJAMIN',
                ],
                'orWhere' => [
                    'lastname', 'AZOMEDOH',
                ],
            ],
            15,
            ['addresses', 'profile'],
            1
        );
        $this->assertInstanceOf(Paginator::class, $list, 'Expect the result of the query to be an instance of the paginator class');
        $this->assertSame($list->count(), 2, 'Expect the total returned row to equals 2');
        $this->assertTrue(
            $list->getCollection()->first()->relationLoaded('addresses'),
            'Expect the addresses relation to be loaded'
        );
    }

    public function test_update_methods()
    {

        // Update by ID
        $person = $this->getQueryable()->update(1, [
            'firstname' => 'BENBOSS',
        ]);
        $this->assertTrue('BENBOSS' === $person->firstname, 'Expect the modified person firstname to equal BENBOSS');
        $person = $this->getQueryable()->select(1);
        $this->assertTrue('BENBOSS' === $person->firstname, 'Expect the selected person firstname to equal BENBOSS');

        // Update by ID String
        $person = $this->getQueryable()->update('1', [
            'firstname' => 'AZANDREW',
        ]);
        $this->assertTrue('AZANDREW' === $person->firstname, 'Expect the modified person firstname to equal AZANDREW');

        // Update using query without mass update
        $count = $this->getQueryable()->update(
            [
                'where' => ['firstname', 'SIDOINE'],
            ],
            [
                'firstname' => 'AZANDREW',
            ]
        );
        $this->assertTrue(1 === $count, 'Expect the total updated items to equals 1');
        $list = $this->getQueryable()->select(
            [
                'where' => [
                    'firstname', 'SIDOINE',
                ],
            ],
        );
        $this->assertTrue(0 === $list->count(), 'Expect Not to find person with firstname === SIDOINE in the database');
    }

    public function test_delete_methods()
    {
        $result = $this->getQueryable()->delete(1);
        $this->assertTrue($result, 'Expect the delete operation to return TRUE');
        $this->assertNull($this->getQueryable()->select(1), 'Expect database to not have person with id == 1');

        // Update by array query
        $result = $this->getQueryable()->delete(
            [
                'where' => ['firstname', 'SIDOINE'],
            ],
            true
        );
        $this->assertTrue(1 === $result, 'Expect the delete operation to return TRUE');
        $this->assertTrue(0 === $this->getQueryable()->select()->count(), 'Expect the database person table to be empty');
    }

    public function test_select_aggregate()
    {
        $this->assertSame(2, $this->getQueryable()->aggregate([], AggregationMethods::COUNT));
    }

    private function getQueryable()
    {
        return new PersonViewModelStub();
    }
}
