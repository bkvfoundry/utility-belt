<?php namespace BkvFoundry\UtilityBelt\Tests;

use BkvFoundry\UtilityBelt\CollectionUtility;
use Exception;

class CollectionUtilityTest extends TestCase
{
	public function testThatPropertyCanBePlucked()
	{
		$items = [
			["a" => "b"],
			["a" => "c"],
			["d" => "e"],
			null
		];

		$plucked = CollectionUtility::pluck($items, "a");
		$this->assertEquals(["b", "c", null, null], $plucked);
	}

	public function testThatKeysCanBeKeptInMultiDimArray()
	{
		$items = [
			["a" => "b", "c" => "d"],
			["a" => "c", "c" => "e", "d" => "e"],
			null
		];

		$kept_keys = CollectionUtility::keepKeys($items, ["a", "c"]);
		$this->assertEquals([
			["a" => "b", "c" => "d"],
			["a" => "c", "c" => "e"],
			[]
		], $kept_keys);
	}

	public function testThatKeysCanBeRemovedInMultiDimArray()
	{
		$items = [
			["a" => "b", "c" => "d"],
			["a" => "c", "c" => "e", "d" => "e"],
			null
		];

		$kept_keys = CollectionUtility::removeKeys($items, ["a", "c"]);
		$this->assertEquals([
			[],
			["d" => "e"],
			[]
		], $kept_keys);
	}

	public function testThatCollectionCanBeKeyedByItemProperty()
	{
		$items = [
			["a" => "b"],
			["a" => "b", "b" => "c"],
			["c" => "d"],
			["a" => "c"],
		];

		//Test keying by single property
		$keyed_by_property = CollectionUtility::keyByProperty($items, "a");
		$this->assertEquals([
			"b" => ["a" => "b", "b" => "c"],
			"c" => ["a" => "c"]
		], $keyed_by_property);

		//Test keying by multiple properties
		$keyed_by_property = CollectionUtility::keyByProperty($items, ["a", "b"]);
		$this->assertEquals([
			"b.c" => ["a" => "b", "b" => "c"]
		], $keyed_by_property);

		//Test meta response
		$meta = CollectionUtility::keyByProperty($items, "a", true);
		$this->assertEquals([
			"keyed" => [
				"b" => ["a" => "b", "b" => "c"],
				"c" => ["a" => "c"]
			],
			"overwritten" => [
				["a" => "b"]
			],
			"invalid" => [
				["c" => "d"]
			]
		], $meta);
	}

	public function testThatCollectionItemsCanBeGroupedByItemProperty()
	{
		$items = [
			["a" => "b"],
			["a" => "b", "b" => "c"],
			["c" => "d"],
			["a" => "c"],
		];

		//group by single property
		$grouped = CollectionUtility::groupByProperty($items, "a");
		$this->assertEquals([
			"b" => [
				["a" => "b"],
				["a" => "b", "b" => "c"]
			],
			"c" => [
				["a" => "c"]
			],
			"" => [
				["c" => "d"]
			]
		], $grouped);

		//... or properties
		$items[] = ["a" => "b", "b" => "c", "c" => "d"];
		$grouped = CollectionUtility::groupByProperty($items, ["b", "a"]);
		$this->assertEquals([
			".b" => [
				["a" => "b"]
			],
			"c.b" => [
				["a" => "b", "b" => "c"],
				["a" => "b", "b" => "c", "c" => "d"]
			],
			".c" => [
				["a" => "c"]
			],
			"." => [
				["c" => "d"]
			]
		], $grouped);
	}

	public function testThatListCanBeCastToArray()
	{
		$items = [
			"a",
			null,
			["a", "b", "c"],
			new Exception("message"),
			new \ArrayObject(["an array", "of", "values"])
		];
		$cast = CollectionUtility::castToArray($items, function ($value) {
			if ($value instanceof Exception) {
				return [$value->getMessage()];
			}
			return (array)$value;
		});

		$this->assertEquals([
			["a"],
			[],
			["a", "b", "c"],
			["message"],
			["an array", "of", "values"]
		], $cast);
	}

