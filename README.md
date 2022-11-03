# Database package

This package provides wrapper arround Laravel Illuminate Database ORM. It provides a ModelRepository, a stable API for performing CRUD operations on a database abstracting away various methods for interacting with database.

## Instalation

```json
// ...
"require": {
    // Other dependencies
    "drewlabs/database": "^2.4",
    "drewlabs/contracts": "^2.5",
    "drewlabs/core-helpers": "^2.3",
    "drewlabs/support": "^2.4"
},
// ...
"repositories": [
    //...
    {
        "type": "vcs",
        "url": "git@github.com:liksoft/drewlabs-php-contracts.git"
    },
    {
        "type": "vcs",
        "url": "git@github.com:liksoft/drewlabs-php-core-helpers.git"
    },
    {
        "type": "vcs",
        "url": "git@github.com:liksoft/drewlabs-php-support.git"
    },
    {
        "type": "vcs",
        "url": "git@github.com:liksoft/drewlabs-php-packages-database.git"
    }
]
```

## Usage

### Providers

* Laravel
    When using Laravel framework the service provider is automatically registered.

* Lumen
    For Lumen appliation you must manually register the providers in the bootstrap/app.php:

```php
// bootstrap/app.php
// ...
$app->register(\Drewlabs\Packages\Database\ServiceProvider::class);
// ...
```

### Components

#### The Database Query Language (DMLManager::class)

This component offer a unified language for quering the database using SELECT, CREATE, UPDATE and DELETE METHOD. It heavily makes use of PHP dictionnary a.k.a arrays for various operations.

* Creating instance of the DMLManager

```php
// ...
use function Drewlabs\Packages\Database\Proxy\DMLManager;

// Example class
use App\Models\Example;

// Create a query manager for querying database using Example model
$ql = DMLManager(Example::class);
```

* Create

The method takes in the attributes to insert into the database table as a row.

```php
$ql = DMLManager(Example::class);

// Insert single values to the database
$example = $dmlManager->create([
    'label' => '<EXAMPLE LABEL>',
]);
```

Note:
In it complex form, the create method takes in the attributes to insert and a set of parameters:

```php
$person = $dmlManager->create(
    // Attributes to insert
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
        ],
        'profile' => [
            'url' => 'https://i.picsum.photos/id/733/200/300.jpg?hmac=JYkTVVdGOo8BnLPxu1zWliHFvwXKurY-uTov5YiuX2s'
        ]
    ],
    [
        // Here we tells the query provider to use `profile`, `addresses` keys of `inputs` as relation
        // methods of the model class
        'relations' => [
            'addresses',
            'profile'
        ]
    ]
);
```

The create method also takes in a 3rd parameter `PHP Closure` that can be executed after the create operation

Examples:

```php
$ql = DMLManager(Example::class);

// Insert single values to the database
$example = $dmlManager->create([
    'label' => '<EXAMPLE LABEL>',
], function($value) {
    // Do something with the created value
});
```

* Update

As the `create` method, the `update` method also provides overloaded method implementations for interacting with the database.

```php
$person = $ql->update(1, ['firstname' => 'BENBOSS']);

// Update by ID String
$person = $ql->update("1", ['firstname' => 'AZANDREW']);

// Update using query without mass update
$count = $ql->update(['where' => ['firstname', 'SIDOINE']], ['firstname' => 'AZANDREW']);
```

* Delete

Delete provides an interface for deleting items based on there id or a complex query.

```php
$ql = DMLManager(Person::class);
    // DELETE AN ITEM BY ID
$result = $ql->delete(1);

    // DELET AN ITEM USING COMPLEX QUERY
$result = $ql->delete(['where' => ['firstname', 'SIDOINE']], true);
```

* Select

`select` method of the DMLManger, provides a single method for querying rows in the database using either a complex query array for which each key correspond to a laravel eloquent model methods.

```php
$ql = DMLManager(Person::class);
    $person = $ql->select("1", ['*'], function ($model) {
        return $model->toArray();
    });
    // Select by ID
    $person = $ql->select(1);
    $list = $ql->select(
        [
            'where' => [
                'firstname', 'BENJAMIN'
            ],
            'orWhere' => [
                'lastname', 'AZOMEDOH'
            ]
        ],
        ['firstname', 'addresses']
    );

    // Select using complex where and orWhere queries
    $list = $ql->select(
        [
            'where' => [
                'firstname', 'BENJAMIN'
            ],
            'orWhere' => [
                'lastname', 'AZOMEDOH'
            ]
        ],
        15,
        ['addresses', 'profile'],
        1
    );
```

