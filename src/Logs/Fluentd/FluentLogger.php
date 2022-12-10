<?php

namespace Vmorozov\LaravelFluentdLogger\Logs\Fluentd;

class FluentLogger extends \Fluent\Logger\FluentLogger
{
    /**
     * send a message to specified fluentd.
     *
     * @param string $tag
     * @param array  $data
     * @return bool
     *
     * @api
     */
    public function post($tag, array $data)
    {
        $entity = new Entity($tag, $data);
        return $this->postImpl($entity);
    }
}
