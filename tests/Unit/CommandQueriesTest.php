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

use Drewlabs\Contracts\Support\Actions\ActionResult;
use Drewlabs\Core\Helpers\Functional;

use function Drewlabs\Laravel\Query\Proxy\CreateQueryAction;
use function Drewlabs\Laravel\Query\Proxy\CreateQueryFilters;
use function Drewlabs\Laravel\Query\Proxy\DeleteQueryAction;

use function Drewlabs\Laravel\Query\Proxy\DMLManager;
use function Drewlabs\Laravel\Query\Proxy\SelectQueryAction;

use function Drewlabs\Laravel\Query\Proxy\UpdateQueryAction;
use function Drewlabs\Laravel\Query\Proxy\useActionQueryCommand;

use Drewlabs\Laravel\Query\Tests\Stubs\Person;

use Drewlabs\Laravel\Query\Tests\TestCase;

class CommandQueriesTest extends TestCase
{
    public function test_create_action_on_object_parameter()
    {
        $person = $this->createPerson('Madison', 'Soto', '(734) 268-1291', 31);
        $this->assertInstanceOf(Person::class, $person);
        $this->assertNotNull($person->created_at);
        $this->assertSame('Madison', $person->firstname);
        $this->assertSame(31, $person->age);
    }

    public function test_select_query_action_on_query_filters()
    {
        $person = $this->createPerson('Stacey', 'Lowe', '(615) 804-1735', 65);
        $query = CreateQueryFilters(['where' => ['firstname', 'Stacey']]);
        $action = SelectQueryAction($query, Functional::compose(
            static function ($result) {
                return $result->first();
            }
        ));
        $result = DMLManager(Person::class)->select(...$action->payload()->toArray());
        $this->assertNotNull($result);
        $this->assertInstanceOf(Person::class, $result);
        $this->assertSame('Stacey', $person->firstname);
    }

    public function test_select_query_action_on_array_query()
    {
        $person = $this->createPerson('Mildred', 'Brown', '(702) 959-3715', 40);
        $action = SelectQueryAction(['and' => ['firstname', 'Mildred']], Functional::compose(
            static function ($result) {
                return $result->first();
            }
        ));
        $result = DMLManager(Person::class)->select(...$action->payload()->toArray());
        $this->assertNotNull($result);
        $this->assertInstanceOf(Person::class, $result);
        $this->assertSame('Mildred', $result->firstname);
        $this->assertSame('(702) 959-3715', $result->phonenumber);
    }

    public function test_update_query_action_on_array_query_using_object()
    {
        $person = $this->createPerson('Mildred', 'Brown', '(702) 959-3715', 40);
        $obj = new \stdClass();
        $obj->lastname = 'Welch';
        $obj->phonenumber = '(610) 535-4895';
        $action = UpdateQueryAction(['and' => ['firstname', 'Mildred']], $obj);
        $result = DMLManager(Person::class)->update(...$action->payload()->toArray());
        $this->assertSame(1, $result);
        $p = DMLManager(Person::class)->select($person->getKey());
        $this->assertSame('Welch', $p->lastname);
        $this->assertNotSame('(702) 959-3715', $p->phonenumber);
    }

    public function test_update_query_action_on_array_query_using_array()
    {
        $person = $this->createPerson('Mildred', 'Brown', '(702) 959-3715', 40);
        $obj = [
            'lastname' => 'Welch',
            'phonenumber' => '(610) 535-4895',
        ];
        $action = UpdateQueryAction(['and' => ['firstname', 'Mildred']], $obj);
        $result = DMLManager(Person::class)->update(...$action->payload()->toArray());
        $this->assertSame(1, $result);
        $p = DMLManager(Person::class)->select($person->getKey());
        $this->assertSame('Welch', $p->lastname);
        $this->assertNotSame('(702) 959-3715', $p->phonenumber);
    }

    public function test_update_query_action_on_filters_using_array()
    {
        $person = $this->createPerson('Mildred', 'Brown', '(702) 959-3715', 40);
        $obj = ['lastname' => 'Welch', 'phonenumber' => '(610) 535-4895'];
        $action = UpdateQueryAction(CreateQueryFilters(['where' => ['firstname', 'Mildred']]), $obj);
        $result = DMLManager(Person::class)->update(...$action->payload()->toArray());
        $this->assertSame(1, $result);
        $p = DMLManager(Person::class)->select($person->getKey());
        $this->assertSame('Welch', $p->lastname);
        $this->assertSame('(610) 535-4895', $p->phonenumber);
    }

    public function test_delete_query_action_on_filters()
    {
        $person = $this->createPerson('Mildred', 'Brown', '(702) 959-3715', 40);
        $action = DeleteQueryAction(CreateQueryFilters(['where' => ['firstname', 'Mildred']]));
        $result = DMLManager(Person::class)->delete(...$action->payload()->toArray());
        $this->assertSame(1, $result);
        $p = DMLManager(Person::class)->select($person->getKey());
        $this->assertNull($p);
    }

    public function test_delete_query_action_on_array_query()
    {
        $person = $this->createPerson('Mildred', 'Brown', '(702) 959-3715', 40);
        $action = DeleteQueryAction(['and' => ['firstname', 'Mildred']]);
        $result = DMLManager(Person::class)->delete(...$action->payload()->toArray());
        $this->assertSame(1, $result);
        $p = DMLManager(Person::class)->select($person->getKey());
        $this->assertNull($p);
    }

    public function test_use_dml_query_action_command_function()
    {
        $this->createPerson('Mildred', 'Brown', '(702) 959-3715', 40);
        $command = useActionQueryCommand(Person::class);
        $result = $command(SelectQueryAction(['and' => ['firstname', 'Mildred']]), static function ($result) {
            return $result->first();
        });
        $this->assertSame('Mildred', $result->firstname);
        $this->assertSame('(702) 959-3715', $result->phonenumber);
    }

    public function test_use_dml_query_action_override_hanlder()
    {
        $result = useActionQueryCommand(Person::class, static function ($action) {
            return $action->payload();
        })(SelectQueryAction(2));
        $this->assertInstanceOf(ActionResult::class, $result);
        $this->assertSame($result->value()->toArray(), [2]);
    }

    private function createPerson($firstname, $lastname, $phonenumber, $age, $sex = 'F')
    {
        $p = new \stdClass();
        $p->firstname = $firstname;
        $p->lastname = $lastname;
        $p->phonenumber = $phonenumber;
        $p->age = $age;
        $p->sex = $sex;
        $action = CreateQueryAction($p);
        $ql = DMLManager(Person::class);

        return $ql->create(...$action->payload()->toArray());
    }
}
