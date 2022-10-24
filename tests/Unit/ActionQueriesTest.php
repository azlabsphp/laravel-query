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

use Drewlabs\Contracts\Support\Actions\ActionResult;
use Drewlabs\Core\Helpers\Functional;
use function Drewlabs\Packages\Database\Proxy\CreateQueryAction;
use function Drewlabs\Packages\Database\Proxy\DeleteQueryAction;

use function Drewlabs\Packages\Database\Proxy\DMLManager;
use function Drewlabs\Packages\Database\Proxy\ModelFiltersHandler;
use function Drewlabs\Packages\Database\Proxy\SelectQueryAction;
use function Drewlabs\Packages\Database\Proxy\UpdateQueryAction;
use function Drewlabs\Packages\Database\Proxy\useDMLQueryActionCommand;
use Drewlabs\Packages\Database\Tests\Stubs\Person;
use Drewlabs\Packages\Database\Tests\TestCase;

class ActionQueriesTest extends TestCase
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
        $query = ModelFiltersHandler(['where' => ['firstname', 'Stacey']]);
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
        $action = SelectQueryAction(['where' => ['firstname', 'Mildred']], Functional::compose(
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
        $action = UpdateQueryAction(['where' => ['firstname', 'Mildred']], $obj);
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
        $action = UpdateQueryAction(['where' => ['firstname', 'Mildred']], $obj);
        $result = DMLManager(Person::class)->update(...$action->payload()->toArray());
        $this->assertSame(1, $result);
        $p = DMLManager(Person::class)->select($person->getKey());
        $this->assertSame('Welch', $p->lastname);
        $this->assertNotSame('(702) 959-3715', $p->phonenumber);
    }

    public function test_update_query_action_on_filters_using_array()
    {
        $person = $this->createPerson('Mildred', 'Brown', '(702) 959-3715', 40);
        $obj = [
            'lastname' => 'Welch',
            'phonenumber' => '(610) 535-4895',
        ];
        $action = UpdateQueryAction(ModelFiltersHandler(['where' => ['firstname', 'Mildred']]), $obj);
        $result = DMLManager(Person::class)->update(...$action->payload()->toArray());
        $this->assertSame(1, $result);
        $p = DMLManager(Person::class)->select($person->getKey());
        $this->assertSame('Welch', $p->lastname);
        $this->assertSame('(610) 535-4895', $p->phonenumber);
    }

    public function test_delete_query_action_on_filters()
    {
        $person = $this->createPerson('Mildred', 'Brown', '(702) 959-3715', 40);
        $action = DeleteQueryAction(ModelFiltersHandler(['where' => ['firstname', 'Mildred']]));
        $result = DMLManager(Person::class)->delete(...$action->payload()->toArray());
        $this->assertSame(1, $result);
        $p = DMLManager(Person::class)->select($person->getKey());
        $this->assertNull($p);
    }

    public function test_delete_query_action_on_array_query()
    {
        $person = $this->createPerson('Mildred', 'Brown', '(702) 959-3715', 40);
        $action = DeleteQueryAction(['where' => ['firstname', 'Mildred']]);
        $result = DMLManager(Person::class)->delete(...$action->payload()->toArray());
        $this->assertSame(1, $result);
        $p = DMLManager(Person::class)->select($person->getKey());
        $this->assertNull($p);
    }

    public function test_use_dml_query_action_command_function()
    {
        $this->createPerson('Mildred', 'Brown', '(702) 959-3715', 40);
        $command = useDMLQueryActionCommand(DMLManager(Person::class));
        $result = $command(SelectQueryAction(['where' => ['firstname', 'Mildred']]), static function ($result) {
            return $result->first();
        });
        $this->assertSame('Mildred', $result->firstname);
        $this->assertSame('(702) 959-3715', $result->phonenumber);
    }

    public function test_use_dml_query_action_override_hanlder()
    {
        $result = useDMLQueryActionCommand(DMLManager(Person::class), static function ($action) {
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
