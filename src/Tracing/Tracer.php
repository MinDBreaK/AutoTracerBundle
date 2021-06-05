<?php

/*
 * This file is part of the AutoTracerBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mindbreak\AutoTracerBundle\Tracing;

use Exception;
use Jaeger\Config;
use Jaeger\Jaeger;
use Jaeger\SpanContext;
use Jaeger\Transport\TransportUdp;
use OpenTracing\Reference;
use OpenTracing\Scope;
use OpenTracing\Span;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use OpenTracing\Formats;

class Tracer
{
    private LoggerInterface $logger;
    private Config          $config;
    private Jaeger          $tracer;
    private ?Span           $serverSpan = null;

    public function __construct(LoggerInterface $logger, string $serverName, string $agentHostPort)
    {
        $this->config = Config::getInstance();
        $this->config->gen128bit();

        $transport = new TransportUdp($agentHostPort, 0);
        $this->config->setTransport($transport);

        $this->tracer = $this->config->initTracer($serverName, $agentHostPort);
        $this->logger = $logger;
    }

    public function initialize(Request $request): void
    {
        if ($this->serverSpan !== null) {
            return;
        }

        $spanContext = $this->tracer->extract(
            Formats\TEXT_MAP,
            iterator_to_array($request->headers->getIterator())
        );
        $options = [];

        if ($spanContext instanceof SpanContext) {
            $options[Reference::CHILD_OF] = $spanContext;
        }

        $operation = $request->getMethod() . ' ' . $request->getBasePath() . ' ' . $request->getPathInfo();

        try {
            $this->serverSpan = $this->tracer->startSpan($operation, $options);
        } catch (Exception $e) {
            $this->logger->error('Failed to initialize tracing : ' . $e->getMessage());
        }
    }

    public function finish(): void
    {
        if ($this->serverSpan !== null) {
            $this->serverSpan->finish();
        }

        $this->config->flush();
        $this->serverSpan = null;
    }

    /**
     * @param string $operationName
     * @param array<string, scalar|array>  $tags
     *
     * @return Scope|null
     */
    public function startSpan(string $operationName, array $tags = []): ?Scope
    {
        $lastSpan = $this->tracer->getActiveSpan();
        $options  = [];

        if ($lastSpan === null) {
            $lastSpan = $this->serverSpan;
        }

        if ($lastSpan instanceof Span) {
            $options[Reference::CHILD_OF] = $lastSpan;
        }

        if ($tags !== []) {
            $options['tags'] = $tags;
        }

        try {
            return $this->tracer->startActiveSpan(
                $operationName,
                $options
            );
        } catch (Exception $e) {
            $this->logger->warning("An error occured while starting Span : " . $e->getMessage());
        }

        return null;
    }
}
