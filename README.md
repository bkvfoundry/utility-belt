# UtilityBelt

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/bkvfoundry/utility-belt/master.svg?style=flat-square)](https://travis-ci.org/bkvfoundry/utility-belt)
[![Coverage Status](https://img.shields.io/coveralls/bkvfoundry/utility-belt/master.svg?style=flat-square)](https://coveralls.io/repos/bkvfoundry/utility-belt/badge.svg?branch=master)

**UPDATE!: UtilityBelt has a new home at Rex Labs**

- [Github Repository](https://github.com/rexlabsio/utility-belt-php)
- [Composer Package](https://packagist.org/packages/rexlabs/utility-belt)

**Please update your projects to use the new package as it will no longer be maintained under the bkvfoundry repository**

Every time we start a new php project that requires even a small amount of data
wrangling, we find ourselves re-writing the same helper functions over and over again.

This package provides some key functions that make it easier to perform
common tasks like:
* finding and filtering values in collections of arrays
* grouping values in collections by properties
* extracting values from deeply nested associative arrays
* recursive array mapping
* determining if an array is associative
* and more...

These functions are all provided without pulling in any heavy framework
dependencies or third party packages.

## Installation

Install the latest version with

```
$ composer require bkvfoundry/utility-belt
```

## CollectionUtility

### filterWhere / filterWhereNot
*(array $items, array $properties)*

Some extremely common functionality involves filtering collections (arrays of rows)
 based on a property list. The code required for this via array_filter tends to become
fairly bloated and makes the purpose of the underlying code harder to understand. These
functions provide a simpler way to perform these kinds of filters.

All comparisons below can be run as case insensitive or strict checks.
```php
$items = [
    [
        "name"=>"john",
        "age"=>18
    ],
    [
        "name"=>"mary",
        "age"=>19
    ],
    [
        "name"=>"william",
        "age"=>18,
        "dog"=>[
            "name"=>"betty"
        ]
    ]
];

CollectionUtility::filterWhere($items,["age"=>18])
// [["name"=>"john",...], ["name"=>"william",...]];

CollectionUtility::filterWhereNot($items,["age"=>18])
// [["name"=>"mary",...]]

CollectionUtility::filterWhere($items,["dog.name"=>"betty"])
// [["name"=>"william",...]]

CollectionUtility::filterWhere($items,[["dog.name"=>"betty"],["name"=>"john"]]);
// [["name"=>"william",...], ["name"=>"john"]]
```
### findWhere / findWhereNot
*(array $items, array $properties)*

Similar to the filter methods but returns the first matching result and optionally
the key where it was found.

### groupByProperty / keyByProperty
*(array $array, $key)*

Re-organises rows in a collection under the values of one or more properties.

The difference between the two methods is that key by property allows for only
a single row per property in the result while grouping will return an array
of collections.

```php
$items = [
    ["name"=>"john","dog"=>["name"=>"william"]],
    ["name"=>"frank","dog"=>["name"=>"william"]],
    ["name"=>"dodd","dog"=>["name"=>"bruce"]],
];

CollectionUtility::keyByProperty($items,"dog.name")
[
   "william"=>["name"=>"frank","dog"=>["name"=>"william"]],
   "bruce"=>["name"=>"dodd","dog"=>["name"=>"bruce"]],
]

CollectionUtility::keyByProperty($items,["name","dog.name"])
[
    "john.william"=>["name"=>"john","dog"=>["name"=>"william"]],
    "frank.william"=>["name"=>"frank","dog"=>["name"=>"william"]],
    "dodd.bruce"=>["name"=>"dodd","dog"=>["name"=>"bruce"]],
]

CollectionUtility::groupByProperty($items,"dog.name")
[
    "william"=>[
        ["name"=>"john","dog"=>["name"=>"william"]],
        ["name"=>"frank","dog"=>["name"=>"william"]],
    ],
    "bruce"=>[
        ["name"=>"dodd","dog"=>["name"=>"bruce"]],
    ]
]
```

### sort / asort
*(array $array, $property, $sort_direction = self::SORT_DIRECTION_ASCENDING, $sort_flags=SORT_REGULAR)*

Sort a collection using a property or nested property
```
$items = [
    ["string"=>"a", "nested"=>["number"=>1]],
    ["string"=>"d", "nested"=>["number"=>3]],
    ["string"=>"c", "nested"=>["number"=>3]],
    ["string"=>"b", "nested"=>["number"=>2]],
];

CollectionUtility::sort($items, "nested.number", CollectionUtility::SORT_DIRECTION_DESCENDING, SORT_NUMERIC);
[
    ["string"=>"c", "nested"=>["number"=>3]],
    ["string"=>"d", "nested"=>["number"=>3]],
    ["string"=>"b", "nested"=>["number"=>2]],
    ["string"=>"a", "nested"=>["number"=>1]]
]
```

### keepKeys
*(array $array, array $keys, $removal_action = CollectionUtility::REMOVAL_ACTION_DELETE)*

Remove (or nullify) all keys in each collection item except for those specified in the keys arg
```
$items = [
    ["animal"=>"dog","name"=>"John","weather"=>"mild"],
    ["animal"=>"cat","name"=>"William"]
];

CollectionUtility::keepKeys($items, ["animal","weather"], CollectionUtility::REMOVAL_ACTION_DELETE);
[
    ["animal"=>"dog", "weather"=>"mild"],
    ["animal"=>"cat"]
]

CollectionUtility::keepKeys($items, ["animal","weather"], CollectionUtility::REMOVAL_ACTION_NULLIFY);
[
    ["animal"=>"dog", "name"=>null, ""weather"=>"mild"],
    ["animal"=>"cat", "name"=>null]
]
```

### removeKeys
*(array $array, array $keys, $removal_action = CollectionUtility::REMOVAL_ACTION_DELETE)*

Remove (or nullify) any keys provided in the $keys argument in each collection item.
```
$items = [
    ["animal"=>"dog","name"=>"John","weather"=>"mild"],
    ["animal"=>"cat","name"=>"William"]
];

CollectionUtility::removeKeys($items, ["animal","weather"], CollectionUtility::REMOVAL_ACTION_DELETE);
[
    ["name"=>"John"],
    ["name"=>"William"]
]
```

### random
*(array $array)*

Get a random item from an array

### shuffle
*(array $array)*

Shuffle an array and return the result

## ArrayUtility

### dotRead
*(array $array, $property, $default_value=null)*

```php
$array = [
    "a"=>[
        "very"=>[
            "deep"=>[
                "hole"=>"with a prize at the bottom"
            ]
        ]
    ]
]

ArrayUtility::dotRead($array, "a.very.shallow.hole", "no prize!")
"no prize!"

ArrayUtility::dotRead($array, "a.very.deep.hole")
"with a prize at the bottom"
```

### dotWrite
*(array $array, $property, $value)*

Original array:

```php
$array = [
    "a"=>[
        "very"=>[
            "deep"=>[
                "hole"=>"with a prize at the bottom"
            ]
        ]
    ],
    "i"=>[
        "like"=>"candy"
    ]
]
```

Return a new array containing the updated property:

```php
$array = ArrayUtility::dotWrite($array, "a.very.deep.hole", "no prize!");
$array - ArrayUtility::dotWrite($array, "i.like", ["carrots","broccoli"]);
```

Array is now:

```php
$array = [
    "a"=>[
        "very"=>[
            "deep"=>[
                "hole"=>"no prize!"
            ]
        ]
    ],
    "i"=>[
        "like"=>["carrots","broccoli"]
    ]
]
```

Set new properties:

```php
$array = ArrayUtility::dotWrite($array, "a.very.shallow.hole", "that may contain a prize!");
$array = ArrayUtility::dotWrite($array, "i.also.like", "blue skies");
```

Array is now:

```php
$array = [
    "a"=>[
        "very"=>[
            "deep"=>[
                "hole"=>"no prize!"
            ]
        ]
    ],
    "a"=>[
        "very"=>[
            "shallow"=>[
                "hole"=>"that may contain a prize!"
            ]
        ]
    ],
    "i"=>[
        "like"=>["carrots","broccoli"]
        "also"=>[
            "like"=>"blue skies"
        ]
    ]
]
```

### dotMutate
*(array $array, $property, $value)*

This method is similar to `dotWrite()` except the array is passed by reference so that property changes directly
mutate the given array.

Original array:

```php
$array = [
    "a"=>[
        "very"=>[
            "deep"=>[
                "hole"=>"with a prize at the bottom"
            ]
        ]
    ],
    "i"=>[
        "like"=>"candy"
    ]
]
```

Mutate the array with the updated property:

```php
ArrayUtility::dotMutate($array, "a.very.deep.hole", "no prize!");
ArrayUtility::dotMutate($array, "i.like", ["carrots","broccoli"]);
```

Array is now:

```php
$array = [
    "a"=>[
        "very"=>[
            "deep"=>[
                "hole"=>"no prize!"
            ]
        ]
    ],
    "i"=>[
        "like"=>["carrots","broccoli"]
    ]
]
```

Set new properties:

```php
ArrayUtility::dotMutate($array, "a.very.shallow.hole", "that may contain a prize!");
ArrayUtility::dotMutate($array, "i.also.like", "blue skies");
```

Array is now:

```php
$array = [
    "a"=>[
        "very"=>[
            "deep"=>[
                "hole"=>"no prize!"
            ]
        ]
    ],
    "a"=>[
        "very"=>[
            "shallow"=>[
                "hole"=>"that may contain a prize!"
            ]
        ]
    ],
    "i"=>[
        "like"=>["carrots","broccoli"]
        "also"=>[
            "like"=>"blue skies"
        ]
    ]
]
```

### flatten / inflate
*(array $array)*

```
$items = [
    "a"=>[
        "name"=>"jimmy",
        "fruits"=>["apple","banana"]
    ],
    "b"=>[
        "name"=>"william",
        "age"=>18,
        "dog"=>[
            "name"=>"betty"
        ]
    ]
]

ArrayUtility::flatten($items);
[
    "a.name"=>"jimmy",
    "a.fruits"=>["apple","banana"], //by default these are not flattened
    "b.name"=>"william",
    "b.age"=>18,
    "b.dog.name"=>"betty"
]

ArrayUtility::inflate($flattenedArray);
[
    "a"=>[
        "name"=>"jimmy",
        "fruits"=>["apple","banana"]
    ],
    "b"=>[
        "name"=>"william",
        "age"=>18,
        "dog"=>[
            "name"=>"betty"
        ]
    ]
]
```

### isAssoc
*(array $array)*

Quick test to determine whether an array is associative or not.
```
ArrayUtility::isAssoc(["a"=>1,"b"=>2])
true

ArrayUtility::isAssoc(["a","b"])
false
```

### keepKeys
*(array $array, array $keys, ArrayUtility::REMOVAL_ACTION_DELETE)*

Same as CollectionUtility::keepKeys but operates on a basic array instead of a collection of arrays.

### removeKeys
*(array $array, array $keys, ArrayUtility::REMOVAL_ACTION_DELETE)*

Same as CollectionUtility::removeKeys but operates on a basic array instead of a collection of arrays.

### mapRecursive
*(array $array, callable $callback, $map_arrays = false)*

Like array walk recursive but doesn't mutate the array. Also, callback receives three arguments: $value, $key, $array

### map
*(array $array, callable $callback)*

Like array_map but the callback receives three arguments: $value, $key, $array

### first
*(array $array)*

Returns the first element from an array. More useful than reset() because it can handle the result of a function.

### last
*(array $array)*

Returns the last element from an array. More useful than end() because it can handle the result of a function.

### firstKey
*(array $array)*

Returns the key of the first element in an array.

### lastKey
*(array $array)*

Returns the key of the last element in an array.

## Install
Install `UtilityBelt` using Composer.

```bash
$ composer require bkvfoundry/utility-belt
```

## Testing
`UtilityBelt` has a [PHPUnit](https://phpunit.de) test suite. To run the tests, run the following command from the project folder.

``` bash
$ composer test
```

## Credits
- [Alex Babkov](https://github.com/ababkov)
- [All Contributors](https://github.com/bkvfoundry/utility-belt/graphs/contributors)

## License
The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