	public function testThatCollectionCanBeFilteredByProperties()
	{
		$items = [
			["a" => 1, "c" => 3],
			["a" => 1, "b" => 2],
			["a" => 1, "b" => 2, "c" => 3],
			["a" => 2, "b" => 2],
			null,
		];

		//Test that invalid match type throws exception
		try {
			CollectionUtility::filterWhere($items, [], "Asdf");
			$this->fail("Expected exception for invalid match type");
		} catch (\InvalidArgumentException $e) {
		}

		//Test single property filtering
		$filtered = CollectionUtility::filterWhere($items, ["a" => 1]);
		$this->assertCount(3, $filtered);

		//Test multi property filtering
		$filtered = CollectionUtility::filterWhere($items, ["a" => 1, "b" => 2]);
		$this->assertCount(2, $filtered);

		//Test "OR" filtering
		$filtered = CollectionUtility::filterWhere($items, [
			["a" => 1, "b" => 2],
			["a" => 2]
		], null);
		$this->assertEquals([
			["a" => 1, "b" => 2], //matches first condition
			["a" => 1, "b" => 2, "c" => 3], //matches first condition
			["a" => 2, "b" => 2] //matches second condition
		], $filtered);

		//Test key preservation for assoc arrays
		$assoc_items = [
			"A" => $items[0],
			"B" => $items[1],
			"C" => $items[2],
			"D" => $items[3]
		];
		$filtered = CollectionUtility::filterWhere($assoc_items, [
			["a" => 1, "b" => 2],
			["a" => 2]
		], null);
		$this->assertEquals([
			"B" => ["a" => 1, "b" => 2], //matches first condition
			"C" => ["a" => 1, "b" => 2, "c" => 3], //matches first condition
			"D" => ["a" => 2, "b" => 2] //matches second condition
		], $filtered);

		//Test forced key preservation for non assoc arrays
		$filtered = CollectionUtility::filterWhere($items, [
			["a" => 1, "b" => 2],
			["a" => 2]
		], null, true);
		$this->assertEquals([
			1 => ["a" => 1, "b" => 2], //matches first condition
			2 => ["a" => 1, "b" => 2, "c" => 3], //matches first condition
			3 => ["a" => 2, "b" => 2] //matches second condition
		], $filtered);

		//Test case sensitivity
		$items = [
			["a" => "B"],
			["a" => "b"],
			["c" => "d"]
		];
		$filtered = CollectionUtility::filterWhere($items, ["a" => "b"], CollectionUtility::MATCH_TYPE_CASE_INSENSITIVE);
		$this->assertCount(2, $filtered);

		$filtered = CollectionUtility::filterWhere($items, ["a" => "b"]);
		$this->assertCount(1, $filtered);

		//Test strict match
		$items = [
			["a" => 1],
			["a" => "1"]
		];
		$filtered = CollectionUtility::filterWhere($items, ["a" => "1"], CollectionUtility::MATCH_TYPE_STRICT);
		$this->assertEquals([["a" => "1"]], $filtered);

		$filtered = CollectionUtility::filterWhere($items, ["a" => "1"]);
		$this->assertCount(2, $filtered);
	}

	public function testThatCollectionCanBeFilteredByPropertiesThatDontMatch()
	{
		//Mostly covered by the filterWhere tests
		$items = [
			["a" => 1],
			["a" => 2]
		];
		$filtered = CollectionUtility::filterWhereNot($items, ["a" => 1]);
		$this->assertEquals([["a" => 2]], $filtered);
	}

	public function testThatFirstItemMatchingPropertiesIsReturnedViaFindFirstWhere()
	{
		//Mostly covered by filterWhere tests
		$items = [
			["a" => 1],
			"B" => ["a" => 2]
		];
		$filtered = CollectionUtility::findFirstWhere($items, ["a" => 1]);
		$this->assertEquals(["a" => 1], $filtered);

		list($key, $value) = CollectionUtility::findFirstWhere($items, ["a" => 2], null, true);
		$this->assertEquals("B", $key);
		$this->assertEquals(["a" => 2], $value);

		//Null on no match
		$filtered = CollectionUtility::findFirstWhere($items, ["a" => 3]);
		$this->assertNull($filtered);
	}

	public function testThatFirstItemNotMatchingPropertiesIsReturnedViaFindNotWhere()
	{
		//Mostly covered by filterWhere tests
		$items = [
			"A" => ["a" => 1],
			["a" => 2]
		];
		$filtered = CollectionUtility::findFirstWhereNot($items, ["a" => 1]);
		$this->assertEquals(["a" => 2], $filtered);

		//Key return
		list($key, $value) = CollectionUtility::findFirstWhereNot($items, ["a" => 2], null, true);
		$this->assertEquals("A", $key);
		$this->assertEquals(["a" => 1], $value);

		//Null on no match
		$filtered = CollectionUtility::findFirstWhereNot($items, [["a" => 1], ["a" => 2]]);
		$this->assertNull($filtered);
	}

