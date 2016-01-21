<?php namespace BkvFoundry\UtilityBelt\Tests;

use BkvFoundry\UtilityBelt\ArrayUtility;

class ArrayUtilityTest extends TestCase
{
    public function testThatArrayIsFlattened()
    {
        //setup source array
        $array = [
            "a" => [
                "aa" => "aa value",
                "bb" => [
                    "aaa" => "aaa value"
                ],
                "cc" => [],
                "dd" => [1, 2, ["a" => ["b" => "c"]]]
            ]
        ];

        //Don't flatten at all
        $result = ArrayUtility::flatten($array, ".", 0);
        $this->assertEquals($array, $result, "Array must not flatten when level is set to 0");

        //Flatten to a certain number of levels
        $result = ArrayUtility::flatten($array, ".", 1);
        $this->assertEquals(["aaa" => "aaa value"], $result['a.bb'], "Array must only flatten by one level");

        //Fully flatten
        $result = ArrayUtility::flatten($array);
        $this->assertEquals("aaa value", $result["a.bb.aaa"], "Array must fully flatten");

        //Full test
        $result = ArrayUtility::flatten($array);
        $this->assertEquals([
            "a.aa" => "aa value",
            "a.bb.aaa" => "aaa value",
            "a.cc" => [],
            "a.dd" => [1, 2, ["a.b" => "c"]]
        ], $result);
    }

    public function testThatArrayInflates()
    {
        //setup source array
        $array = [
            "a.aa" => "aa value",
            "a.bb.aaa" => "aaa value"
        ];

        //Don't inflate at all
        $result = ArrayUtility::inflate($array, ".", 0);
        $this->assertEquals($array, $result, "Array must not inflate when level is set to 0");

        //Inflate to a certain number of levels
        $result = ArrayUtility::inflate($array, ".", 1);
        $this->assertEquals([
            "a" => [
                "aa" => "aa value",
                "bb.aaa" => "aaa value"
            ]
        ], $result, "Array must only inflate by one level");

        //Fully inflate
        $result = ArrayUtility::inflate($array);
        $this->assertEquals([
            "a" => [
                "aa" => "aa value",
                "bb" => [
                    "aaa" => "aaa value"
                ]
            ]
        ], $result, "Array must fully inflate");
    }

    public function testThatPrefixesAreStripped()
    {
        $array = [
            "__a" => "value",
            "_b" => [
                "nested" => "value",
                "_2nd" => "value"
            ]
        ];
        $expected_output = [
            "a" => "value",
            "b" => [
                "nested" => "value",
                "2nd" => "value"
            ]
        ];

        $this->assertEquals($expected_output, ArrayUtility::stripKeyPrefix($array));
    }

    public function testThatADeepPropertyCanBeRead()
    {
        //setup source array
        $array = [
            "a" => [
                "aa" => "aa value",
                "bb" => [
                    "aaa" => "aaa value"
                ]
            ]
        ];

        //try some existing value dot reads
        $this->assertEquals("aa value", ArrayUtility::dotRead($array, "a.aa"));
        $this->assertEquals("aaa value", ArrayUtility::dotRead($array, "a.bb.aaa"));
        $this->assertEquals(["aaa" => "aaa value"], ArrayUtility::dotRead($array, "a.bb"));

        //check default values
        $this->assertEquals("default value", ArrayUtility::dotRead($array, "c.cc.ccc", "default value"));
    }

    public function testThatAssociativeArrayCanBeDetected()
    {
        $assoc_array = [
            "a" => "b",
            "c" => "d"
        ];
        $non_assoc_array = [
            "a",
            "b"
        ];
        $another_assoc_array = [
            0 => "a",
            2 => "b"
        ];

        //Asset true
        $this->assertTrue(ArrayUtility::isAssoc($assoc_array));
        $this->assertTrue(ArrayUtility::isAssoc($another_assoc_array));
        $this->assertFalse(ArrayUtility::isAssoc($non_assoc_array));
    }

    public function testThatArrayMapsRecursively()
    {
        $array = [
            "a" => " b",
            "c" => [
                " d",
                "e"
            ]
        ];
        $array2 = $array;

        //Ensure map is applied recursively
        $this->assertEquals(["a" => "b", "c" => ["d", "e"]], ArrayUtility::mapRecursive($array, "trim"),
            "Trim not run recursively");

        //Ensure array doesn't mutate
        $this->assertEquals($array, $array2, "Array has mutated");

        //Check that arrays can be mapped too
        $this->assertEquals(["a" => "b", "c" => ["d", "e", "f"]], ArrayUtility::mapRecursive($array, function ($value) {
            if (is_array($value)) {
                return ["d", "e", "f "];
            }
            return trim($value);
        }, true));
    }

    public function testThatMultiplePropertiesCanBeDotRead()
    {
        $array = [
            "a" => "b",
            "c" => [
                "d" => [
                    "e" => "f",
                    "g" => [
                        "h" => "i"
                    ]
                ]
            ]
        ];

        $result = ArrayUtility::dotReadProperties($array, [
            "a",
            "missing",
            "c.d.e",
            "c.d.g"
        ], "no");
        $this->assertEquals([
            "a" => "b",
            "missing" => "no",
            "c.d.e" => "f",
            "c.d.g" => ["h" => "i"]
        ], $result);
    }

    public function testMapCallbackIsFired()
    {
        $arr = [
            'one' => 'foo',
        ];

        $mock = $this->getMock('stdClass', array('myCallBack'));
        $mock->expects($this->once())
            ->method('myCallBack')
            ->will($this->returnValue('test'));

        ArrayUtility::map($arr, [$mock, 'myCallBack']);
    }

    public function testMapFunctionReceivesCorrectParams()
    {
        $arr = [
            'one' => 'foo',
        ];

        ArrayUtility::map($arr, function($value, $key, $arr){
            $this->assertEquals('foo', $value);
            $this->assertEquals('one', $key);
            $this->assertEquals(['one'=>'foo'], $arr);
        });
    }
}