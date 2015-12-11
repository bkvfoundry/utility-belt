# UtilityBelt

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/bkvfoundry/utility-belt/master.svg?style=flat-square)](https://travis-ci.org/bkvfoundry/utility-belt)
[![Coverage Status](https://img.shields.io/coveralls/bkvfoundry/utility-belt/master.svg?style=flat-square)](https://coveralls.io/repos/bkvfoundry/utility-belt/badge.svg?branch=master)

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

## CollectionUtility

### filterWhere(Not)(array $items, array $properties)

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
### findWhere(Not)(array $items, array $properties)
Similar to the filter methods but returns the first matching result and optionally
the key where it was found.

### groupByProperty/keyByProperty(array $array, $key)
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

### random(array $array)
Get a random item from an array

### shuffle(array $array)
Shuffle an array and return the result

## ArrayUtility

### dotRead(Properties)

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

### flatten/inflate
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

### isAssoc(array $array)
Quick test to determine whether an array is associative or not.
```
ArrayUtility::isAssoc(["a"=>1,"b"=>2])
true

ArrayUtility::isAssoc(["a","b"])
false
```

### mapRecursive(array $array, callable $callback, $map_arrays = false)
Like array walk recursive but doesn't mutate the array. 

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