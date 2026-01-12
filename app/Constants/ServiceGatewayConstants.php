<?php

namespace App\Constants;

/**
 * Service Gateway Constants
 *
 * Zentrale Definition aller Magic Numbers für bessere Wartbarkeit.
 * Änderungen hier wirken sich auf alle Service Gateway Jobs aus.
 */
final class ServiceGatewayConstants
{
    // === Delivery Job ===
    public const DELIVERY_MAX_ATTEMPTS = 3;
    public const DELIVERY_JOB_TIMEOUT_SECONDS = 60;
    public const DELIVERY_BACKOFF_DELAYS = [60, 120, 300];
    public const DELIVERY_ENRICHMENT_GATE_RETRY_SECONDS = 30;
    public const DELIVERY_ENRICHMENT_DEFAULT_TIMEOUT = 180;

    // === Enrichment Job ===
    public const ENRICHMENT_MAX_ATTEMPTS = 5;
    public const ENRICHMENT_BACKOFF_SECONDS = 30;
    public const ENRICHMENT_UNIQUE_FOR_SECONDS = 300;

    // === Audio Processing Job ===
    public const AUDIO_MAX_ATTEMPTS = 3;
    public const AUDIO_BACKOFF_SECONDS = 60;
    public const AUDIO_UNIQUE_FOR_SECONDS = 3600;

    // === Webhooks ===
    public const WEBHOOK_TIMEOUT_SECONDS = 30;
    public const SLACK_ALERT_TIMEOUT_SECONDS = 5;

    // === Logging ===
    public const LOG_ERROR_MAX_LENGTH = 500;
    public const LOG_RESPONSE_MAX_LENGTH = 500;
    public const SLACK_ERROR_MAX_LENGTH = 200;

    // === Customer Matching ===
    public const MATCH_CONFIDENCE_PHONE = 100;
    public const MATCH_CONFIDENCE_EMAIL = 85;
    public const MATCH_CONFIDENCE_NAME = 70;
    public const MATCH_CONFIDENCE_UNKNOWN = 0;
    public const MATCH_NAME_MIN_LENGTH = 3;

    // === Source/Channel Constants ===
    // ServiceNow-compatible case sources
    public const SOURCE_VOICE = 'voice';
    public const SOURCE_EMAIL = 'email';
    public const SOURCE_WEB = 'web';
    public const SOURCE_API = 'api';
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_CHAT = 'chat';
    public const SOURCE_CALLBACK = 'callback';
    public const SOURCE_WALK_IN = 'walk-in';

    /**
     * All valid source values for validation.
     */
    public const SOURCES = [
        self::SOURCE_VOICE,
        self::SOURCE_EMAIL,
        self::SOURCE_WEB,
        self::SOURCE_API,
        self::SOURCE_MANUAL,
        self::SOURCE_CHAT,
        self::SOURCE_CALLBACK,
        self::SOURCE_WALK_IN,
    ];

    /**
     * German labels for UI display.
     */
    public const SOURCE_LABELS = [
        self::SOURCE_VOICE => 'Telefonanruf',
        self::SOURCE_EMAIL => 'E-Mail',
        self::SOURCE_WEB => 'Web-Formular',
        self::SOURCE_API => 'API',
        self::SOURCE_MANUAL => 'Manuell erfasst',
        self::SOURCE_CHAT => 'Chat',
        self::SOURCE_CALLBACK => 'Rückruf-Wunsch',
        self::SOURCE_WALK_IN => 'Vor Ort',
    ];

    /**
     * ServiceNow contact_type mapping.
     * Maps internal source to ServiceNow's contact_type field values.
     */
    public const SERVICENOW_CONTACT_TYPE_MAP = [
        self::SOURCE_VOICE => 'phone',
        self::SOURCE_EMAIL => 'email',
        self::SOURCE_WEB => 'self-service',
        self::SOURCE_API => 'self-service',
        self::SOURCE_MANUAL => 'walk-in',
        self::SOURCE_CHAT => 'virtual_agent',
        self::SOURCE_CALLBACK => 'phone',
        self::SOURCE_WALK_IN => 'walk-in',
    ];

    /**
     * Icons for email templates and UI (text-based for email compatibility).
     */
    public const SOURCE_ICONS = [
        self::SOURCE_VOICE => '📞',
        self::SOURCE_EMAIL => '📧',
        self::SOURCE_WEB => '🌐',
        self::SOURCE_API => '⚙️',
        self::SOURCE_MANUAL => '✏️',
        self::SOURCE_CHAT => '💬',
        self::SOURCE_CALLBACK => '↩️',
        self::SOURCE_WALK_IN => '🚶',
    ];

    /**
     * Colors for badges in Filament UI and emails.
     */
    public const SOURCE_COLORS = [
        self::SOURCE_VOICE => 'primary',    // Blue
        self::SOURCE_EMAIL => 'warning',    // Yellow/Orange
        self::SOURCE_WEB => 'success',      // Green
        self::SOURCE_API => 'purple',       // Purple
        self::SOURCE_MANUAL => 'gray',      // Gray
        self::SOURCE_CHAT => 'danger',      // Red
        self::SOURCE_CALLBACK => 'orange',  // Orange
        self::SOURCE_WALK_IN => 'lime',     // Lime green
    ];

    /**
     * Get source label with fallback.
     */
    public static function getSourceLabel(?string $source): string
    {
        return self::SOURCE_LABELS[$source] ?? $source ?? 'Unbekannt';
    }

    /**
     * Get ServiceNow contact_type for a source.
     */
    public static function getServiceNowContactType(?string $source): string
    {
        return self::SERVICENOW_CONTACT_TYPE_MAP[$source] ?? 'phone';
    }
}
