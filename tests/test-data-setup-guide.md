# Test Data Setup Guide für Business Portal

## 🎯 Übersicht

Dieses Dokument beschreibt, wie Testdaten für das Business Portal eingerichtet werden, um realistische Testszenarien durchführen zu können.

---

## 📋 Vorbereitung

### 1. Datenbank-Backup
```bash
# Backup der aktuellen Datenbank erstellen
mysqldump -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db > backup_$(date +%Y%m%d_%H%M%S).sql

# Test-Datenbank erstellen (optional)
mysql -u root -p'V9LGz2tdR5gpDQz' -e "CREATE DATABASE askproai_test;"
mysql -u root -p'V9LGz2tdR5gpDQz' askproai_test < backup_$(date +%Y%m%d_%H%M%S).sql
```

### 2. Environment Setup
```bash
# Test-Environment vorbereiten
cp .env .env.backup
cp .env.testing .env

# Horizon für Tests starten
php artisan horizon
```

---

## 🏢 Test-Unternehmen einrichten

### Basis-Unternehmen
```php
// Test-Unternehmen via Tinker erstellen
php artisan tinker

$company = \App\Models\Company::create([
    'name' => 'Test GmbH',
    'domain' => 'test-gmbh',
    'email' => 'test@example.com',
    'phone' => '+49 30 12345678',
    'address' => 'Teststraße 123',
    'city' => 'Berlin',
    'postal_code' => '10115',
    'country' => 'DE',
    'timezone' => 'Europe/Berlin',
    'language' => 'de',
    'status' => 'active',
    'settings' => [
        'business_hours' => [
            'monday' => ['09:00', '18:00'],
            'tuesday' => ['09:00', '18:00'],
            'wednesday' => ['09:00', '18:00'],
            'thursday' => ['09:00', '18:00'],
            'friday' => ['09:00', '16:00'],
            'saturday' => 'closed',
            'sunday' => 'closed'
        ],
        'features' => [
            'appointments' => true,
            'billing' => true,
            'team_management' => true,
            'api_access' => true
        ]
    ]
]);
```

### Filialen erstellen
```php
// Hauptfiliale
$branch1 = \App\Models\Branch::create([
    'company_id' => $company->id,
    'name' => 'Hauptfiliale Berlin',
    'slug' => 'berlin-mitte',
    'phone' => '+49 30 12345678',
    'email' => 'berlin@test-gmbh.de',
    'address' => 'Unter den Linden 1',
    'city' => 'Berlin',
    'postal_code' => '10117',
    'is_active' => true,
    'settings' => [
        'capacity' => 10,
        'booking_buffer' => 15,
        'max_advance_booking' => 90
    ]
]);

// Zweigstelle
$branch2 = \App\Models\Branch::create([
    'company_id' => $company->id,
    'name' => 'Filiale Hamburg',
    'slug' => 'hamburg',
    'phone' => '+49 40 87654321',
    'email' => 'hamburg@test-gmbh.de',
    'address' => 'Jungfernstieg 10',
    'city' => 'Hamburg',
    'postal_code' => '20354',
    'is_active' => true
]);
```

---

## 👥 Test-Benutzer erstellen

### Admin-Benutzer
```php
$admin = \App\Models\User::create([
    'name' => 'Test Admin',
    'email' => 'admin@test-gmbh.de',
    'password' => bcrypt('Test123!'),
    'company_id' => $company->id,
    'role' => 'admin',
    'is_active' => true,
    'email_verified_at' => now()
]);

// Berechtigungen zuweisen
$admin->assignRole('company_admin');
$admin->givePermissionTo(['manage_company', 'manage_team', 'view_billing']);
```

### Mitarbeiter-Benutzer
```php
// Manager
$manager = \App\Models\User::create([
    'name' => 'Maria Manager',
    'email' => 'manager@test-gmbh.de',
    'password' => bcrypt('Test123!'),
    'company_id' => $company->id,
    'branch_id' => $branch1->id,
    'role' => 'manager',
    'is_active' => true,
    'email_verified_at' => now()
]);

// Mitarbeiter
$staff = \App\Models\User::create([
    'name' => 'Stefan Staff',
    'email' => 'staff@test-gmbh.de',
    'password' => bcrypt('Test123!'),
    'company_id' => $company->id,
    'branch_id' => $branch1->id,
    'role' => 'staff',
    'is_active' => true,
    'email_verified_at' => now()
]);

// Service-Mitarbeiter
$service = \App\Models\Staff::create([
    'company_id' => $company->id,
    'branch_id' => $branch1->id,
    'user_id' => $staff->id,
    'first_name' => 'Stefan',
    'last_name' => 'Staff',
    'email' => 'stefan@test-gmbh.de',
    'phone' => '+49 30 11111111',
    'is_active' => true,
    'can_book_appointments' => true
]);
```

