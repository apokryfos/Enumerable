<?php

namespace Apokryfos\Helpers;

/**
 * @property-read  string $name
 * @property-read  string $context
 * @property-read  array $parameters
 */
class Event {

    private $eventName;
    private $eventContext;
    private $eventParameters;

    public function __construct($name, $context, ...$parameters) {
        $this->eventName = $name;
    }


    public function __get($name) {
        if ($name == 'name') {
            return $this->eventName;
        }
        if ($name === 'context') {
            return $this->eventContext;
        }
        if ($name === 'parameters') {
            return $this->eventParameters;
        }
    }
}