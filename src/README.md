# DREWLABS Database/Data handlers package

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
