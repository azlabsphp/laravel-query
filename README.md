# drewlabs/database package documentation

The current package is under activate developpement and API may change during versions implementation.

## Providers

By default providers are automatically registered when running Laravel application after composer finishes installing the package.

- For Lumen appliation we must manually register the providers in the bootstrap/app.php:

```php
// bootstrap/app.php
// ...
$app->register(\Drewlabs\Packages\Database\ServiceProvider::class);
// ...
```

### Repository class

They repository class provides method for working with database model without exposing Laravel/Eloquent model implementations. It tries to mimic complex query through array of query parameters.

- Creating a repository class:


```php

$repository = new \Drewlabs\Packages\Database\Extensions\IlluminateModelRepository();

// Because php does not provide Generic implementation, we can bind a model to the repository using {setModel()} method
$repository = $repository->setModel(Example::class);

// By default the repository class use Laravel Container to load and inject dependencies. If your application uses a different injector
$repository = new \Drewlabs\Packages\Database\Extensions\IlluminateModelRepository(null, new ContainerClass());

// Binding model at initialization
$repository = new \Drewlabs\Packages\Database\Extensions\IlluminateModelRepository(Example::class);

```

- Get class name binded to the repository:

```php
$modelClass = $repository->getModel();
```

- Binding model attribute parser:

By default the package comes with an attribute parser that is use to set/hydrate model properties from array passed as parameter when creating or updating a model. To override the default parser:

```php

$repository = $repository->bindAttributesParser(new AttributeParserClass());

```
Note: The model attribute parser must implements [Drewlabs\Contracts\Data\DataRepository\Services\IModelAttributesParser] interface and provide method for parsing the array inputs.

- Performing CRUD operations

```php
// Insert new item to the table
/// Syntax:
/// $repository->insert(Array <Data>, bool <ParseInput>, bool <Upsert>, Array <UpsertConditions>);

$repository = new \Drewlabs\Packages\Database\Extensions\IlluminateModelRepository(Example::class);

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
$result = $repository->pushFilter(new CustomFilters())->find([], $columns = ['*']);


/// Pagination
$result = $repository->pushFilter(new CustomFilters())->paginate($item_per_page = 20, $columns = ['*']);
```

- Default query filter

The package comes with a handy query filter class that provide implementation for applying queries to an illuminate model. The default query filter take advantage of PHP dynamic call on object to apply the query params to eloquent model.


```php
/// Using the default query filter without a repository

/// Note: Each key is an eloquent model/Eloquent query builder method 
/// Parameters are passed in the order and the same way they are passed to the model method, but are specified as array
$filter = new \Drewlabs\Packages\Database\Extensions\CustomQueryCriteria([
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
    ]
]);


/// Applying the query to an Eloquent model and call the Builder get() method to retrieve the matching model
$result = $filter->apply(new Example())->get($columns = ['*']);

/// Applying the filter on a repository class
$result = $repository->pushFilter($filter)->find([], $columns = ['*']);
```

Note : Supported method are:

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

## Model

In order to be repository compatible, the model class must implement [Drewlabs\Contracts\Data\ModelInterface] interface. The package comes with a wrapper arrounf Eloquent model class that already implements the interface.

Therefore to make your model compatible with the repository interface, simply extends [Drewlabs\Packages\Database\Extensions\EloquentModel] class.

Example:

```php
/// This example class can serve as template for any model that is due to be compatible with IlluminateModelRepository and IValidator class
namespace App\Models;

use Drewlabs\Core\Validator\Contracts\Validatable;
use Drewlabs\Packages\Database\Extensions\EloquentModel as Model;
use Illuminate\Validation\Rule;

class Example extends Model implements Validatable
{

    /**
     * Model referenced table
     *
     * @var string
     */
    protected $table = "examples";

    /**
     * Unique identifier of table referenced by model
     *
     * @var string
     */
    protected $primaryKey = "id";

    protected $hidden = [];

    protected $appends = [];

    /**
     * List of fillable properties of the current model
     *
     * @var array
     */
    protected $fillable = [
        // ... Add fillable properties
    ];

    /**
     * Model relations definitions
     *
     * @var array
     */
    public $relation_methods = [];

    // View model interface implementations
    // Methods below let the model being elligible for IValidator class

    /**
     * @inheritDoc
     */
    public function rules()
    {
        return array();
    }

    /**
     * @inheritDoc
     */
    public function updateRules()
    {
        return array();
    }

    /**
     * @inheritDoc
     */
    public function messages()
    {
        return array();
    }
}

```

## Client request filters Generator

This method takes a parameter \Illuminate\Http\Request and tries to generate query filters that can be used by the CustomQueryCriteria Class instances.
Request queries can be in form of:

```php
[
    "whereIn" => [
        "column" => $column,
        "match" => $array
    ],
    "where" => [$column, $operator, $condition],
    "where" => [$column, $condition],
    "where" => [
        "match" => [
            "method" => "whereIn"
            "params" => [$column, $condition]
        ]
    ],
    "whereNotIn" => [
        "column" => $column,
        "match" => $array
    ],
    "whereNull" => $column,
    "orWhere" => [$column, $operator, $condition],
    "orWhere" => [
        "match" => [
            "method" => "whereIn"
            "params" => [$column, $condition]
        ]
    ],
    "orderBy" => [
            "order" => $order,
            "by" => $by
    ],
    "whereHas" => [
        "column" => $relation,
        "match" => [
            "method" => "whereIn"
            "params" => [$column, $condition]
        ]
    ],
    "doesntHave" => [
        "column" => $relation,
        "match" => [
            "method" => "whereIn"
            "params" => [$column, $condition]
        ]
    ]
]
```

### Example

```php
// Create the request
$request = new \Illuminate\Http\Request([
    '_query' => [
        'where' => [['label', '<>', 'Hello World!']],
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
        'orderBy' => [
            'column' => 'id',
            'order' => 'DESC'
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
$filters = \drewlabs_databse_parse_client_request_query(new \Drewlabs\Packages\Database\Tests\Stubs\TestModelStub, $request);
```
