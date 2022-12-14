<?php

namespace Vmorozov\LaravelFluentdLogger\Logs;

use Exception;
use Fluent\Logger\LoggerInterface;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use LogicException;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Vmorozov\LaravelFluentdLogger\Tracing\TraceStorage;
use function array_key_exists;
use function is_array;
use function preg_match_all;
use function sprintf;
use function str_replace;

/**
 * FluentHandler
 *
 * @phpstan-import-type Level from \Monolog\Logger
 */
class FluentHandler extends AbstractProcessingHandler
{
    protected LoggerInterface $logger;
    private Repository $config;
    private TraceStorage $traceStorage;

    protected string $tagFormat = '{{app_name}}.{{level_name}}';

    /**
     * @param LoggerInterface $logger
     * @param null|string $tagFormat
     * @param int $level
     * @param bool $bubble
     *
     * @phpstan-param Level $level
     */
    public function __construct(
        LoggerInterface $logger,
        Application     $application,
        string          $tagFormat = null,
        int             $level = Logger::DEBUG,
        bool            $bubble = true
    )
    {
        $this->logger = $logger;
        if ($tagFormat !== null) {
            $this->tagFormat = $tagFormat;
        }
        $this->config = $application->make(Repository::class);
        $this->traceStorage = $application->make(TraceStorage::class);
        parent::__construct($level, $bubble);
    }

    /**
     * @param array<string, mixed> $record
     */
    protected function write(array $record): void
    {
        $tag = $this->populateTag($record);
        $this->logger->post(
            $tag,
            [
                '@trace_id' => $this->traceStorage->getTraceId(),
                '@span_id' => $this->traceStorage->getSpanId(),
                '@level' => $record['level_name'],
                '@env' => $record['channel'],
                '@message' => $record['message'],
                '@context' => $this->getContext($record['context']),
                '@extra' => $record['extra'],
                '@host' => (string)gethostname(),
            ]
        );
    }

    /**
     * @param array<string, mixed> $record
     *
     * @return string
     */
    protected function populateTag(array $record): string
    {
        $record['app_name'] = $this->config->get('app.name');
        return $this->processFormat($record, $this->tagFormat);
    }

    /**
     * @param array<string, mixed> $record
     * @param string $tag
     *
     * @return string
     */
    protected function processFormat(array $record, string $tag): string
    {
        if (preg_match_all('/{{(.*?)}}/', $tag, $matches)) {
            foreach ($matches[1] as $match) {
                if (!isset($record[$match])) {
                    throw new LogicException('No such field in the record');
                }
                $tag = str_replace(sprintf('{{%s}}', $match), $record[$match], $tag);
            }
        }

        return $tag;
    }

    /**
     * returns the context
     *
     * @param mixed $context
     *
     * @return mixed
     */
    protected function getContext($context)
    {
        if ($this->contextHasException($context)) {
            return $this->getContextExceptionTrace($context);
        }

        return $context;
    }

    /**
     * Identifies the content type of the given $context
     *
     * @param mixed $context
     *
     * @return bool
     */
    protected function contextHasException($context): bool
    {
        return (
            is_array($context)
            && array_key_exists('exception', $context)
            && $context['exception'] instanceof Exception
        );
    }

    /**
     * Returns the entire exception trace as array
     *
     * @param array<string, mixed> $context
     * @return array
     */
    protected function getContextExceptionTrace(array $context): array
    {
        /** @var Exception $exception */
        $exception = $context['exception'];
        unset($context['exception']);
        return [
            ...$context,
            'message' => $exception->getMessage(),
            'file' => $exception->getFile() . ':' . $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
