<?php

namespace Tests\Helpers;


class TestHelper {
    private $num;
    public function __construct() {
        $this->num = rand();
    }
    public function test() {
        return $this->num;
    }

    public static function mapAnd($callback, ...$array) {
        if (func_num_args() == 2 && is_array($array[0])) {
            $array = $array[0];
        }

        return $callback(array_map(function (TestHelper $v) {
            return $v->test();
        }, $array));
    }

}