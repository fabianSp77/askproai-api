<?php

namespace Database\Seeders;

use App\Models\PromptTemplate;
use Illuminate\Database\Seeder;

class PromptTemplateSeeder extends Seeder
{
    public function run()
    {
        // Base Templates
        $baseGreeting = PromptTemplate::create([
            'name' => 'Base Greeting Template',
            'slug' => 'base-greeting',
            'description' => 'Grundlegende Begrüßung für alle Anrufe',
            'content' => "Guten Tag und herzlich willkommen bei {{company_name}}.\nMein Name ist {{agent_name}} und ich bin Ihr virtueller Assistent.\n\nWie kann ich Ihnen heute helfen?",
            'variables' => ['company_name', 'agent_name'],
            'category' => 'general',
            'version' => '1.0.0',
            'is_active' => true,
        ]);

        $baseAppointment = PromptTemplate::create([
            'name' => 'Base Appointment Template',
            'slug' => 'base-appointment',
            'description' => 'Grundlegendes Template für Terminbestätigungen',
            'content' => "Vielen Dank für Ihre Terminbuchung.\n\nIhr Termin:\n- Datum: {{appointment_date}}\n- Uhrzeit: {{appointment_time}}\n- Service: {{service_name}}\n- Ort: {{location_address}}",
            'variables' => ['appointment_date', 'appointment_time', 'service_name', 'location_address'],
            'category' => 'general',
            'version' => '1.0.0',
            'is_active' => true,
        ]);

        // Retell.ai Templates (inheriting from base)
        $retellGreeting = PromptTemplate::create([
            'name' => 'Retell Agent Greeting',
            'slug' => 'retell-agent-greeting',
            'description' => 'Begrüßung für Retell.ai Agents',
            'content' => "{{parent}}\n\nIch kann Ihnen bei folgenden Anliegen helfen:\n- Terminvereinbarungen\n- Informationen zu unseren Services\n- Allgemeine Fragen\n\nWas möchten Sie gerne tun?",
            'variables' => [],
            'parent_id' => $baseGreeting->id,
            'category' => 'retell',
            'version' => '1.0.0',
            'is_active' => true,
        ]);

        $retellAppointment = PromptTemplate::create([
            'name' => 'Retell Appointment Confirmation',
            'slug' => 'retell-appointment-confirmation',
            'description' => 'Terminbestätigung für Retell.ai',
            'content' => "{{parent}}\n\nBitte notieren Sie sich Ihren Termin. Sie erhalten zusätzlich eine Bestätigung per {{confirmation_method}}.\n\nHaben Sie noch weitere Fragen zu Ihrem Termin?",
            'variables' => ['confirmation_method'],
            'parent_id' => $baseAppointment->id,
            'category' => 'retell',
            'version' => '1.0.0',
            'is_active' => true,
        ]);

        // Email Templates
        $emailBase = PromptTemplate::create([
            'name' => 'Email Base Template',
            'slug' => 'email-base',
            'description' => 'Basis-Template für alle E-Mails',
            'content' => "Sehr geehrte/r {{customer_name}},\n\n{{content}}\n\nMit freundlichen Grüßen\n{{company_name}}\n\n---\nDiese E-Mail wurde automatisch generiert.",
            'variables' => ['customer_name', 'content', 'company_name'],
            'category' => 'email',
            'version' => '1.0.0',
            'is_active' => true,
        ]);

        $emailAppointment = PromptTemplate::create([
            'name' => 'Email Appointment Confirmation',
            'slug' => 'email-appointment-confirmation',
            'description' => 'E-Mail Terminbestätigung',
            'content' => "{{parent}}\n\n[content]\nwir bestätigen Ihren Termin:\n\n📅 Datum: {{appointment_date}}\n🕐 Uhrzeit: {{appointment_time}}\n📍 Ort: {{location_address}}\n🔧 Service: {{service_name}}\n\nBitte erscheinen Sie pünktlich zu Ihrem Termin.\n\nSollten Sie den Termin nicht wahrnehmen können, bitten wir um rechtzeitige Absage unter {{company_phone}}.\n[/content]",
            'variables' => ['appointment_date', 'appointment_time', 'location_address', 'service_name', 'company_phone'],
            'parent_id' => $emailBase->id,
            'category' => 'email',
            'version' => '1.0.0',
            'is_active' => true,
        ]);

        // Multi-level inheritance example
        $medicalGreeting = PromptTemplate::create([
            'name' => 'Medical Practice Greeting',
            'slug' => 'medical-practice-greeting',
            'description' => 'Spezielle Begrüßung für Arztpraxen',
            'content' => "{{parent}}\n\nFür Notfälle außerhalb unserer Sprechzeiten wenden Sie sich bitte an den ärztlichen Bereitschaftsdienst unter 116117.",
            'variables' => [],
            'parent_id' => $retellGreeting->id,
            'category' => 'retell',
            'version' => '1.0.0',
            'is_active' => true,
            'metadata' => ['industry' => 'healthcare', 'compliance' => 'DSGVO'],
        ]);

        // System Templates
        PromptTemplate::create([
            'name' => 'Error Message Template',
            'slug' => 'error-message',
            'description' => 'Template für Fehlermeldungen',
            'content' => "Es ist ein Fehler aufgetreten: {{error_message}}\n\nFehlercode: {{error_code}}\n\nBitte versuchen Sie es später erneut oder kontaktieren Sie unseren Support.",
            'variables' => ['error_message', 'error_code'],
            'category' => 'system',
            'version' => '1.0.0',
            'is_active' => true,
        ]);

        // Cal.com Templates
        PromptTemplate::create([
            'name' => 'Cal.com Event Description',
            'slug' => 'calcom-event-description',
            'description' => 'Beschreibung für Cal.com Events',
            'content' => "Termin gebucht über {{booking_source}}\n\nKunde: {{customer_name}}\nTelefon: {{customer_phone}}\nE-Mail: {{customer_email}}\n\nNotizen:\n{{customer_notes}}",
            'variables' => ['booking_source', 'customer_name', 'customer_phone', 'customer_email', 'customer_notes'],
            'category' => 'calcom',
            'version' => '1.0.0',
            'is_active' => true,
        ]);
    }
}