#### Repository class

They repository class provides method for working with database model without exposing Laravel/Eloquent model implementations. It tries to mimic complex query through array of query parameters.

* Creating a repository class:

```php

$repository = new \Drewlabs\Packages\Database\Extensions\IlluminateModelRepository();

// Because php does not provide Generic implementation, we can bind a model to the repository using {setModel()} method
$repository = $repository->setModel(Example::class);

// By default the repository class use Laravel Container to load and inject dependencies. If your application uses a different injector
$repository = new \Drewlabs\Packages\Database\Extensions\IlluminateModelRepository(null, new ContainerClass());

// Binding model at initialization
$repository = new \Drewlabs\Packages\Database\Extensions\IlluminateModelRepository(Example::class);

```

* Get class name binded to the repository:

```php
$modelClass = $repository->getModel();
```

* Performing CRUD operations

```php

use Drewlabs\Packages\Database\Extensions\IlluminateModelRepository;
use Drewlabs\Packages\Database\EloquentBuilderQueryFilters;
// Insert new item to the table
/// Syntax:
/// $repository->insert(Array <Data>, bool <ParseInput>, bool <Upsert>, Array <UpsertConditions>);

$repository = IlluminateModelRepository(Example::class);

$result = $repository->insert([
    'label' => '...',
    'display_label' => '...'
]);

// Upsert or Insert/Update if exists
$result = $repository->insert([
    'label' => '...',
    'display_label' => '...'
], null, true, [
    'label' => '....'
]);

/// Inserting many values
$result = $repository->insertMany([
    [
    'label' => '...',
    'display_label' => '...'
    ],
    [
    'label' => '...',
    'display_label' => '...'
    ]
]);

/// Update values by id
/// $repository->updateById(int|mixed $id, array $data) -> ModelInterface; Returns the updated model

$result = $repository->updateById($id, array $data);

/// Update based on conditions
/// $repository->updateById(array $values, array $conditions, bool $parse_input = true, bool $mass_uptate = true) -> int; Returns the number of updated items

$result = $repository->updateById($values, $conditions, $parse_input = true, $mass_uptate = true);

/// Delete value by id
/// $repository->deleteById(int|mixed $id) -> int; Returns the 0 if no value is deleted and 1 if value is deleted

$result = $repository->deleteById($id);

/// Delete based on conditions
/// $repository->delete(array $conditions, bool $mass_delete = true) -> int; Returns the number of deleted items

$result = $repository->delete($conditions, $mass_delete = true);

/// Querying for Items in the database

/// Query by id
$result = $repository->findById($id, $columns = ['*']);

// Note the CustomFilter is your user provided class that implement {Drewlabs\Contracts\Data\IModelFilter} interface
$result = $repository->pushFilter(new EloquentBuilderQueryFilters())->find([], $columns = ['*']);

/// Pagination
$result = $repository->pushFilter(new EloquentBuilderQueryFilters())->paginate($item_per_page = 20, $columns = ['*']);
```

#### Query filters

Query filters provides a way to easily apply database select query using PHP array mapping keys of the array of a given method of the framework ORM, and each values to the list of parameters.

* Default query filter

The package comes with a handy query filter class that provide implementation for applying queries to an illuminate model. The default query filter take advantage of PHP dynamic call on object to apply the query params to eloquent model.

```php

use Drewlabs\Packages\Database\Proxy\ModelFiltersHandler;

/// Using the default query filter without a repository

/// Note: Each key is an eloquent model/Eloquent query builder method 
/// Parameters are passed in the order and the same way they are passed to the model method, but are specified as array
$filter = ModelFiltersHandler([
    // Creatigng a where query
    'where' => [
        ['label', '<LabelValue>'],
        ['slug', 'like', '<SlugValue>']
    ],
    'orWhere' => ['id' , '<>', '<IDValue>'],
    'whereHas' => ['relation_name', function($query){
        // ... Provide the subquery
    }],
    // Multiple subqueries at once
    'whereDoesntHave' => [
        ['relation1', function($query){
        // ... Provide the subquery
        }],
        ['relation1', function($query){
        // ... Provide the subquery
        }]
    ],
    // Query date field
    'whereDate' => [$date],

    // Query relation presence
    'has' => 'hasRelation',

    // Query presence of multiple relations
    'has' => ['relation1', 'relation2'],

    // Where in query
    'whereIn' => ['column', [...]],

    // Multiple WherNotIn query

    'whereNotIn' => [
        ['colum1', [...]],
        ['colum2', [...]]
    ],

    // Join query
    'join' => [
        Folder::class,
        [
            'model' => UploadedFile::class,
            'column' => 'folder_id'
        ],
        [
            'model' => Folder::class,
            'column' => 'id'
        ]
    ],
    // Normal laravel join
    'join' => [
        'table1',
        'table2.id', 
        '=', 
        'table1.user_id'
    ]
]);

/// Applying the query to an Eloquent model and call the Builder get() method to retrieve the matching model
$result = $filter->apply(new Example())->get($columns = ['*']);

/// Applying the filter on a repository class
$result = $repository->pushFilter($filter)->find([], $columns = ['*']);
```

