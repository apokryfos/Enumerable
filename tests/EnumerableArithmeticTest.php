<?php

namespace Tests\Apokryfos;

use Apokryfos\Enumerable;
use Apokryfos\Helpers\SelectorHelpers;
use PHPUnit\Framework\Error\Warning;
use PHPUnit\Framework\TestCase;
use Tests\Helpers\TestHelper;

class EnumerableArithmeticTest extends TestCase {


    /**
     * @dataProvider \Tests\Fixtures\Datasets::sumDataset
     * @param $input
     * @param $sum
     * @param null $callback
     */
    public function testBasicSum($input, $sum, $callback = null) {
        $e = new Enumerable($input);
        $result = $e->sum($callback);
        $this->assertEquals($sum, $result);

    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::higherOrderTestCase
     * @param TestHelper[] $testHelpers
     */
    public function testHigherOrderSum(...$testHelpers) {
        $expected = TestHelper::mapAnd('array_sum', $testHelpers);
        $enumerable = new Enumerable($testHelpers);
        $this->assertEquals($expected, $enumerable->sum->test());
    }


    /**
     * @dataProvider \Tests\Fixtures\Datasets::avgDataset
     * @param $input
     * @param $avg
     * @param null $callback
     */
    public function testBasicAverage($input, $avg, $callback = null) {
        $e = new Enumerable($input);
        $e2 = new Enumerable($input);
        $result = $e->average($callback);
        $this->assertEquals($avg, $result);
        $this->assertEquals($result, $e2->avg($callback));
    }


    public function testEdgeAverage() {
        //Division by zero warning.
        // Note that the DivisionByZero error is not thrown because of
        // http://php.net/manual/en/class.divisionbyzeroerror.php#118928
        $this->expectException(Warning::class);
        $e = new Enumerable([]);
        $e->average();
    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomNumbersDataset
     * @param array $numbers
     */
    public function testMax(...$numbers) {
        $e = new Enumerable($numbers);
        $expected = max($numbers);
        $this->assertEquals($expected, $e->max());
    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomComplexDataset
     * @param array $numbers
     */
    public function testMaxAutoproperty(...$numbers) {
        $e = new Enumerable($numbers);
        $expected = max(array_map(function ($num) { return $num["identifier"]; }, $numbers));
        $this->assertEquals($expected, $e->max("identifier"));
    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::higherOrderTestCase
     * @param array $testClasses
     */
    public function testHigherMax(...$testClasses) {
        $e = new Enumerable($testClasses);
        $expected = TestHelper::mapAnd('max', $testClasses);
        $this->assertEquals($expected, $e->max->test());

    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomNumbersDataset
     * @param array $numbers
     */
    public function testMin(...$numbers) {
        $e = new Enumerable($numbers);
        $expected = min($numbers);
        $this->assertEquals($expected, $e->min());
    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::higherOrderTestCase
     * @param array $testClasses
     */
    public function testHigherMin(...$testClasses) {
        $e = new Enumerable($testClasses);
        $expected = TestHelper::mapAnd('min', $testClasses);
        $this->assertEquals($expected, $e->min->test());
    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::medianDataset
     * @param $array
     * @param $expected
     */
    public function testMedianBasic($array, $expected) {
        $e = new Enumerable($array);
        $this->assertEquals($expected, $e->median());
    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomNumbersDataset
     * @param array $numbers
     */
    public function testMedian(...$numbers) {
        $e = new Enumerable($numbers);
        sort($numbers);

        $size = count($numbers);
        $expected = $size%2 == 0? ($numbers[$size/2] + $numbers[$size/2-1])/2 : $numbers[$size/2];
        $this->assertEquals($expected, $e->median());
    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::higherOrderTestCase
     * @param array $testClasses
     */
    public function testHigherMedian(...$testClasses) {
        $e = new Enumerable($testClasses);
        $numbers = TestHelper::mapAnd(SelectorHelpers::identity(), $testClasses);
        sort($numbers);
        $size = count($numbers);
        $expected = $size%2 == 0? ($numbers[$size/2] + $numbers[$size/2-1])/2 : $numbers[$size/2];
        $this->assertEquals($expected, $e->median->test());
    }




}
