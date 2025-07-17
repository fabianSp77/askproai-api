## MCP Beispiele

### 📅 Appointment Management

#### Termin buchen
```php
// Via MCP Service
$mcp = app(MCPOrchestrator::class);
$result = $mcp->executeForTenant(
    tenantId: 1,
    service: 'appointment',
    tool: 'create_appointment',
    arguments: [
        'customer_phone' => '+49 123 456789',
        'service_id' => 'massage-60min',
        'date' => '2025-01-20',
        'time' => '14:00',
        'branch_id' => 1
    ]
);

// Via Artisan
php artisan mcp:execute appointment create_appointment \
  --customer_phone="+49 123 456789" \
  --service_id="massage-60min" \
  --date="2025-01-20" \
  --time="14:00"
```

#### Verfügbarkeit prüfen
```php
$availability = $mcp->executeForTenant(
    tenantId: 1,
    service: 'calcom',
    tool: 'check_availability',
    arguments: [
        'date' => '2025-01-20',
        'service_id' => 'massage-60min',
        'branch_id' => 1
    ]
);

// Ergebnis
[
    'available_slots' => [
        '09:00', '10:00', '14:00', '15:00'
    ],
    'booked_slots' => [
        '11:00', '12:00', '13:00'
    ]
]
```

### 📞 Call Processing

#### Anrufe importieren
```php
// Letzte 50 Anrufe importieren
$calls = $mcp->executeForTenant(
    tenantId: 1,
    service: 'retell',
    tool: 'fetch_calls',
    arguments: [
        'limit' => 50,
        'order' => 'desc'
    ]
);

// Mit Datum-Filter
$calls = $mcp->executeForTenant(
    tenantId: 1,
    service: 'retell',
    tool: 'fetch_calls',
    arguments: [
        'start_date' => '2025-01-01',
        'end_date' => '2025-01-20'
    ]
);
```

#### Call-Daten analysieren
```php
// Via Database MCP
$stats = $mcp->executeForTenant(
    tenantId: 1,
    service: 'database',
    tool: 'execute_query',
    arguments: [
        'query' => "
            SELECT 
                COUNT(*) as total_calls,
                AVG(duration_minutes) as avg_duration,
                COUNT(DISTINCT from_number) as unique_callers
            FROM calls 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        "
    ]
);
```

### 👥 Customer Management

#### Kunde suchen
```php
// Nach Telefonnummer
$customer = $mcp->executeForTenant(
    tenantId: 1,
    service: 'customer',
    tool: 'search_customers',
    arguments: [
        'query' => '+49 123 456789'
    ]
);

// Nach Name
$customers = $mcp->executeForTenant(
    tenantId: 1,
    service: 'customer',
    tool: 'search_customers',
    arguments: [
        'query' => 'Schmidt',
        'limit' => 10
    ]
);
```

#### Kundenhistorie
```php
$history = $mcp->executeForTenant(
    tenantId: 1,
    service: 'customer',
    tool: 'get_customer_history',
    arguments: [
        'customer_id' => 123
    ]
);

// Ergebnis
[
    'appointments' => [...],
    'calls' => [...],
    'invoices' => [...],
    'notes' => [...]
]
```

### 💳 Payment Processing

#### Rechnung erstellen
```php
$invoice = $mcp->executeForTenant(
    tenantId: 1,
    service: 'stripe',
    tool: 'create_invoice',
    arguments: [
        'customer_id' => 'cus_123456',
        'items' => [
            [
                'description' => 'Massage 60 Min',
                'amount' => 8000, // in Cents
                'quantity' => 1
            ]
        ],
        'due_date' => '2025-02-01'
    ]
);
```

### 🧠 Memory Bank

#### Kontext speichern
```php
// Aufgabe speichern
$memory = $mcp->executeForTenant(
    tenantId: 1,
    service: 'memory_bank',
    tool: 'create_memory',
    arguments: [
        'key' => 'deploy_feature_x',
        'value' => [
            'status' => 'in_progress',
            'started' => '2025-01-19',
            'assignee' => 'developer@askproai.de'
        ],
        'memory_type' => 'task',
        'tags' => ['deployment', 'feature-x']
    ]
);

// Session speichern
$session = $mcp->executeForTenant(
    tenantId: 1,
    service: 'memory_bank',
    tool: 'save_session',
    arguments: [
        'session_id' => 'user_123_session',
        'data' => [
            'current_page' => 'appointments',
            'filters' => ['status' => 'confirmed']
        ]
    ]
);
```

### 🔄 Batch Operations

