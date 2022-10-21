<?php

use Drewlabs\Contracts\Support\Actions\ActionPayload;
use Drewlabs\Contracts\Support\Actions\ActionResult;
use Drewlabs\Core\Helpers\Functional;
use Drewlabs\Packages\Database\Tests\Stubs\Person;
use Drewlabs\Packages\Database\Tests\TestCase;

use function Drewlabs\Packages\Database\Proxy\CreateQueryAction;
use function Drewlabs\Packages\Database\Proxy\DeleteQueryAction;
use function Drewlabs\Packages\Database\Proxy\DMLManager;
use function Drewlabs\Packages\Database\Proxy\ModelFiltersHandler;
use function Drewlabs\Packages\Database\Proxy\SelectQueryAction;
use function Drewlabs\Packages\Database\Proxy\UpdateQueryAction;
use function Drewlabs\Packages\Database\Proxy\useDMLQueryActionCommand;

class ActionQueriesTest extends TestCase
{

    private function createPerson($firstname, $lastname, $phonenumber, $age, $sex = 'F')
    {
        $p = new \stdClass;
        $p->firstname = $firstname;
        $p->lastname = $lastname;
        $p->phonenumber = $phonenumber;
        $p->age = $age;
        $p->sex = $sex;
        $action = CreateQueryAction($p);
        $ql = DMLManager(Person::class);
        return $ql->create(...$action->payload()->toArray());
    }

    public function test_create_action_on_object_parameter()
    {
        $person = $this->createPerson('Madison', 'Soto', '(734) 268-1291', 31);
        $this->assertInstanceOf(Person::class, $person);
        $this->assertNotNull($person->created_at);
        $this->assertEquals('Madison', $person->firstname);
        $this->assertEquals(31, $person->age);
    }

    public function test_select_query_action_on_query_filters()
    {
        $person = $this->createPerson('Stacey', 'Lowe', '(615) 804-1735', 65);
        $query = ModelFiltersHandler(['where' => ['firstname', 'Stacey']]);
        $action = SelectQueryAction($query, Functional::compose(
            function ($result) {
                return $result->first();
            }
        ));
        $result = DMLManager(Person::class)->select(...$action->payload()->toArray());
        $this->assertNotNull($result);
        $this->assertInstanceOf(Person::class, $result);
        $this->assertEquals('Stacey', $person->firstname);
    }

    public function test_select_query_action_on_array_query()
    {
        $person = $this->createPerson('Mildred', 'Brown', '(702) 959-3715', 40);
        $action = SelectQueryAction(['where' => ['firstname', 'Mildred']], Functional::compose(
            function ($result) {
                return $result->first();
            }
        ));
        $result = DMLManager(Person::class)->select(...$action->payload()->toArray());
        $this->assertNotNull($result);
        $this->assertInstanceOf(Person::class, $result);
        $this->assertEquals('Mildred', $result->firstname);
        $this->assertEquals('(702) 959-3715', $result->phonenumber);
    }

    public function test_update_query_action_on_array_query_using_object()
    {
        $person = $this->createPerson('Mildred', 'Brown', '(702) 959-3715', 40);
        $obj = new \stdClass;
        $obj->lastname = 'Welch';
        $obj->phonenumber = '(610) 535-4895';
        $action = UpdateQueryAction(['where' => ['firstname', 'Mildred']], $obj);
        $result = DMLManager(Person::class)->update(...$action->payload()->toArray());
        $this->assertEquals(1, $result);
        $p = DMLManager(Person::class)->select($person->getKey());
        $this->assertEquals('Welch', $p->lastname);
        $this->assertNotEquals('(702) 959-3715', $p->phonenumber);
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
        $this->assertEquals(1, $result);
        $p = DMLManager(Person::class)->select($person->getKey());
        $this->assertEquals('Welch', $p->lastname);
        $this->assertNotEquals('(702) 959-3715', $p->phonenumber);
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
        $this->assertEquals(1, $result);
        $p = DMLManager(Person::class)->select($person->getKey());
        $this->assertEquals('Welch', $p->lastname);
        $this->assertEquals('(610) 535-4895', $p->phonenumber);
    }

    public function test_delete_query_action_on_filters()
    {
        $person = $this->createPerson('Mildred', 'Brown', '(702) 959-3715', 40);
        $action = DeleteQueryAction(ModelFiltersHandler(['where' => ['firstname', 'Mildred']]));
        $result = DMLManager(Person::class)->delete(...$action->payload()->toArray());
        $this->assertEquals(1, $result);
        $p = DMLManager(Person::class)->select($person->getKey());
        $this->assertNull($p);
    }

    public function test_delete_query_action_on_array_query()
    {
        $person = $this->createPerson('Mildred', 'Brown', '(702) 959-3715', 40);
        $action = DeleteQueryAction(['where' => ['firstname', 'Mildred']]);
        $result = DMLManager(Person::class)->delete(...$action->payload()->toArray());
        $this->assertEquals(1, $result);
        $p = DMLManager(Person::class)->select($person->getKey());
        $this->assertNull($p);
    }


    public function test_use_dml_query_action_command_function()
    {
        $this->createPerson('Mildred', 'Brown', '(702) 959-3715', 40);
        $command = useDMLQueryActionCommand(DMLManager(Person::class));
        $result = $command(SelectQueryAction(['where' => ['firstname', 'Mildred']]), function($result) {
            return $result->first();
        });
        $this->assertEquals('Mildred', $result->firstname);
        $this->assertEquals('(702) 959-3715', $result->phonenumber);
    }

    public function test_use_dml_query_action_override_hanlder()
    {
        $result = useDMLQueryActionCommand(DMLManager(Person::class), function($action) {
            return $action->payload();
        })(SelectQueryAction(2));
        $this->assertInstanceOf(ActionResult::class, $result);
        $this->assertEquals($result->value()->toArray(), [2]);
    }
}
