<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends CompatibleMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('key', 100); // z.B. 'appointment.confirmed'
            $table->string('channel', 20); // email, sms, whatsapp
            $table->json('translations'); // {"de": {"subject": "...", "body": "..."}, "en": {...}}
            $table->json('variables')->nullable(); // verfügbare Variablen für dieses Template
            $table->json('metadata')->nullable(); // zusätzliche Einstellungen
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false); // System-Templates können nicht gelöscht werden
            $table->timestamps();
            
            // Unique constraint
            $table->unique(['company_id', 'key', 'channel'], 'notification_template_unique');
            
            // Indexes
            $table->index(['company_id', 'is_active']);
            $table->index('key');
            $table->index('channel');
        });
        
        // Seed default templates
        $this->seedDefaultTemplates();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
    
    /**
     * Seed default notification templates
     */
    private function seedDefaultTemplates(): void
    {
        $defaultTemplates = [
            [
                'key' => 'appointment.confirmed',
                'channel' => 'email',
                'translations' => [
                    'de' => [
                        'subject' => 'Terminbestätigung - {{service_name}}',
                        'body' => "Hallo {{customer_name}},\n\nIhr Termin wurde bestätigt:\n\nDatum: {{date}}\nUhrzeit: {{time}}\nService: {{service_name}}\nMitarbeiter: {{staff_name}}\n\nBitte kommen Sie 10 Minuten vor Ihrem Termin.\n\nMit freundlichen Grüßen,\n{{company_name}}"
                    ],
                    'en' => [
                        'subject' => 'Appointment Confirmation - {{service_name}}',
                        'body' => "Hello {{customer_name}},\n\nYour appointment has been confirmed:\n\nDate: {{date}}\nTime: {{time}}\nService: {{service_name}}\nStaff: {{staff_name}}\n\nPlease arrive 10 minutes before your appointment.\n\nBest regards,\n{{company_name}}"
                    ]
                ],
                'variables' => [
                    'customer_name', 'date', 'time', 'service_name', 
                    'staff_name', 'company_name', 'branch_address'
                ]
            ],
            [
                'key' => 'appointment.reminder',
                'channel' => 'sms',
                'translations' => [
                    'de' => [
                        'body' => "Erinnerung: Ihr Termin {{service_name}} ist morgen um {{time}}. Adresse: {{branch_address}}. Bei Fragen: {{phone}}"
                    ],
                    'en' => [
                        'body' => "Reminder: Your {{service_name}} appointment is tomorrow at {{time}}. Address: {{branch_address}}. Questions? Call {{phone}}"
                    ]
                ],
                'variables' => [
                    'service_name', 'time', 'branch_address', 'phone'
                ]
            ],
            [
                'key' => 'appointment.cancelled',
                'channel' => 'email',
                'translations' => [
                    'de' => [
                        'subject' => 'Terminabsage - {{service_name}}',
                        'body' => "Hallo {{customer_name}},\n\nIhr Termin am {{date}} um {{time}} wurde abgesagt.\n\nBitte kontaktieren Sie uns, um einen neuen Termin zu vereinbaren.\n\nMit freundlichen Grüßen,\n{{company_name}}"
                    ],
                    'en' => [
                        'subject' => 'Appointment Cancellation - {{service_name}}',
                        'body' => "Hello {{customer_name}},\n\nYour appointment on {{date}} at {{time}} has been cancelled.\n\nPlease contact us to schedule a new appointment.\n\nBest regards,\n{{company_name}}"
                    ]
                ],
                'variables' => [
                    'customer_name', 'date', 'time', 'service_name', 'company_name'
                ]
            ]
        ];
        
        // Insert templates for each company
        $companies = DB::table('companies')->get();
        
        foreach ($companies as $company) {
            foreach ($defaultTemplates as $template) {
                DB::table('notification_templates')->insert([
                    'company_id' => $company->id,
                    'key' => $template['key'],
                    'channel' => $template['channel'],
                    'translations' => json_encode($template['translations']),
                    'variables' => json_encode($template['variables']),
                    'is_active' => true,
                    'is_system' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }
};