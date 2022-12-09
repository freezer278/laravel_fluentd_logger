<?php

namespace Vmorozov\LaravelRichLogs\Logs;

use Fluent\Logger\FluentLogger;
use Fluent\Logger\PackerInterface;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Log\LogManager;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger as Monolog;
use Psr\Log\LoggerInterface;

use function class_exists;
use function is_null;
use function strval;

/**
 * FluentLogManager
 */
final class FluentLogManager extends LogManager
{
    /** @var Container */
    protected $app;

    /**
     * @param array<string, mixed> $config
     * @return LoggerInterface
     * @throws BindingResolutionException
     */
    protected function createFluentDriver(array $config): LoggerInterface
    {
        return new Monolog($this->parseChannel($config), [
            $this->prepareHandler(
                $this->createFluentHandler($config)
            ),
        ]);
    }

    /**
     * @param array<string, mixed> $config
     * @return HandlerInterface
     * @throws BindingResolutionException
     */
    private function createFluentHandler(array $config): HandlerInterface
    {
        $configure = $this->app->make('config')['laravel_rich_logs'];
        $fluentHandler = $this->detectHandler($configure);

        $handler = new $fluentHandler(
            new FluentLogger(
                $configure['host'] ?? FluentLogger::DEFAULT_ADDRESS,
                (int)($configure['port'] ?? FluentLogger::DEFAULT_LISTEN_PORT),
                $configure['options'] ?? [],
                $this->detectPacker($configure)
            ),
            $this->app,
            $configure['tagFormat'] ?? null,
            $this->level($config)
        );

        if (isset($configure['processors']) && is_array($configure['processors'])) {
            foreach ($configure['processors'] as $processor) {
                if (is_string($processor) && class_exists($processor)) {
                    $processor = $this->app->make($processor);
                }

                $handler->pushProcessor($processor);
            }
        }

        return $handler;
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return LoggerInterface
     * @throws BindingResolutionException
     */
    public function __invoke(array $config): LoggerInterface
    {
        return $this->createFluentDriver($config);
    }

    /**
     * @return string
     */
    protected function defaultHandler(): string
    {
        return FluentHandler::class;
    }

    /**
     * @param array<string, mixed> $configure
     *
     * @return PackerInterface|null
     * @throws BindingResolutionException
     */
    protected function detectPacker(array $configure): ?PackerInterface
    {
        if (!is_null($configure['packer'])) {
            if (class_exists($configure['packer'])) {
                return $this->app->make($configure['packer']);
            }
        }
        return null;
    }

    /**
     * @param array<string, mixed> $configure
     *
     * @return string
     */
    protected function detectHandler(array $configure): string
    {
        $handler = $configure['handler'] ?? null;
        if (!is_null($handler)) {
            if (class_exists($handler)) {
                return strval($handler);
            }
        }
        return $this->defaultHandler();
    }
}
