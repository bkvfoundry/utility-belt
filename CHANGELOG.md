# Changelog

All notable changes to ```UtilityBelt``` will be noted in this file.

## 2.2.0

### Methods Added
* ArrayUtility::dotExists
* ArrayUtility::dotWrite
* ArrayUtility::dotMutate

### Other Changes
* Extend ArrayUtility::dotRead to handle nulls being passed in as the array
* Improved README including install instructions

## 2.1.0

### Methods Added
* ArrayUtility::first
* ArrayUtility::last
* ArrayUtility::firstKey
* ArrayUtility::lastKey

New methods added to ArrayUtility to make it easier to deal with function call results immediately without first assigning the result to another variable.

## 2.0.0

### Features
* CollectionUtility::keepKeys, ArrayUtility::keepKeys: removal action option added. Keys can now be deleted (default behaviour) or nullified.
* CollectionUtility::removeKeys, ArrayUtility::removeKeys: removal action option added. Keys can now be deleted (default behaviour) or nullified.
* ArrayUtility::map function that supports value, key, array being passed into the callback added.
* ArrayUtility::mapRecursive now passes three arguments to callback.

### Breaking Changes
* CollectionUtility::mapRecursive now passes three arguments (may break on calls to string based callbacks like "trim").
* ArrayUtility::map method signature now explicitly requires an array (brought in line with other method calls).

## 1.0.1

### Features
* Added support for sort/asort 

## 1.0.0

Initial release.
