<?php

namespace BkvFoundry\UtilityBelt;

class ArrayUtility
{
	const REMOVAL_ACTION_DELETE  = "delete";
	const REMOVAL_ACTION_NULLIFY = "nullify";

	/**
	 * Flatten an array
	 * @param array $array The array to flatten
	 * @param string $delimiter The string to join array keys with
	 * @param int $levels The number of levels of the array to flatten (from the top).
	 * @return array
	 */
	public static function flatten(array $array, $delimiter = ".", $levels = null)
	{
		if (!isset($levels)) {
			$levels = -1;
		}
		if (!is_array($array) || $levels === 0) {
			return $array;
		}
		$levels--;

		$final_array = [];
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				//check if we have a value and then if the value is an associative array
				if (!$value) {
					$final_array[ $key ] = [];
				} elseif (!self::isAssoc($value)) {
					//If it's not, we want to keep it as an array but flatten out the sub object...
					foreach ($value as $_value) {
						$final_array[ $key ][] = is_array($_value) ? self::flatten($_value, $delimiter, $levels - 1) : $_value;
					}
				} else {
					//If this is an associative array, we want to flatten it out
					$flattened_array = self::flatten($value, $delimiter, $levels);
					foreach ($flattened_array as $_key => $_value) {
						$final_array["{$key}{$delimiter}{$_key}"] = $_value;
					}
				}
			} else {
				$final_array[ $key ] = $value;
			}
		}
		return $final_array;
	}

	/**
	 * Inflate a flat array (opposite of flatten)
	 * @param array $array
	 * @param string $delimiter
	 * @param int $levels The maximum height to inflate the array keys to. Leave as null to inflate fully.
	 * @return array
	 */
	public static function inflate(array $array, $delimiter = ".", $levels = null)
	{
		if (!is_array($array) || $levels === 0) {
			return $array;
		}
		if (!isset($levels)) {
			$levels = 1000;
		}

		$final_array = [];
		foreach ($array as $key => $value) {
			//Let's work out the key parts and the final key
			$key_parts = explode($delimiter, $key);
			if ($levels && count($key_parts) > $levels) {
				$final_key = implode($delimiter, array_slice($key_parts, $levels));
				$key_parts = array_slice($key_parts, 0, $levels);
			} else {
				$final_key = array_pop($key_parts);
			}

			//Now let's construct the base structure
			$parent = &$final_array;
			foreach ($key_parts as $key_part) {
				if (!isset($parent[ $key_part ]) || !is_array($parent[ $key_part ])) {
					$parent[ $key_part ] = [];
				}
				//change ref to newly created sub array before we loop again.
				$parent = &$parent[ $key_part ];
			}

			//set the value on the final key
			if (!array_key_exists($final_key, $parent)) {
				$parent[ $final_key ] = $value;
			}
			unset($parent);
		}

		return $final_array;
	}

	/**
	 * Read a value from a nested array using a single string
	 * @param array $array
	 * @param string $key The read key e.g. "level1.level2.key"
	 * @param mixed $default_value Default value
	 * @return mixed|null The value from the array or null
	 */
	public static function dotRead(array $array, $key, $default_value = null)
	{
		if (is_array($array)) {
			$keys = explode(".", $key);
			$value = $array;
			foreach ($keys as $key) {
				if (!is_array($value) || !isset($value[ $key ])) {
					$value = null;
					break;
				}
				$value = $value[ $key ];
			}
		}

		//Get a default value
		if (!isset($value) && isset($default_value)) {
			return $default_value;
		}

		return $value;
	}

	/**
	 * Dot read multiple properties
	 * @param array $array
	 * @param array $keys Keys to read
	 * @param null $default_value
	 * @return array
	 */
	public static function dotReadProperties(array $array, $keys, $default_value = null)
	{
		$result = [];
		foreach ($keys as $key) {
			$result[ $key ] = self::dotRead($array, $key, $default_value);
		}
		return $result;
	}

	/**
	 * Strips a possible prefix off array keys recursively. e.g. ["__a"=>"value","_b"=>"value","c"=>"value"] becomes
	 * ["a"=>"value","b"=>"value","c"=>"value"]
	 * @param array $array An array of values
	 * @param string $prefix The prefix to strip (will only strip if present). If the prefix appears multiple times at
	 *     the start of the string, every occurrence will be stripped
	 * @return array
	 */
	public static function stripKeyPrefix(array $array, $prefix = "_")
	{
		$result = [];
		foreach ($array as $key => $value) {
			$key = preg_replace("/^({$prefix}+)/", "", $key);
			if (is_array($value)) {
				$result[ $key ] = self::stripKeyPrefix($value, $prefix);
			} else {
				$result[ $key ] = $value;
			}
		}
		return $result;
	}

	/**
	 * Call a method on every value in an array
	 * @param array $array The array to map recursively
	 * @param callable $callback ($value) The callback to call on every non array value (though see next argument)
	 * @param bool $map_arrays True to also map array values.
	 * @return array
	 */
	public static function mapRecursive(array $array, callable $callback, $map_arrays = false)
	{
		foreach ($array as $key => $value) {
			if (is_array($value) && $map_arrays) {
				$value = call_user_func($callback, $value);
			}
			if (is_array($value)) {
				$value = self::mapRecursive($value, $callback, $map_arrays);
			} else {
				$value = call_user_func($callback, $value);
			}
			$array[ $key ] = $value;
		}
		return $array;
	}

	/**
	 * Reduce the array to only the keys specified
	 * @param array $array
	 * @param array $keys The keys to keep
	 * @param string $removal_action One of the removal action constants. Default is to delete keys but you can also
	 *     nullify / clear them instead.
	 * @return array
	 */
	public static function keepKeys(array $array, $keys, $removal_action = self::REMOVAL_ACTION_DELETE)
	{
		//Reuse existing logic
		return current(CollectionUtility::keepKeys([$array], $keys, $removal_action));
	}

	/**
	 * Remove specific keys from the array
	 * @param array $array
	 * @param array $keys The keys to remove
	 * @param string $removal_action One of the removal action constants. Default is to delete keys but you can also
	 *     nullify / clear them instead.
	 * @return array
	 */
	public static function removeKeys(array $array, $keys, $removal_action = self::REMOVAL_ACTION_DELETE)
	{
		//Reuse existing logic
		return current(CollectionUtility::removeKeys([$array], $keys, $removal_action));
	}

	/**
	 * Determines if an array is associative or not.
	 * @param array $array An array to check
	 * @return bool True if associative (i.e. an object), false otherwise
	 */
	public static function isAssoc(array $array)
	{
		return array_keys($array) !== range(0, count($array) - 1);
	}

	/**
	 * Perform an array map that will pass in the value, key and array
	 *
	 * @param \Iterator|array $array
	 * @param callable $callable
	 * @return array
	 */
	public static function map($array, callable $callable)
	{
		if (!$array) {
			return [];
		}
		$return = [];
		foreach ($array as $k => $v) {
			$return[] = call_user_func_array($callable, [$v, $k, $array]);
		}
		return $return;
	}
}