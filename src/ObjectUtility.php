<?php

namespace BkvFoundry\UtilityBelt;

/**
 * Class ObjectUtility
 * @package BkvFoundry\UtilityBelt
 */
class ObjectUtility
{
	/**
	 * Cast an object or string to an array
	 * @param \stdClass|array|string $object An object the either extends ArrayObject or provides any of the following
	 *     methods: getArrayCopy toArray getArray getAsArray
	 * @param callable $uncastable_caster (optional) A callback to use to cast a value if it otherwise couldn't be
	 *     cast. If not provided and an unprocessable object is provided an \InvalidArgumentException will be thrown.
	 *     If the callback does not return an array an InvalidArgumentException will be thrown
	 * @return array
	 */
	public static function castToArray($object, callable $uncastable_caster = null)
	{
		if (is_array($object)) {
			return $object;
		}
		if (is_object($object)) {
			if ($object instanceof \ArrayObject) {
				return $object->getArrayCopy();
			}
			$possible_methods = ["toArray", "getArray", "getAsArray", "getArrayCopy"];
			foreach ($possible_methods as $method_name) {
				if (method_exists($object, $method_name)) {
					return (array)call_user_func([$object, $method_name]);
				}
			}

			if ($uncastable_caster) {
				$value = call_user_func($uncastable_caster, $object);
				if (!is_array($value)) {
					throw new \InvalidArgumentException("Invalid callback. Value returned from castToArray must be an array");
				}
				return $value;
			}
			throw new \InvalidArgumentException("Object passed in must either be an array, implement a toArray method which returns an array or extend ArrayObject.");
		}
		return (array)$object;
	}
}