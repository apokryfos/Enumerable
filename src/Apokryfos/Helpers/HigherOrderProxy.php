<?php

namespace Apokryfos\Helpers;


use Apokryfos\Enumerable;

class HigherOrderProxy {

    private $collection;
    private $method;

    public function __construct(Enumerable $collection, $name) {
        $this->collection = $collection;
        $this->method = $name;
    }

    public function __call($name, $arguments) {
        return call_user_func([ $this->collection, $this->method ], function ($value) use ($name, $arguments) {
            return call_user_func_array([$value,$name], $arguments);
        });
    }
}