---

## 🛎️ Services einrichten

```php
// Basis-Services
$services = [
    [
        'name' => 'Beratungsgespräch',
        'duration' => 30,
        'price' => 50.00,
        'buffer_time' => 10,
        'description' => 'Persönliches Beratungsgespräch'
    ],
    [
        'name' => 'Premium Beratung',
        'duration' => 60,
        'price' => 120.00,
        'buffer_time' => 15,
        'description' => 'Ausführliche Premium-Beratung'
    ],
    [
        'name' => 'Quick Check',
        'duration' => 15,
        'price' => 25.00,
        'buffer_time' => 5,
        'description' => 'Kurzer Status-Check'
    ]
];

foreach ($services as $serviceData) {
    $service = \App\Models\Service::create([
        'company_id' => $company->id,
        'name' => $serviceData['name'],
        'duration' => $serviceData['duration'],
        'price' => $serviceData['price'],
        'buffer_time' => $serviceData['buffer_time'],
        'description' => $serviceData['description'],
        'is_active' => true
    ]);
    
    // Service mit Mitarbeiter verknüpfen
    $service->staff()->attach($staff->id, [
        'is_available' => true,
        'custom_duration' => null,
        'custom_price' => null
    ]);
}
```

---

## 👥 Test-Kunden erstellen

```php
// Kunden-Generator
$faker = \Faker\Factory::create('de_DE');

for ($i = 1; $i <= 50; $i++) {
    $customer = \App\Models\Customer::create([
        'company_id' => $company->id,
        'branch_id' => $faker->randomElement([$branch1->id, $branch2->id]),
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
        'email' => $faker->unique()->safeEmail,
        'phone' => $faker->phoneNumber,
        'date_of_birth' => $faker->dateTimeBetween('-70 years', '-18 years'),
        'address' => $faker->streetAddress,
        'city' => $faker->city,
        'postal_code' => $faker->postcode,
        'notes' => $faker->optional()->sentence,
        'tags' => $faker->randomElements(['VIP', 'Stammkunde', 'Neukunde', 'Newsletter'], 2),
        'preferences' => [
            'reminder' => $faker->randomElement(['email', 'sms', 'both', 'none']),
            'language' => 'de',
            'newsletter' => $faker->boolean(70)
        ],
        'created_at' => $faker->dateTimeBetween('-1 year', 'now')
    ]);
}

// VIP-Kunden
$vipCustomer = \App\Models\Customer::create([
    'company_id' => $company->id,
    'branch_id' => $branch1->id,
    'first_name' => 'Max',
    'last_name' => 'Mustermann',
    'email' => 'max.mustermann@example.com',
    'phone' => '+49 30 22222222',
    'tags' => ['VIP', 'Stammkunde'],
    'notes' => 'Wichtiger Geschäftskunde - bevorzugte Behandlung'
]);
```

---

## 📞 Test-Anrufe generieren

```php
// Anruf-Status Varianten
$callStatuses = ['completed', 'no_answer', 'busy', 'failed', 'voicemail'];
$callTypes = ['inbound', 'outbound'];

for ($i = 1; $i <= 100; $i++) {
    $customer = \App\Models\Customer::inRandomOrder()->first();
    $duration = $faker->numberBetween(30, 600); // 30 Sekunden bis 10 Minuten
    
    $call = \App\Models\Call::create([
        'company_id' => $company->id,
        'branch_id' => $faker->randomElement([$branch1->id, $branch2->id]),
        'customer_id' => $customer->id,
        'retell_call_id' => 'test_call_' . Str::uuid(),
        'phone_number' => $customer->phone,
        'from_number' => '+49 30 99999999',
        'direction' => $faker->randomElement($callTypes),
        'status' => $faker->randomElement($callStatuses),
        'duration' => $duration,
        'started_at' => $faker->dateTimeBetween('-30 days', 'now'),
        'ended_at' => now(),
        'recording_url' => 'https://example.com/recording/test_' . $i . '.mp3',
        'transcript' => $this->generateTestTranscript($faker),
        'summary' => $faker->paragraph,
        'sentiment' => $faker->randomElement(['positive', 'neutral', 'negative']),
        'extracted_data' => [
            'appointment_date' => $faker->optional()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'service_requested' => $faker->optional()->randomElement(['Beratungsgespräch', 'Premium Beratung']),
            'callback_requested' => $faker->boolean(30)
        ],
        'metadata' => [
            'agent_id' => 'test_agent_001',
            'wait_time' => $faker->numberBetween(0, 30),
            'transfer_count' => 0
        ]
    ]);
}

// Helper für Transkript-Generierung
function generateTestTranscript($faker) {
    $lines = [];
    $speakers = ['agent', 'user'];
    
    for ($j = 0; $j < $faker->numberBetween(10, 30); $j++) {
        $lines[] = [
            'speaker' => $faker->randomElement($speakers),
            'text' => $faker->sentence,
            'timestamp' => $j * 5
        ];
    }
    
    return $lines;
}
```

