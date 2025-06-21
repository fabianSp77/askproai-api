<?php

namespace App\Services\CircuitBreaker;

class CircuitState
{
    const CLOSED = 'closed';
    const OPEN = 'open';
    const HALF_OPEN = 'half_open';
}