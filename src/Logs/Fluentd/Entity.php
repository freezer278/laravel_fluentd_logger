<?php

namespace Vmorozov\LaravelFluentdLogger\Logs\Fluentd;

class Entity extends \Fluent\Logger\Entity
{
    public function __construct($tag, $data, $time = null)
    {
        @parent::__construct($tag, $data, $time);
        $this->time = microtime(true);
    }
}
