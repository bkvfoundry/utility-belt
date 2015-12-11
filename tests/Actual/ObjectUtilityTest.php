<?php namespace BkvFoundry\UtilityBelt\Tests;

use BkvFoundry\UtilityBelt\ObjectUtility;

class ObjectUtilityTest extends TestCase
{
	public function testThatObjectCanBeCastToArray()
	{
		//Array object
		$object = new \ArrayObject(["a", "b"]);
		$this->assertEquals(ObjectUtility::castToArray($object), ["a", "b"]);

		//Object with method toArray/getAsArray/getArray/getArrayCopy
		$this->assertEquals(ObjectUtility::castToArray(new Castable()), ["a", "b"]);

		//Object with no such method
		try {
			ObjectUtility::castToArray(new Uncastable());
			$this->fail("Expected an invalid argument exception");
		} catch (\InvalidArgumentException $e) {
		}

		//Uncastable and an invalid uncastable result
		try {
			ObjectUtility::castToArray(new Uncastable(), function () {
				return true;
			});
			$this->fail("Expected an invalid argument exception");
		} catch (\InvalidArgumentException $e) {
		}

		//Uncastable and a valid castable result
		$this->assertEquals(["a"], ObjectUtility::castToArray(
			new Uncastable(),
			function ($value) {
				if (!$value instanceof Uncastable) {
					$this->fail("Incorrect value in callback");
				}
				return ["a"];
			}
		));
	}
}

class Castable
{
	public function toArray()
	{
		return ["a", "b"];
	}
}

class Uncastable
{

}