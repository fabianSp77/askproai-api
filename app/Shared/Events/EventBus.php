<?php

namespace App\Shared\Events;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessDomainEvent;
use Exception;

/**
 * Event Bus - Central Event Management
 *
 * Manages publishing events and notifying all registered listeners.
 * Supports both synchronous and asynchronous event processing.
 *
 * FEATURES:
 * - Synchronous event processing for critical path
 * - Asynchronous event processing via queues
 * - Priority-based listener execution order
 * - Error handling and logging
 * - Event tracing via correlation IDs
 * - Listener registration and management
 *
 * ARCHITECTURE:
 * Service → EventBus::publish() → Listeners subscribed to event type
 *           → Sync: Execute immediately
 *           → Async: Queue for background processing
 *
 * @author Architecture Refactoring
 * @date 2025-10-18
 */
class EventBus
{
    /**
     * Registered event listeners
     *
     * Format: ['EventClassName' => [EventListener, ...]]
     */
    private array $listeners = [];

    /**
     * Event history (for debugging/tracing)
     *
     * Format: ['eventId' => DomainEvent]
     */
    private array $eventHistory = [];

    /**
     * Max events to keep in history
     */
    private const MAX_HISTORY = 1000;

    /**
     * Subscribe a listener to an event type
     *
     * @param string $eventClass Event class name (e.g., AppointmentCreatedEvent::class)
     * @param EventListener $listener Listener instance
     */
    public function subscribe(string $eventClass, EventListener $listener): void
    {
        if (!isset($this->listeners[$eventClass])) {
            $this->listeners[$eventClass] = [];
        }

        $this->listeners[$eventClass][] = $listener;

        // Sort by priority (higher first)
        usort($this->listeners[$eventClass], function ($a, $b) {
            return $b->priority() <=> $a->priority();
        });

        Log::debug("📢 EventBus: Listener registered for {$eventClass}", [
            'listener' => get_class($listener),
            'priority' => $listener->priority(),
        ]);
    }

    /**
     * Subscribe multiple listeners at once
     *
     * @param array $subscriptions Format: [EventClass => [ListenerInstance, ...]]
     */
    public function subscribeMany(array $subscriptions): void
    {
        foreach ($subscriptions as $eventClass => $listeners) {
            foreach ((array) $listeners as $listener) {
                $this->subscribe($eventClass, $listener);
            }
        }
    }

    /**
     * Publish an event (notify all listeners)
     *
     * @param DomainEvent $event Event to publish
     * @throws Exception If critical listeners fail
     */
    public function publish(DomainEvent $event): void
    {
        Log::info('📤 EventBus: Publishing event', [
            'eventId' => $event->eventId,
            'eventName' => $event->getEventName(),
            'aggregateId' => $event->aggregateId,
            'correlationId' => $event->correlationId,
        ]);

        // Store in history
        $this->recordEventHistory($event);

        // Get all listeners for this event
        $eventClass = get_class($event);
        $listeners = $this->listeners[$eventClass] ?? [];

        if (empty($listeners)) {
            Log::debug("⚠️ EventBus: No listeners registered for {$eventClass}");
            return;
        }

        // Separate sync and async listeners
        $syncListeners = [];
        $asyncListeners = [];

        foreach ($listeners as $listener) {
            if ($listener->isAsync()) {
                $asyncListeners[] = $listener;
            } else {
                $syncListeners[] = $listener;
            }
        }

        // Execute synchronous listeners immediately
        foreach ($syncListeners as $listener) {
            try {
                $this->executeListener($listener, $event);
            } catch (Exception $e) {
                Log::error('❌ EventBus: Synchronous listener failed', [
                    'eventId' => $event->eventId,
                    'listener' => get_class($listener),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Don't stop other listeners, but log the error
                // In production, might want to throw on critical listeners
            }
        }

        // Queue asynchronous listeners
        foreach ($asyncListeners as $listener) {
            try {
                Queue::push(new ProcessDomainEvent(
                    eventData: $event->toArray(),
                    listenerClass: get_class($listener),
                    correlationId: $event->correlationId,
                ));

                Log::debug('📋 EventBus: Queued async listener', [
                    'eventId' => $event->eventId,
                    'listener' => get_class($listener),
                ]);
            } catch (Exception $e) {
                Log::error('❌ EventBus: Failed to queue async listener', [
                    'eventId' => $event->eventId,
                    'listener' => get_class($listener),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Execute a single listener
     *
     * @throws Exception If listener fails
     */
    private function executeListener(EventListener $listener, DomainEvent $event): void
    {
        $startTime = microtime(true);

        try {
            $listener->handle($event);

            $duration = (microtime(true) - $startTime) * 1000;

            Log::debug('✅ EventBus: Listener executed', [
                'eventId' => $event->eventId,
                'listener' => get_class($listener),
                'duration_ms' => round($duration, 2),
            ]);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Get all listeners for an event
     */
    public function getListeners(string $eventClass): array
    {
        return $this->listeners[$eventClass] ?? [];
    }

    /**
     * Record event in history (for debugging/replay)
     */
    private function recordEventHistory(DomainEvent $event): void
    {
        $this->eventHistory[$event->eventId] = $event;

        // Keep history under control
        if (count($this->eventHistory) > self::MAX_HISTORY) {
            // Keep newest events
            $this->eventHistory = array_slice(
                $this->eventHistory,
                -self::MAX_HISTORY,
                self::MAX_HISTORY,
                true
            );
        }
    }

    /**
     * Get event history (for debugging)
     */
    public function getEventHistory(): array
    {
        return $this->eventHistory;
    }

    /**
     * Clear event history
     */
    public function clearHistory(): void
    {
        $this->eventHistory = [];
        Log::info('📋 EventBus: Event history cleared');
    }

    /**
     * Get bus statistics
     */
    public function getStats(): array
    {
        $totalListeners = array_sum(array_map('count', $this->listeners));

        return [
            'registered_event_types' => count($this->listeners),
            'total_listeners' => $totalListeners,
            'events_in_history' => count($this->eventHistory),
            'listeners_by_event' => array_map('count', $this->listeners),
        ];
    }
}
