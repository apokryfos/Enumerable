<?php

namespace Apokryfos\Helpers;


final class Lazy {

    private $callback;
    private $arguments;
    private $invoked;
    private $invocationResult;

    public function __construct(callable $callback, array $arguments = [], $object = null) {
        $this->callback = \Closure::fromCallable($callback);
        if ($object) {
            $this->callback = $this->callback->bindTo($object, $object);
        }
        $this->arguments = $arguments;
        $this->invoked = false;
    }

    public function __invoke() {
        return $this->getValue();
    }

    public function isEvaluated() {
        return $this->invoked === true;
    }

    public function getValue() {
        if (!$this->invoked) {
            $this->invocationResult = call_user_func_array($this->callback, $this->arguments);
            $this->invoked = true;
        }
        return $this->invocationResult;
    }

}