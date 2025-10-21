<?php

namespace App\Shared\Events;

/**
 * Event Listener Interface
 *
 * Services implement this interface to subscribe to domain events.
 * When an event is published, all registered listeners are notified.
 *
 * PATTERN: Observer/Pub-Sub Pattern
 *
 * USAGE:
 * ```php
 * class SendConfirmationEmailListener implements EventListener {
 *     public static function subscribesTo(): array {
 *         return [AppointmentCreatedEvent::class];
 *     }
 *
 *     public function handle(DomainEvent $event): void {
 *         if ($event instanceof AppointmentCreatedEvent) {
 *             Mail::to($event->customerEmail)
 *                 ->send(new AppointmentConfirmationMail($event));
 *         }
 *     }
 * }
 *
 * // Register in ServiceProvider
 * app(EventBus::class)->subscribe(
 *     AppointmentCreatedEvent::class,
 *     new SendConfirmationEmailListener()
 * );
 * ```
 *
 * @author Architecture Refactoring
 * @date 2025-10-18
 */
interface EventListener
{
    /**
     * Get list of event classes this listener subscribes to
     *
     * @return array<string> Array of event class names (e.g., [AppointmentCreatedEvent::class])
     */
    public static function subscribesTo(): array;

    /**
     * Handle the event
     *
     * @param DomainEvent $event The domain event to handle
     * @throws \Exception Any exception thrown will be logged and not block other listeners
     */
    public function handle(DomainEvent $event): void;

    /**
     * Get priority for execution order (higher = earlier)
     *
     * Default: 0
     * Critical listeners: 100+
     * Logging listeners: -100
     *
     * @return int
     */
    public function priority(): int;

    /**
     * Whether this listener should run asynchronously
     *
     * @return bool True to queue in background, false to execute immediately
     */
    public function isAsync(): bool;
}
