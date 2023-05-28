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

namespace Drewlabs\LaravelQuery\Tests\Unit;

use function Drewlabs\LaravelQuery\Proxy\DMLManager;

use Drewlabs\LaravelQuery\Tests\Stubs\Person;
use Drewlabs\LaravelQuery\Tests\TestCase;
use Drewlabs\Query\PreparesFiltersArray;

class SelectQueryTest extends TestCase
{
    public function testSelectAllWithRelation()
    {
        $manager = DMLManager(Person::class);

        $result = $manager->select(PreparesFiltersArray::new(['where' => ['firstname', 'BENJAMIN']])->call(), ['*', 'addresses']);
        $this->assertTrue($result->first()->relationLoaded('addresses'), 'Expect the addresses relation to be loaded');
    }

    public function testSelectColumnsWithRelations()
    {
        $manager = DMLManager(Person::class);
        $result = $manager->select(PreparesFiltersArray::new(['where' => ['firstname', 'BENJAMIN']])->call(), ['firstname', 'lastname', 'addresses', 'profile']);
        $this->assertTrue($result->first()->relationLoaded('addresses'), 'Expect the addresses relation to be loaded');
        $array = $result->first()->toArray();
        $this->assertTrue('BENJAMIN' === $array['firstname'], 'Expect first name of the selected item to be BENJAMIN');
        $this->assertNull($array['phonenumber'] ?? null, 'Expect the phonenumber attribute to not be loaded');
    }

    public function testSelectMissingColumns()
    {
        $manager = DMLManager(Person::class);
        $result = $manager->select(PreparesFiltersArray::new(['where' => ['firstname', 'BENJAMIN']])->call(), ['firstname', 'lastname']);
        $array = $result->first()->toArray();
        $this->assertNull($array['phonenumber'] ?? null, 'Expect the phonenumber attribute to not be loaded');
    }

    public function test_select_one_method_parameters()
    {
        $manager = DMLManager(Person::class);
        $result = $manager->selectFirst(PreparesFiltersArray::new(['where' => ['firstname', 'BENJAMIN']])->call(), ['*', 'addresses']);
        $this->assertTrue($result->relationLoaded('addresses'), 'Expect the addresses relation to be loaded');
        $this->assertInstanceOf(Person::class, $result);
        $result = $manager->selectFirst(
            static function ($value) {
                return $value->toArray();
            }
        );
        $this->assertIsArray($result, 'Expect the transformation function passed as last argument to return a PHP array');
    }
}
