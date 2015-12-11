<?php

namespace BkvFoundry\UtilityBelt;

/**
 * Class CollectionUtility
 * @package BkvFoundry\UtilityBelt
 * A utility class designed to help deal with arrays where each entry is an array representing an item
 */
class CollectionUtility
{
	const MATCH_TYPE_CASE_INSENSITIVE = "ci";
	const MATCH_TYPE_LOOSE            = "loose";
	const MATCH_TYPE_STRICT           = "strict";

	/**
	 * "Pluck" a single property value from every item in the collection
	 * @param array $collection
	 * @param string $property The name of the property to pluck. This can be a dot separated string
	 * @return array
	 */
	public static function pluck(array $collection, $property)
	{
		return array_map(function ($entry) use ($property) {
			if (!is_array($entry) || !$entry) {
				return null;
			}
			return ArrayUtility::dotRead($entry, $property);
		}, $collection);
	}

	/**
	 * Reduce every item in the collection to only the keys specified
	 * @param array $collection
	 * @param array $keys The keys to keep
	 * @return array
	 */
	public static function keepKeys(array $collection, $keys)
	{
		$keys = array_flip($keys);
		return array_map(function ($item) use ($keys) {
			if (!is_array($item)) {
				return [];
			}
			return array_intersect_key($item, $keys);
		}, $collection);
	}

	/**
	 * Remove specific keys from every item in the collection
	 * @param array $collection
	 * @param array $keys The keys to remove
	 * @return array
	 */
	public static function removeKeys(array $collection, $keys)
	{
		$keys = array_flip($keys);
		return array_map(function ($item) use ($keys) {
			if (!is_array($item)) {
				return [];
			}
			return array_diff_key($item, $keys);
		}, $collection);
	}

	/**
	 * Keys the collection by a particular property. If an entry does not have ALL the given properties being keyed by,
	 * it will be removed from the collection. If two entries contain the same property, the last processed entry will
	 * remain in the collection
	 * @param array $collection
	 * @param string|array $property_to_key_by Either the name of a single property or an array of property names to
	 *     string together to create a key
	 * @param bool $return_meta If true, return format will be:
	 *      [
	 *          "keyed"=>{StandardResult},
	 *          "overwritten"=>[Items that were successfully keyed but subsequently overwritten],
	 *          "invalid"=>[Items that did not have required properties specified]
	 *      ]
	 * @return array A collection keyed by the provided property(s).
	 */
	public static function keyByProperty(array $collection, $property_to_key_by, $return_meta = false)
	{
		$result = [];
		$properties_to_key_by = !is_array($property_to_key_by) ? [$property_to_key_by] : $property_to_key_by;

		$items_missing_properties = [];
		$items_overwritten = [];
		foreach ($collection as $item) {
			$property_values = ArrayUtility::dotReadProperties($item, $properties_to_key_by);
			if (count(array_filter($property_values)) != count($properties_to_key_by)) {
				$items_missing_properties[] = $item;
				continue;
			}
			$key = implode(".", $property_values);
			if (isset($result[ $key ])) {
				$items_overwritten[] = $result[ $key ];
			}
			$result[ $key ] = $item;
		}

		if ($return_meta) {
			return [
				"keyed" => $result,
				"overwritten" => $items_overwritten,
				"invalid" => $items_missing_properties
			];
		}
		return $result;
	}

	/**
	 * Similar to key by property however each entry is an array of matching collection items. If two items had the
	 * same property value, both with appear in the same collection subset. Also items will NOT be removed if they
	 * have missing properties
	 * @param array $collection
	 * @param string|array $property_to_key_by Either the name of a single property or an array of property names to
	 *     string together to create a key
	 * @return array An array keyed by the given properties where each value in the returend array is a collection
	 */
	public static function groupByProperty(array $collection, $property_to_key_by)
	{
		$result = [];
		$properties_to_key_by = !is_array($property_to_key_by) ? [$property_to_key_by] : $property_to_key_by;
		foreach ($collection as $item) {
			$key = implode(".", ArrayUtility::dotReadProperties($item, $properties_to_key_by));
			if (!isset($result[ $key ])) {
				$result[ $key ] = [];
			}
			$result[ $key ][] = $item;
		}
		return $result;
	}