	public function testThatFirstItemMatchingTestIsReturnedViaFindFirst()
	{
		$items = [
			["a" => 1],
			["a" => 2]
		];
		$filtered = CollectionUtility::findFirst($items, function ($value) {
			if ($value['a'] == 2) {
				return true;
			}
			return false;
		});
		$this->assertEquals(["a" => 2], $filtered);

		//Ensure null returned if no value found
		$this->assertEquals(null, CollectionUtility::findFirst($items, function ($value) {
			return false;
		}));

		//Ensure key is returend if requested
		$items = [
			"a" => ["a" => 1],
			"B" => ["a" => 2]
		];
		list($key, $value) = CollectionUtility::findFirst($items, function ($value) {
			if ($value['a'] == 2) {
				return true;
			}
			return false;
		}, true);
		$this->assertEquals("B", $key);
		$this->assertEquals(["a" => 2], $value);
	}

	public function testThatCollectionCanBeSortedByNestedProperty(){
		$collection = [
			["string"=>"a", "nested"=>["number"=>1]],
			["string"=>"d", "nested"=>["number"=>3]],
			["string"=>"c", "nested"=>["number"=>3]],
			["string"=>"b", "nested"=>["number"=>2]],
		];

		//Sort ascending number
		$this->assertEquals([
			["string"=>"a", "nested"=>["number"=>1]],
			["string"=>"b", "nested"=>["number"=>2]],
			["string"=>"d", "nested"=>["number"=>3]],
			["string"=>"c", "nested"=>["number"=>3]],
		],CollectionUtility::sort($collection, "nested.number", null, SORT_NUMERIC));

		//Sort ascending string
		$this->assertEquals([
			["string"=>"a", "nested"=>["number"=>1]],
			["string"=>"b", "nested"=>["number"=>2]],
			["string"=>"c", "nested"=>["number"=>3]],
			["string"=>"d", "nested"=>["number"=>3]],
		],CollectionUtility::sort($collection, "string", null, SORT_FLAG_CASE | SORT_STRING));

		//Maintain indexes
		$this->assertEquals([
			0=>["string"=>"a", "nested"=>["number"=>1]],
			3=>["string"=>"b", "nested"=>["number"=>2]],
			2=>["string"=>"c", "nested"=>["number"=>3]],
			1=>["string"=>"d", "nested"=>["number"=>3]],
		],CollectionUtility::asort($collection, "string", null, SORT_FLAG_CASE | SORT_STRING));

		//Sort descending
		$this->assertEquals([
			["string"=>"c", "nested"=>["number"=>3]],
			["string"=>"d", "nested"=>["number"=>3]],
			["string"=>"b", "nested"=>["number"=>2]],
			["string"=>"a", "nested"=>["number"=>1]],
		],CollectionUtility::sort($collection, "nested.number", CollectionUtility::SORT_DIRECTION_DESCENDING));
	}

	public function testThatRandomItemIsReturnedViaRandom()
	{
		//Test that null is returned if an empty array is provdied
		$this->assertEquals(null, CollectionUtility::random([]));

		//Test rest of function
		$items = [
			["a" => 1],
			["a" => 2]
		];
		$attempts = 0;
		$different = false;
		$random_item = null;
		$previous_item = null;
		while ($attempts++ < 50) {
			//just get one entry where last one is not equal to this one
			$random_item = CollectionUtility::random($items);
			if (isset($previous_item) && $previous_item != $random_item) {
				$different = true;
				break;
			}
			$previous_item = $random_item;
		}
		if (!$different) {
			$this->fail("Same item returned every time over 50 tests, probably not random but run test again to be sure");
		}
	}

	public function testThatArrayIsShuffled()
	{
		//Test that null is returned if an empty array is provdied
		$this->assertEquals(null, CollectionUtility::shuffle([]));

		//Test rest of function
		$items = [
			["a" => 1],
			["a" => 2]
		];
		$attempts = 0;
		$different = false;
		while ($attempts++ < 50) {
			$shuffled = CollectionUtility::shuffle($items);
			if ($shuffled != $items) {
				$different = true;
				break;
			}
		}
		if (!$different) {
			$this->fail("Items not shuffled after 50 tests, probably not good shuffling but run test again to be sure");
		}
	}
}