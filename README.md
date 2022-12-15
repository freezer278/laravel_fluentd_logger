# vmorozov/laravel_fluentd_logger

[![Latest Version on Packagist](https://img.shields.io/packagist/v/vmorozov/laravel_fluentd_logger.svg?style=flat-square)](https://packagist.org/packages/vmorozov/laravel_fluentd_logger)
[![Total Downloads](https://img.shields.io/packagist/dt/vmorozov/laravel_fluentd_logger.svg?style=flat-square)](https://packagist.org/packages/vmorozov/laravel_fluentd_logger)

[comment]: <> ([![GitHub Tests Action Status]&#40;https://img.shields.io/github/workflow/status/vmorozov/laravel_fluentd_logger/run-tests?label=tests&#41;]&#40;https://github.com/vmorozov/laravel_fluentd_logger/actions?query=workflow%3Arun-tests+branch%3Amain&#41;)

[comment]: <> ([![GitHub Code Style Action Status]&#40;https://img.shields.io/github/workflow/status/vmorozov/laravel_fluentd_logger/Fix%20PHP%20code%20style%20issues?label=code%20style&#41;]&#40;https://github.com/vmorozov/laravel_fluentd_logger/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain&#41;)

This package provides ability to use fluentd as log driver. It also add additional logging capabilities such as:
- Request log
- DB Query log
- Queue Jobs log
- Log tracing

## Installation

You can install the package via composer:

```bash
composer require vmorozov/laravel_fluentd_logger
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel_fluentd_logger-config"
```

Add middlewares to `app/Http/Kernel.php`:
```php
protected $middleware = [
    // ... other middlewares here
    \Vmorozov\LaravelFluentdLogger\Middleware\LogRequestMiddleware::class,
    \Vmorozov\LaravelFluentdLogger\Middleware\ContinueTraceMiddleware::class,
];
```

Add fluentd log channel to `config/logging.php`:
```php
// ...some exisiting channels

'fluentd' => [
    'driver' => 'fluentd',
    'level' => env('LOG_LEVEL', 'debug'),
],
```

Add ENV vars with fluentd configs:
```dotenv
FLUENTD_HOST=127.0.0.1
FLUENTD_PORT=24224
```

## Configuration

In config file `laravel_fluentd_logger.php` you can make some adjustments:

- Disable some features
```php
    'features_enabled' => [
        'request_log' => false,
        'db_query_log' => false,
        'queue_log' => false,
    ],
```
- Overwrite default fluentd log handler
```php
    // optionally override \Vmorozov\LaravelFluentdLogger\Logs\FluentHandler class to customize behaviour
    'handler' => SomeCustomHandler::class,
```
- Change log tag format
```php
'tagFormat' => '{{app_name}}.{{level_name}}',
```
- Overwrite some options for fluentd sdk classes
```php
    /** @see https://github.com/fluent/fluent-logger-php/blob/master/src/FluentLogger.php */
    'options' => [],

    /** @see https://github.com/fluent/fluent-logger-php/blob/master/src/PackerInterface.php */
    // specified class name
    'packer' => null,
```

### Fluentd config samples
- simple stdout output:
```
<match your_app_name_from_env.**>
  type stdout
</match>
```
- output to elasticsearch + stdout:
```
# sendlog to the elasticsearch
# the host must match to the elasticsearch
# container service
<match your_app_name_from_env.**>
  @type copy
  <store>
    @type elasticsearch
    host elasticsearch
    port 9200
    logstash_format true
    logstash_prefix fluentd
    logstash_dateformat %Y-%m-%d
    include_tag_key true
    type_name access_log
    tag_key @log_name
    flush_interval 1s
    log_es_400_reason false
  </store>
  <store>
    @type stdout
  </store>
</match>
```
- config for fluentd to accept data using port:
```
# bind fluentd on IP 0.0.0.0
# port 24224
<source>
  @type forward
  port 24224
  bind 0.0.0.0
</source>
```

More info about fluentd configuration can be found in [official fluentd documentation](https://docs.fluentd.org/).

### Monolog processors

You can add processors to the monolog handlers by adding them to the `processors` array within the `laravel_fluentd_logger.php` config.
config/laravel_fluentd_logger.php:
```php
'processors' => [CustomProcessor::class],
```
CustomProcessor.php:
```php
class CustomProcessor
{
    public function __invoke($record)
    {
        $record['extra']['level'] = $record['level_name'];
        return $record;
    }
}
```

## Testing

```bash
composer test
```

## Credits

- [Vladimir Morozov](https://github.com/freezer278)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
