<?php

/*
 * This file is part of the AutoTracerBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mindbreak\AutoTracerBundle\Doctrine;

use Doctrine\DBAL\Logging\SQLLogger;
use Mindbreak\AutoTracerBundle\Tracing\Tracer;
use OpenTracing\Scope;

final class DoctrineTracer implements SQLLogger
{
    private const MAX_STRING_LENGTH = 32;
    private const BINARY_DATA_VALUE = '(binary)';

    private Tracer $tracer;
    private ?Scope $scope = null;
    private bool $traceArguments;

    public function __construct(Tracer $tracer, bool $traceArguments = false)
    {
        $this->tracer         = $tracer;
        $this->traceArguments = $traceArguments;
    }

    public function startQuery($sql, ?array $params = null, ?array $types = null): void
    {
        $this->scope = $this->tracer->startSpan($sql);

        if ($this->traceArguments && $this->scope instanceof Scope) {
            $normalizedParams = $this->normalizeParams($params);
            $span             = $this->scope->getSpan();
            foreach ($normalizedParams as $key => $param) {
                $span->setTag("params.$key", $param);
            }
        }
    }

    public function stopQuery(): void
    {
        if ($this->scope !== null) {
            $this->scope->close();
        }
    }

    /**
     * @param array|null $params
     *
     * @return array<array-key, scalar>
     */
    private function normalizeParams(?array $params): array
    {
        if ($params === null) {
            return [];
        }
        foreach ($params as $index => $param) {
            // normalize recursively
            if (\is_array($param)) {
                $params[$index] = "[" . implode(',', $this->normalizeParams($param)) . "]";
                continue;
            }

            if (!\is_string($param)) {
                if (\is_object($param)) {
                    $params[$index] = $param::class;
                }

                continue;
            }

            // non utf-8 strings break json encoding
            if (!preg_match('//u', $param)) {
                $params[$index] = self::BINARY_DATA_VALUE;
                continue;
            }

            // detect if the too long string must be shorten
            if (self::MAX_STRING_LENGTH < mb_strlen($param, 'UTF-8')) {
                $params[$index] = mb_substr($param, 0, self::MAX_STRING_LENGTH - 6, 'UTF-8') . ' [...]';
            }
        }

        /** @var array<array-key, scalar> $params */
        return $params;
    }
}
