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
}
