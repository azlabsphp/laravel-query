<?php


function shiftBuilderHasClosure(array $params, $boolean = 'and', $operator = '>=')
{
    $opts = [$operator, 1, $boolean];
    $output = [];
    $callback = null;
    foreach ($params as $value) {
        if (!is_string($value) && is_callable($value)) {
            $callback = $value;
            continue;
        }
        $output[] = $value;
    }
    // We merge the output with the slice from the optional parameters value and append the callback
    // at the end if provided
    $output = [...$output, ...array_slice($opts, count($output) - 1), $callback];
    // We protect the query method against parameters that do not translate what they means, by overriding
    // the operator and the boolean function to use for the query
    $output[1] = $operator;
    $output[3] = $boolean;
    return $output;
}

function println(string $format, ...$args) {
    $format = $format . "\n";
    return printf($format, ...$args);
}

println("[products]");

print_r(shiftBuilderHasClosure(['posts']));

println("[products] + closure");
print_r(shiftBuilderHasClosure(['posts', function($query) {
    return $query->where('name', 'iMac 2019');
}]));

println("[products] + operator");
print_r(shiftBuilderHasClosure(['posts', '<=']));


println("[products] + operator + closure");
print_r(shiftBuilderHasClosure(['posts', '<=', function($query) {
    return $query->where('name', 'iMac 2019');
}]));

println("[products] + operator + 2");
print_r(shiftBuilderHasClosure(['posts', '<=', 2]));


println("[products] + operator + 10 + closure");
print_r(shiftBuilderHasClosure(['posts', '<=', 10, function($query) {
    return $query->where('name', 'iMac 2019');
}]));


println("or -> [products] + operator + 2");
print_r(shiftBuilderHasClosure(['posts', '<=', 2], 'or'));


println("or -> [products] + operator + 10 + closure");
print_r(shiftBuilderHasClosure(['posts', '<=', 10, 'ls', function($query) {
    return $query->where('name', 'iMac 2019');
}], 'or'));

println("or -> [products] + operator + 2 -> overrides(operator)");
print_r(shiftBuilderHasClosure(['posts', '<=', 2], 'or', '>='));


println("or -> [products] + operator + 10 + closure -> overrides(operator)");
print_r(shiftBuilderHasClosure(['posts', '<=', 10, 'ls', function($query) {
    return $query->where('name', 'iMac 2019');
}], 'and', '>='));