	/**
	 * Cast collection to an array
	 * @param array $collection
	 * @param callable $uncastable_caster Callback to use in event that object can't natively be cast according to the
	 *     base rules
	 * @return array
	 */
	public static function castToArray(array $collection, callable $uncastable_caster = null)
	{
		return array_map(function ($item) use ($uncastable_caster) {
			return ObjectUtility::castToArray($item, $uncastable_caster);
		}, $collection);
	}

	/**
	 * Filters an array of arrays based on whether or not
	 * @param array $collection A collection of arrays. Any values that are not arrays will be automatically filtered
	 *     out.
	 * @param array $property_list An array of properties that must match for the item to be included in the final
	 *     list. Comparison is run using ArrayUtility::dotRead Can also be provided as an array of arrays. If passed in
	 *     this format, each array is treated as part of an "OR" test i.e. if any individual array would pass the
	 *     filter test if submitted on its own, the item is considered valid.
	 * @param string $match_type Any of the available MATCH_TYPE_* constants or null to use default. Affects whether
	 *     value comparisons are performed on exact data types. Default is "loose" which does not perform a type check
	 * @param bool $force_key_preservation Keys are preserved for assoc arrays only by default. Set to true to ALWAYS
	 *     preserve keys
	 * @return array An array of only the matching items. Keys are preserved for associative arrays only.
	 */
	public static function filterWhere(array $collection, array $property_list, $match_type = self::MATCH_TYPE_LOOSE, $force_key_preservation = false)
	{
		if (is_null($match_type)) {
			$match_type = self::MATCH_TYPE_LOOSE;
		}
		if (!in_array($match_type, [self::MATCH_TYPE_LOOSE, self::MATCH_TYPE_STRICT, self::MATCH_TYPE_CASE_INSENSITIVE])) {
			throw new \InvalidArgumentException("'{$match_type}' is not a valid match type. Must be one of loose, strict.");
		}

		//Turn property lists in to array
		$property_lists = !is_array(current($property_list)) ? [$property_list] : $property_list;
		$property_lists = array_map([ArrayUtility::class, "flatten"], $property_lists);

		//Determine if keys should be preserved
		$preserve_keys = $force_key_preservation || ArrayUtility::isAssoc($collection);

		$result = array_filter($collection, function ($entry) use ($property_lists, $match_type) {
			if (!is_array($entry) || !$entry) {
				return false;
			}

			//test all keys and return false if any of them don't match
			foreach ($property_lists as $property_list) {
				$matches = true;
				foreach ($property_list as $key => $expected_value) {
					//Ensure every value matches
					switch ($match_type) {
						case self::MATCH_TYPE_CASE_INSENSITIVE:
							if (strtolower(ArrayUtility::dotRead($entry, $key)) != strtolower($expected_value)) {
								$matches = false;
							}
							break;
						case self::MATCH_TYPE_LOOSE:
							if (ArrayUtility::dotRead($entry, $key) != $expected_value) {
								$matches = false;
							}
							break;
						default:
							if (ArrayUtility::dotRead($entry, $key) !== $expected_value) {
								$matches = false;
							}
							break;
					}
				}
				if ($matches) {
					return true;
				}
			}

			return false;
		});

		if (!$preserve_keys) {
			return array_values($result);
		}
		return $result;
	}

