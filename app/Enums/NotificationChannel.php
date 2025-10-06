<?php

namespace App\Enums;

/**
 * Notification Channel Enum
 *
 * Central registry for all notification delivery channels.
 * Provides consistent labels, icons, and emojis across the application.
 */
enum NotificationChannel: string
{
    case EMAIL = 'email';
    case SMS = 'sms';
    case WHATSAPP = 'whatsapp';
    case PUSH = 'push';
    case IN_APP = 'in_app';
    case NONE = 'none';

    /**
     * Get human-readable label for the channel
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::EMAIL => 'E-Mail',
            self::SMS => 'SMS',
            self::WHATSAPP => 'WhatsApp',
            self::PUSH => 'Push-Benachrichtigung',
            self::IN_APP => 'In-App',
            self::NONE => 'Kein Fallback',
        };
    }

    /**
     * Get Heroicon for the channel
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::EMAIL => 'heroicon-o-envelope',
            self::SMS => 'heroicon-o-device-mobile',
            self::WHATSAPP => 'heroicon-o-chat-bubble-left-right',
            self::PUSH => 'heroicon-o-bell',
            self::IN_APP => 'heroicon-o-inbox',
            self::NONE => 'heroicon-o-x-circle',
        };
    }

    /**
     * Get emoji for the channel (legacy support)
     */
    public function getEmoji(): string
    {
        return match ($this) {
            self::EMAIL => '📧',
            self::SMS => '📱',
            self::WHATSAPP => '💬',
            self::PUSH => '🔔',
            self::IN_APP => '📬',
            self::NONE => '—',
        };
    }

    /**
     * Get all channels as options array for forms
     */
    public static function getOptions(): array
    {
        return collect(self::cases())
            ->reject(fn ($case) => $case === self::NONE)
            ->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }

    /**
     * Get fallback channel options (includes 'none')
     */
    public static function getFallbackOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }

    /**
     * Get channels for validation rules
     */
    public static function getValues(): array
    {
        return collect(self::cases())
            ->reject(fn ($case) => $case === self::NONE)
            ->pluck('value')
            ->toArray();
    }

    /**
     * Get fallback channel values for validation
     */
    public static function getFallbackValues(): array
    {
        return collect(self::cases())
            ->pluck('value')
            ->toArray();
    }

    /**
     * Try to create from string value
     */
    public static function tryFromValue(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }

        return self::tryFrom($value);
    }

    /**
     * Get formatted label with emoji
     */
    public function getLabelWithEmoji(): string
    {
        return $this->getEmoji() . ' ' . $this->getLabel();
    }

    /**
     * Check if this channel is a real notification channel (not 'none')
     */
    public function isRealChannel(): bool
    {
        return $this !== self::NONE;
    }
}
