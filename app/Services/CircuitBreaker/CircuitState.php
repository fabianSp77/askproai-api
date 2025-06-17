<?php

namespace App\Services\CircuitBreaker;

class CircuitState
{
    const CLOSED = 'closed';      // Normal operation
    const OPEN = 'open';          // Failing, reject requests
    const HALF_OPEN = 'half_open'; // Testing if service recovered
}