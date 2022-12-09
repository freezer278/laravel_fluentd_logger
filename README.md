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

## Testing

```bash
composer test
```

## Credits

- [Vladimir Morozov](https://github.com/freezer278)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
