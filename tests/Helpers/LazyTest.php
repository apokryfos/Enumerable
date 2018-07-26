<?php

namespace Tests\Helpers;


use Apokryfos\Helpers\Lazy;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Generator;


/**
 * Class LazyTest
 * @covers \Apokryfos\Helpers\Lazy
 */
class LazyTest extends TestCase {

    public function testIsEvaluated() {
        $l = new Lazy('strtoupper', [ 'lower' ]);
        $this->assertFalse($l->isEvaluated());
        $l();
        $this->assertTrue($l->isEvaluated());
    }

    public function testGetValueFunction() {
        $value = Generator::randomValue();
        $expected = strtoupper($value);
        $l = new Lazy('strtoupper', [ $value ]);
        $this->assertEquals($expected, $l());
    }

    public function testBindsToObject() {
        $class = new class {
            private $value;
            public function getValue() { return $this->value; }
        };

        $value = Generator::randomValue();
        $l = new Lazy(function ($v) {
            $this->value = $v;
        }, [ $value ], $class);
        $l();
        $this->assertEquals($value, $class->getValue());
    }

}
