<?php

return [

    'host' => env('FLUENTD_HOST', '127.0.0.1'),

    'port' => env('FLUENTD_PORT', 24224),

    /** @see https://github.com/fluent/fluent-logger-php/blob/master/src/FluentLogger.php */
    'options' => [],

    /** @see https://github.com/fluent/fluent-logger-php/blob/master/src/PackerInterface.php */
    // specified class name
    'packer' => null,

    // optionally override \Vmorozov\LaravelFluentdLogger\Logs\FluentHandler class to customize behaviour
    'handler' => null,

    'processors' => [],

    'tagFormat' => '{{app_name}}.{{level_name}}',

    /**
     * Here you can disable some features if you don`t need them
     */
    'features_enabled' => [
        'request_log' => true,
        'db_query_log' => true,
        'queue_log' => true,
    ],
];
