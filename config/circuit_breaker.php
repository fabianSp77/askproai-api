<?php

return [
    /**
     * Number of failures before opening the circuit
     */
    'failure_threshold' => env('CIRCUIT_BREAKER_FAILURE_THRESHOLD', 5),

    /**
     * Number of successful requests in half-open state before closing circuit
     */
    'success_threshold' => env('CIRCUIT_BREAKER_SUCCESS_THRESHOLD', 2),

    /**
     * Timeout in seconds before attempting to half-open the circuit
     */
    'timeout' => env('CIRCUIT_BREAKER_TIMEOUT', 60),

    /**
     * Maximum requests allowed in half-open state
     */
    'half_open_requests' => env('CIRCUIT_BREAKER_HALF_OPEN_REQUESTS', 3),

    /**
     * Service-specific configurations
     */
    'services' => [
        'calcom' => [
            'failure_threshold' => env('CALCOM_CIRCUIT_BREAKER_THRESHOLD', 3),
            'timeout' => env('CALCOM_CIRCUIT_BREAKER_TIMEOUT', 30),
        ],
        'retell' => [
            'failure_threshold' => env('RETELL_CIRCUIT_BREAKER_THRESHOLD', 5),
            'timeout' => env('RETELL_CIRCUIT_BREAKER_TIMEOUT', 60),
        ],
        'stripe' => [
            'failure_threshold' => env('STRIPE_CIRCUIT_BREAKER_THRESHOLD', 10),
            'timeout' => env('STRIPE_CIRCUIT_BREAKER_TIMEOUT', 120),
        ],
    ],
];