---

## 📅 Test-Termine erstellen

```php
// Vergangene und zukünftige Termine
$appointmentStatuses = ['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show'];

for ($i = 1; $i <= 200; $i++) {
    $customer = \App\Models\Customer::inRandomOrder()->first();
    $service = \App\Models\Service::inRandomOrder()->first();
    $staff = \App\Models\Staff::inRandomOrder()->first();
    
    $startDate = $faker->dateTimeBetween('-60 days', '+60 days');
    $isPast = $startDate < now();
    
    $appointment = \App\Models\Appointment::create([
        'company_id' => $company->id,
        'branch_id' => $staff->branch_id,
        'customer_id' => $customer->id,
        'staff_id' => $staff->id,
        'service_id' => $service->id,
        'start_time' => $startDate,
        'end_time' => (clone $startDate)->addMinutes($service->duration),
        'status' => $isPast 
            ? $faker->randomElement(['completed', 'cancelled', 'no_show'])
            : $faker->randomElement(['scheduled', 'confirmed']),
        'price' => $service->price,
        'notes' => $faker->optional()->sentence,
        'confirmed_at' => $faker->optional()->dateTime,
        'reminder_sent_at' => $faker->optional()->dateTime,
        'created_at' => $faker->dateTimeBetween('-90 days', $startDate)
    ]);
    
    // Zugehörigen Anruf erstellen
    if ($faker->boolean(70)) {
        $call = \App\Models\Call::create([
            'company_id' => $company->id,
            'branch_id' => $appointment->branch_id,
            'customer_id' => $customer->id,
            'appointment_id' => $appointment->id,
            'retell_call_id' => 'booking_call_' . Str::uuid(),
            'phone_number' => $customer->phone,
            'status' => 'completed',
            'duration' => $faker->numberBetween(120, 300),
            'started_at' => $appointment->created_at,
            'type' => 'appointment_booking'
        ]);
    }
}
```

---

## 💰 Test-Rechnungen erstellen (wenn Billing-Modul aktiv)

```php
// Monatliche Rechnungen
for ($month = 6; $month >= 0; $month--) {
    $invoice = \App\Models\Invoice::create([
        'company_id' => $company->id,
        'invoice_number' => 'INV-' . date('Y-m', strtotime("-$month months")) . '-001',
        'status' => $month === 0 ? 'pending' : 'paid',
        'amount' => $faker->randomFloat(2, 500, 2500),
        'currency' => 'EUR',
        'period_start' => now()->subMonths($month)->startOfMonth(),
        'period_end' => now()->subMonths($month)->endOfMonth(),
        'due_date' => now()->subMonths($month)->endOfMonth()->addDays(14),
        'paid_at' => $month === 0 ? null : now()->subMonths($month)->endOfMonth()->addDays(7),
        'items' => [
            [
                'description' => 'Monatliche Grundgebühr',
                'quantity' => 1,
                'unit_price' => 299.00,
                'total' => 299.00
            ],
            [
                'description' => 'Anrufe (' . $faker->numberBetween(100, 500) . ' Minuten)',
                'quantity' => $faker->numberBetween(100, 500),
                'unit_price' => 0.10,
                'total' => $faker->randomFloat(2, 10, 50)
            ]
        ]
    ]);
}
```

---

## 🔄 Test-Szenarien vorbereiten

### 1. Edge Cases
```php
// Kunde ohne E-Mail
\App\Models\Customer::create([
    'company_id' => $company->id,
    'branch_id' => $branch1->id,
    'first_name' => 'Ohne',
    'last_name' => 'Email',
    'phone' => '+49 30 33333333'
]);

// Sehr langer Kundenname
\App\Models\Customer::create([
    'company_id' => $company->id,
    'branch_id' => $branch1->id,
    'first_name' => 'Maximilian-Alexander-Friedrich-Wilhelm',
    'last_name' => 'von und zu Hohenlohe-Langenburg-Öhringen',
    'email' => 'adel@example.com',
    'phone' => '+49 30 44444444'
]);

// Anruf mit sehr langem Transkript
$longTranscript = [];
for ($i = 0; $i < 200; $i++) {
    $longTranscript[] = [
        'speaker' => $i % 2 === 0 ? 'agent' : 'user',
        'text' => $faker->paragraph,
        'timestamp' => $i * 5
    ];
}

\App\Models\Call::create([
    'company_id' => $company->id,
    'branch_id' => $branch1->id,
    'retell_call_id' => 'long_call_001',
    'phone_number' => '+49 30 55555555',
    'status' => 'completed',
    'duration' => 3600, // 1 Stunde
    'transcript' => $longTranscript
]);
```