	/**
	 * Opposite of filter where properties match - rejects any items where properties do match
	 * @param array $collection A collection of arrays. Any values that are not arrays will be automatically filtered
	 *     out.
	 * @param array $property_list An array of properties that must NOT match for the item to be included in the final
	 *     list. Comparison is run using ArrayUtility::dotRead
	 * @param string $match_type Any of the available MATCH_TYPE_* constants. Affects whether value comparisons are
	 *     performed on exact data types.
	 * @param bool $force_key_preservation Keys are preserved for assoc arrays only by default. Set to true to ALWAYS
	 *     preserve keys
	 * @return array
	 */
	public static function filterWhereNot(array $collection, array $property_list, $match_type = self::MATCH_TYPE_LOOSE, $force_key_preservation = false)
	{
		$preserve_keys = $force_key_preservation || ArrayUtility::isAssoc($collection);
		$filtered = array_diff_key($collection, self::filterWhere($collection, $property_list, $match_type, true));
		return $preserve_keys ? $filtered : array_values($filtered);
	}

	/**
	 * Find the first element in a collection that matches a filterWherePropertiesMatch call
	 * @param array $collection A collection of arrays or objects that can be cast to arrays (see castObjectToArray).
	 *     Any values that are not arrays will be automatically filtered out.
	 * @param array $property_list An array of properties that must match for the item to be included in the final
	 *     list. Comparison is run using ArrayUtility::dotRead
	 * @param string $match_type Any of the available MATCH_TYPE_* constants. Affects whether value comparisons are
	 *     performed on exact data types.
	 * @param bool $return_key If set to true, a successful result will be returned as an array of [key,value]
	 * @return array|null
	 */
	public static function findFirstWhere(array $collection, array $property_list, $match_type = self::MATCH_TYPE_LOOSE, $return_key = false)
	{
		$filtered_list = self::filterWhere($collection, $property_list, $match_type, $return_key);
		if (!$filtered_list) {
			return null;
		}
		$entry = current($filtered_list);
		if (!$return_key) {
			return $entry;
		}
		return [current(array_keys($filtered_list)), $entry];
	}

	/**
	 * Find the first element in a collection that matches a filterWherePropertiesDoNotMatch call
	 * @param array $collection A collection of arrays. Any values that are not arrays will be automatically filtered
	 *     out.
	 * @param array $property_list An array of properties that must NOT match for the item to be returned. If an item
	 *     that doesn't match is found it will be returned immediately.
	 * @param string $match_type Any of the available MATCH_TYPE_* constants. Affects whether value comparisons are
	 *     performed on exact data types.
	 * @param bool $return_key If set to true, a successful result will be returned as an array of [key,value]
	 * @return array|null
	 */
	public static function findFirstWhereNot(array $collection, array $property_list, $match_type = self::MATCH_TYPE_LOOSE, $return_key = false)
	{
		$filtered_list = self::filterWhereNot($collection, $property_list, $match_type, $return_key);
		if (!$filtered_list) {
			return null;
		}
		$entry = current($filtered_list);
		if (!$return_key) {
			return $entry;
		}
		return [current(array_keys($filtered_list)), $entry];
	}

	/**
	 * Finds and returns the first value in an array that returns true when evaluated by the callback
	 * @param array $array An array of values to be tested
	 * @param callable $callback function($value,$key) - if the callback returns bool true, the value that was tested
	 *     is returned immediately.
	 * @param bool $return_key If set to true, a successful result will be returned as an array of [key,value]
	 * @return mixed|null Null is returned if no value is found.
	 */
	public static function findFirst(array $array, callable $callback, $return_key = false)
	{
		foreach ($array as $key => $value) {
			if ($callback($value, $key) === true) {
				if ($return_key) {
					return [$key, $value];
				}
				return $value;
			}
		}
		return null;
	}

	/**
	 * Returns a random element from an array
	 * @param array $array An array
	 * @return mixed
	 */
	public static function random(array $array)
	{
		if (!$array) {
			return null;
		}

		// This seems inefficient but see Sebmil's comment http://php.net/manual/en/function.array-rand.php
		return current(self::shuffle($array));
	}

	/**
	 * Suffles an array in an immutable fashion. Using shuffle directly will reorder the original array
	 * @param array $array An array to be shuffled
	 * @return array
	 */
	public static function shuffle(array $array)
	{
		if (!$array) {
			return null;
		}
		$shuffled = $array;
		shuffle($shuffled);

		//If reshuffle
		return $shuffled;
	}
}
