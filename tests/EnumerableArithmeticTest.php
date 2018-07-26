<?php
/**
 * Created by PhpStorm.
 * User: yiannis
 * Date: 14/07/18
 * Time: 09:18
 */

namespace Tests\Apokryfos;

use Apokryfos\Enumerable;
use PHPUnit\Framework\Error\Warning;
use PHPUnit\Framework\TestCase;

class EnumerableArithmeticTest extends TestCase {


    /**
     * @dataProvider \Tests\Fixtures\Datasets::sumAndCountDataset
     */
    public function testBasicSumAndCount($input, $sum, $count, $callback = null) {
        $e = new Enumerable($input);
        $result = $e->sumAndCount($callback);
        $this->assertEquals([$sum, $count], array_values($result));

    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::avgDataset
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

}