#### Client request filters Generator

Request query filter handler is a global function for generating query filters from an HTTP request parameters. It helps/allow developper to query the database table from client application whithout interaction of the backend application.

### Example

```php

// imports
use Drewlabs\Packages\Database\Tests\Stubs\TestModelStub;

// Create the request
$request = new \Illuminate\Http\Request([
    '_query' => [
        'where' => ['label', '<>', 'Hello World!'],
        'orWhere' => [
            [
                'match' => [
                    'method' => 'whereIn',
                    'params' => ['url', ['http://localhost', 'http://liksoft.tg']]
                ]
            ],
            [
                'id', 340
            ]
        ],
        'whereNull' => [
            'column' => 'basepath'
        ],
        'whereIn' => [
            [
                "column" => 'basepath',
                "match" => ['/home/usr/workspace', '/local/usr/Cellar/etc/workspace']
            ],
            [
                'fullpath',
                ['/home/usr/workspace', '/local/usr/Cellar/etc/workspace']
            ]
        ]
    ],
    'label' => 'Are you there ?',
    'id' => 320
]);
$filters = \drewlabs_databse_parse_client_request_query(new TestModelStub, $request);
```

* Here is a list of Eloquent methods supported by the package:

```php
$methods = [
    'where',
    'whereHas',
    'whereDoesntHave',
    'whereDate',
    'has',
    'doesntHave',
    'whereIn',
    'whereNotIn',
    // Added where between query
    'whereBetween',
    'orWhere',
    'orderBy',
    'groupBy',
    'skip',
    'take',
    // Supporting joins queries
    'join',
    'rightJoin',
    'leftJoin',
    // Supporting whereNull and whereNotNull queries
    'whereNull',
    'orWhereNull',
    'whereNotNull',
    'orWhereNotNull'
]
```

#### ORM Model

In order to be repository compatible, the model class must implement [Drewlabs\Contracts\Data\Model] interface.

Example:

```php

namespace App\Models\Patients;

use Drewlabs\Packages\Database\Traits\Model;
use Drewlabs\Contracts\Data\Model\ActiveModel;
use Drewlabs\Contracts\Data\Model\Parseable;
use Drewlabs\Contracts\Data\Model\Relatable;
use Drewlabs\Contracts\Data\Model\GuardedModel;
use Illuminate\Database\Eloquent\Model as EloquentModel;

final class Adresse extends EloquentModel implements ActiveModel, Parseable, Relatable, GuardedModel
{

    use Model;

    /**
     * Model referenced table
     * 
     * @var string
     */
    protected $table = "adresse";

    /**
     * List of values that must be hidden when generating the json output
     * 
     * @var array
     */
    protected $hidden = [];

    /**
     * List of attributes that will be appended to the json output of the model
     * 
     * @var array
     */
    protected $appends = [];

    /**
     * List of fillable properties of the current model
     * 
     * @var array
     */
    protected $fillable = [
        "id",
        "adresse",
        "ville",
    ];

    /**
     * List of fillable properties of the current model
     * 
     * @var array
     */
    public $relation_methods = [];

    /**
     * Table primary key
     * 
     * @var string
     */
    protected $primaryKey = "id";

    /**
     * Bootstrap the model and its traits.
     * 
     *
     * @return void
     */
    protected static function boot()
    {
        # code...
        parent::boot();
    }

}
```

## [v2.5.x] Changes

### SelectQueryAction

`SelectQueryAction` Proxy function provides a typo free function for creating database query action of type `SELECT` .

* SelectQueryAction($id [, array $columns, \Closure $callback])

```php
use function Drewlabs\Packages\Database\Proxy\SelectQueryAction;

// ...

// Example
$action = SelectQueryAction($id) // Creates a select by id query
```

* SelectQueryAction(array $query [, array $columns, \Closure $callback])
* SelectQueryAction(array $query, int $per_page [?int $page = null, array $columns, \Closure $callback])