### 2. Performance Test-Daten
```php
// Viele Anrufe für einen Tag (Stress-Test)
$stressDate = now()->startOfDay();
for ($hour = 8; $hour <= 18; $hour++) {
    for ($minute = 0; $minute < 60; $minute += 5) {
        \App\Models\Call::create([
            'company_id' => $company->id,
            'branch_id' => $branch1->id,
            'retell_call_id' => "stress_call_{$hour}_{$minute}",
            'phone_number' => $faker->phoneNumber,
            'status' => 'completed',
            'duration' => $faker->numberBetween(30, 300),
            'started_at' => $stressDate->copy()->addHours($hour)->addMinutes($minute)
        ]);
    }
}
```

### 3. Fehler-Szenarien
```php
// Fehlgeschlagene Anrufe
for ($i = 1; $i <= 10; $i++) {
    \App\Models\Call::create([
        'company_id' => $company->id,
        'branch_id' => $branch1->id,
        'retell_call_id' => 'failed_call_' . $i,
        'phone_number' => $faker->phoneNumber,
        'status' => 'failed',
        'error_message' => $faker->randomElement([
            'Network timeout',
            'Invalid phone number',
            'Service unavailable',
            'Authentication failed'
        ]),
        'started_at' => now()->subHours($i)
    ]);
}
```

---

## 🧹 Test-Daten bereinigen

### Alle Test-Daten löschen
```bash
# Via Artisan Command
php artisan db:seed --class=CleanTestDataSeeder
```

### Selektives Löschen
```php
// In Tinker
$testCompany = \App\Models\Company::where('domain', 'test-gmbh')->first();

if ($testCompany) {
    // Cascading delete über Relationships
    $testCompany->appointments()->delete();
    $testCompany->calls()->delete();
    $testCompany->customers()->delete();
    $testCompany->staff()->delete();
    $testCompany->services()->delete();
    $testCompany->branches()->delete();
    $testCompany->users()->delete();
    $testCompany->delete();
}
```

---

## 📊 Test-Daten validieren

### Daten-Integrität prüfen
```sql
-- Überprüfen ob alle Anrufe eine Company haben
SELECT COUNT(*) as orphaned_calls 
FROM calls 
WHERE company_id IS NULL OR company_id NOT IN (SELECT id FROM companies);

-- Überprüfen ob alle Termine gültige Beziehungen haben
SELECT COUNT(*) as invalid_appointments
FROM appointments a
WHERE NOT EXISTS (SELECT 1 FROM customers c WHERE c.id = a.customer_id)
   OR NOT EXISTS (SELECT 1 FROM staff s WHERE s.id = a.staff_id)
   OR NOT EXISTS (SELECT 1 FROM services sv WHERE sv.id = a.service_id);

-- Performance-Check: Anzahl Datensätze
SELECT 
    (SELECT COUNT(*) FROM companies) as companies,
    (SELECT COUNT(*) FROM users) as users,
    (SELECT COUNT(*) FROM customers) as customers,
    (SELECT COUNT(*) FROM calls) as calls,
    (SELECT COUNT(*) FROM appointments) as appointments;
```

---

## 🚀 Quick Setup Script

```bash
#!/bin/bash
# test-data-setup.sh

echo "🚀 Setting up test data..."

# Backup existing data
echo "📦 Creating backup..."
mysqldump -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db > backup_before_test_$(date +%Y%m%d_%H%M%S).sql

# Run migrations
echo "🔧 Running migrations..."
php artisan migrate --force

# Clear caches
echo "🧹 Clearing caches..."
php artisan optimize:clear

# Create test data via seeder
echo "🌱 Seeding test data..."
php artisan db:seed --class=TestDataSeeder

# Start queues
echo "⚡ Starting queue workers..."
php artisan horizon

echo "✅ Test data setup complete!"
echo "📝 Test credentials:"
echo "   Admin: admin@test-gmbh.de / Test123!"
echo "   Manager: manager@test-gmbh.de / Test123!"
echo "   Staff: staff@test-gmbh.de / Test123!"
```

---

## 📝 Notizen

- Test-Daten sollten regelmäßig aktualisiert werden
- Vor Major-Releases immer mit frischen Test-Daten testen
- Test-Daten niemals in Production verwenden
- Backup vor dem Erstellen von Test-Daten ist Pflicht
- Test-Daten sollten alle Edge-Cases abdecken