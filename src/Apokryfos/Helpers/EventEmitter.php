<?php

namespace Apokryfos\Helpers;


trait EventEmitter {
    protected $listeners;


    public function on($event, $callback) {
        $this->listeners[$event] = $this->listeners[$event] ? : [];
        $this->listeners[$event][] = $callback;
        $index = count($this->listeners[$event])-1;
        return function() use ($event, $index) {
          unset($this->listeners[$event][$index]);
        };
    }

    public function emit($eventName, ...$parameters) {
        $event = new Event($eventName, $this, ...$parameters);

        foreach ($this->listeners[$eventName] ?? [] as $listener) {
            $listener($event);
        }
        return $event;
    }
}