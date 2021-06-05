<?php

/*
 * This file is part of the AutoTracerBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mindbreak\AutoTracerBundle\EventDispatcher;

use Mindbreak\AutoTracerBundle\Tracing\Tracer;
use OpenTracing\Scope;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface as ComponentEventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface as ContractsEventDispatcherInterface;

class TracingEventListener implements
    EventDispatcherInterface,
    ContractsEventDispatcherInterface,
    ComponentEventDispatcherInterface
{
    private EventDispatcherInterface $decorated;
    private Tracer                   $tracer;

    public function __construct(EventDispatcherInterface $decorated, Tracer $tracer)
    {
        $this->decorated = $decorated;
        $this->tracer    = $tracer;
    }

    public function dispatch(object $event, ?string $eventName = null): object
    {
        $scope = $this->tracer->startSpan($event::class);

        if ($this->decorated instanceof ComponentEventDispatcherInterface) {
            $result = $this->decorated->dispatch($event, $eventName);
        } else {
            $result = $this->decorated->dispatch($event);
        }

        if ($scope instanceof Scope) {
            if ($event instanceof Event) {
                $scope->getSpan()->setTag('propagation_stop', $event->isPropagationStopped());
            }

            $scope->close();
        }

        return $result;
    }

    public function addListener(string $eventName, $listener, int $priority = 0): void
    {
        if (method_exists($this->decorated, 'addListener')) {
            $this->decorated->addListener($eventName, $listener, $priority);
        }
    }

    public function addSubscriber(EventSubscriberInterface $subscriber): void
    {
        if (method_exists($this->decorated, 'addSubscriber')) {
            $this->decorated->addSubscriber($subscriber);
        }
    }

    public function removeListener(string $eventName, $listener): void
    {
        if (method_exists($this->decorated, 'removeListener')) {
            $this->decorated->removeListener($eventName, $listener);
        }
    }

    public function removeSubscriber(EventSubscriberInterface $subscriber): void
    {
        if (method_exists($this->decorated, 'removeSubscriber')) {
            $this->decorated->removeSubscriber($subscriber);
        }
    }

    public function getListeners(string $eventName = null): array
    {
        if (method_exists($this->decorated, 'getListeners')) {
            $this->decorated->getListeners($eventName);
        }

        return [];
    }

    public function getListenerPriority(string $eventName, $listener)
    {
        if (method_exists($this->decorated, 'getListenerPriority')) {
            $this->decorated->getListenerPriority($eventName, $listener);
        }
    }

    public function hasListeners(string $eventName = null): bool
    {
        if (method_exists($this->decorated, 'hasListeners')) {
            $this->decorated->hasListeners($eventName);
        }

        return false;
    }
}