```php
use function Drewlabs\Packages\Database\Proxy\SelectQueryAction;

//...

// Example
$action = SelectQueryAction([
 'where' => ['id', 12],
 'whereHas' => ['parent', function($q) {
     return $q->where('id', <>, 12);
 }]
]);
```

* SelectQueryAction(FiltersInterface $query [, array $columns, \Closure $callback])
* SelectQueryAction(FiltersInterface $query, int $per_page [?int $page = null, array $columns, \Closure $callback])

```php
use function Drewlabs\Packages\Database\Proxy\ModelFiltersHandler;
use function Drewlabs\Packages\Database\Proxy\SelectQueryAction;

// ...
// Example
$action = SelectQueryAction(ModelFiltersHandler(...));
```

### UpdateQueryAction

`UpdateQueryAction` Proxy function provides a typo free function for creating database query action of type `UPDATE` .

* UpdateQueryAction($id, array|object $attributes [, \Closure $callback])

```php
use function Drewlabs\Packages\Database\Proxy\UpdateQueryAction;

// ...

// Example
$action = UpdateQueryAction($id, ['name' => 'John Doe'])
```

* UpdateQueryAction(array $query, array|object $attributes [, \Closure $callback])

```php
use function Drewlabs\Packages\Database\Proxy\UpdateQueryAction;

// ...

// Example
$action = UpdateQueryAction(ModelFiltersHandler(...), ['name' => 'John Doe'])
```

* UpdateQueryAction(FiltersInterface $query, array|object $attributes [, \Closure $callback])

```php
use function Drewlabs\Packages\Database\Proxy\UpdateQueryAction;
use function Drewlabs\Packages\Database\Proxy\ModelFiltersHandler;

// ...

// Example
$action = UpdateQueryAction(['where' => ['id' => 3]], ['name' => 'John Doe'])
```

### DeleteQueryAction

Creates a `DELETE` type query action using user provided by function user.

* DeleteQueryAction($id [, \Closure $callback])

```php
use function Drewlabs\Packages\Database\Proxy\DeleteQueryAction;

// ...

// Example
$action = DeleteQueryAction($id)
```

* DeleteQueryAction(array $query [, \Closure $callback])

```php
use function Drewlabs\Packages\Database\Proxy\DeleteQueryAction;

// ...

// Example
$action = DeleteQueryAction(['where' => ['id' => 3]])
```

* DeleteQueryAction(FiltersInterface $query [, \Closure $callback])

```php
use function Drewlabs\Packages\Database\Proxy\DeleteQueryAction;
use function Drewlabs\Packages\Database\Proxy\ModelFiltersHandler;

// ...

// Example
$action = DeleteQueryAction(ModelFiltersHandler(...))
```

### CreateQueryAction

Creates a `CREATE` type query action using user provided by function user

* CreateQueryAction(array $attributes [, array $params, \Closure $callback])

```php
use function Drewlabs\Packages\Database\Proxy\CreateQueryAction;

// ...

// Example
$action = CreateQueryAction([...])
```

* CreateQueryAction(object $attributes, [, array $params , \Closure $callback])

```php
use function Drewlabs\Packages\Database\Proxy\CreateQueryAction;

// ...

// Example
$object = new stdClass;
$object->name = 'John Doe';
$object->notes = 67;

$action = CreateQueryAction($object);
```

### useDMLQueryActionCommand

Provides a default action handler command object for database queries.

```php
use function Drewlabs\Packages\Database\Proxy\useDMLQueryActionCommand;
use function Drewlabs\Packages\Database\Proxy\DMLManager;
use function Drewlabs\Packages\Database\Proxy\SelectQueryAction;

$command = useDMLQueryActionCommand(DMLManager(Test::class));
// Executing command with an action using `exec` method
$result = $command->exec(SelectQueryAction($id));

// or Executing command using invokable/high order function interface
$result = $command(SelectQueryAction($id));

// Creatating and executing action in a single line
useDMLQueryActionCommand(DMLManager(Test::class))(SelectQueryAction($id));
```

**Note**
To allow the creator function be more customizable, the function supports
a second parameter that allow developpers to provides their own custom action handler.

```php
use function Drewlabs\Packages\Database\Proxy\useDMLQueryActionCommand;
use function Drewlabs\Packages\Database\Proxy\DMLManager;
use use Drewlabs\Contracts\Support\Actions\Action;

$command = useDMLQueryActionCommand(DMLManager(Test::class), function(Action $action, ?\Closure $callback = null) {
     // Provides custom action handlers
});
```