#### Mehrere Termine buchen
```php
$appointments = [
    ['phone' => '+49 111', 'date' => '2025-01-20', 'time' => '10:00'],
    ['phone' => '+49 222', 'date' => '2025-01-20', 'time' => '11:00'],
    ['phone' => '+49 333', 'date' => '2025-01-20', 'time' => '14:00'],
];

foreach ($appointments as $apt) {
    $result = $mcp->executeForTenant(
        tenantId: 1,
        service: 'appointment',
        tool: 'create_appointment',
        arguments: array_merge($apt, [
            'service_id' => 'consultation',
            'branch_id' => 1
        ])
    );
    
    if (!$result['success']) {
        Log::error('Failed to book appointment', $result);
    }
}
```

### 🛡️ Error Handling

```php
try {
    $result = $mcp->executeForTenant(
        tenantId: 1,
        service: 'appointment',
        tool: 'create_appointment',
        arguments: $data
    );
    
    if (!$result['success']) {
        // Handle business logic error
        return response()->json([
            'error' => $result['error']
        ], 400);
    }
    
    // Success
    return response()->json($result['data']);
    
} catch (RateLimitExceededException $e) {
    // Handle rate limit
    return response()->json([
        'error' => 'Too many requests',
        'retry_after' => $e->retryAfter
    ], 429);
    
} catch (CircuitBreakerOpenException $e) {
    // Handle circuit breaker
    return response()->json([
        'error' => 'Service temporarily unavailable'
    ], 503);
}
```

### 🔍 Komplexe Queries

#### Multi-Service Orchestration
```php
// 1. Kunde finden oder erstellen
$customerResult = $mcp->executeForTenant(
    tenantId: 1,
    service: 'customer',
    tool: 'find_or_create',
    arguments: [
        'phone' => '+49 123 456789',
        'name' => 'Max Mustermann'
    ]
);

// 2. Verfügbarkeit prüfen
$availabilityResult = $mcp->executeForTenant(
    tenantId: 1,
    service: 'calcom',
    tool: 'check_availability',
    arguments: [
        'date' => '2025-01-20',
        'service_id' => 'massage-60min'
    ]
);

// 3. Termin buchen
if (!empty($availabilityResult['data']['available_slots'])) {
    $appointmentResult = $mcp->executeForTenant(
        tenantId: 1,
        service: 'appointment',
        tool: 'create_appointment',
        arguments: [
            'customer_id' => $customerResult['data']['id'],
            'service_id' => 'massage-60min',
            'date' => '2025-01-20',
            'time' => $availabilityResult['data']['available_slots'][0]
        ]
    );
}

// 4. Rechnung erstellen
if ($appointmentResult['success']) {
    $invoiceResult = $mcp->executeForTenant(
        tenantId: 1,
        service: 'stripe',
        tool: 'create_invoice',
        arguments: [
            'customer_id' => $customerResult['data']['stripe_id'],
            'appointment_id' => $appointmentResult['data']['id']
        ]
    );
}
```

### 📊 Reporting

#### Custom Report erstellen
```php
$report = $mcp->executeForTenant(
    tenantId: 1,
    service: 'database',
    tool: 'execute_query',
    arguments: [
        'query' => "
            SELECT 
                s.name as service_name,
                COUNT(a.id) as appointment_count,
                SUM(s.price) as total_revenue,
                AVG(s.duration) as avg_duration
            FROM appointments a
            JOIN services s ON a.service_id = s.id
            WHERE a.scheduled_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY s.id
            ORDER BY appointment_count DESC
        "
    ]
);

// Format für Dashboard
$dashboardData = array_map(function($row) {
    return [
        'service' => $row['service_name'],
        'bookings' => $row['appointment_count'],
        'revenue' => number_format($row['total_revenue'] / 100, 2) . ' €',
        'avg_duration' => $row['avg_duration'] . ' Min'
    ];
}, $report['data']);
```

### 🔐 Advanced Security

#### Mit Custom Headers
```php
$mcp->executeForTenant(
    tenantId: 1,
    service: 'webhook',
    tool: 'send_notification',
    arguments: [...],
    headers: [
        'X-Custom-Auth' => 'secret-token',
        'X-Request-ID' => Str::uuid()
    ]
);
```

#### Mit Timeout Override
```php
$result = $mcp->executeForTenant(
    tenantId: 1,
    service: 'retell',
    tool: 'process_long_call',
    arguments: [...],
    options: [
        'timeout' => 120, // 2 Minuten statt default 30s
        'retry_on_timeout' => false
    ]
);
```