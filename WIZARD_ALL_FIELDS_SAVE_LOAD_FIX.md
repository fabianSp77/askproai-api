# Quick Setup Wizard - Vollständige Speicherung und Laden aller Felder

## Date: 2025-06-19

### Problem
Die ausgewählten Werte wurden gespeichert, aber nicht korrekt im Formular angezeigt beim erneuten Laden.

### Lösung

1. **Erweiterte `loadPhoneConfiguration`**:
   - Lädt jetzt auch `phone_strategy` basierend auf vorhandenen Telefonnummern
   - Erkennt automatisch ob "direct", "hotline" oder "mixed" Strategie verwendet wird

2. **Neue Methode `loadAdditionalSettings`**:
   - Lädt alle zusätzlichen Einstellungen aus den Company Settings
   - KI-Telefon Einstellungen (phone_setup, ai_voice, use_template_greeting, etc.)
   - Kommunikations-Einstellungen (SMS, WhatsApp)
   - Import-Einstellungen
   - Post-Setup Actions

3. **Erweiterte Save-Methoden**:
   - `saveStep3Data`: Speichert jetzt auch `calcom_connection_type` in Settings
   - `saveStep4Data`: Speichert alle KI und Kommunikations-Einstellungen
   - `saveStep6Data`: Speichert Service und Working Hours Präferenzen

4. **Verbesserte Felder**:
   - Cal.com API Key zeigt Platzhalter im Edit-Mode
   - Connection Type wird korrekt geladen
   - Staff Members werden mit allen Attributen geladen

### Geladene Felder:

**Company & Branches:**
- company_name
- industry
- logo
- branches (mit id, name, city, address, phone_number, features)

**Phone Configuration:**
- phone_strategy (direct/hotline/mixed)
- use_hotline
- hotline_number
- routing_strategy
- branch_phone_numbers (mit SMS/WhatsApp Einstellungen)

**Cal.com:**
- calcom_connection_type
- calcom_api_key (als Platzhalter)
- calcom_team_slug
- import_event_types

**KI-Telefon:**
- phone_setup
- ai_voice
- use_template_greeting
- custom_greeting
- enable_test_call

**Kommunikation:**
- global_sms_enabled
- global_whatsapp_enabled

**Services & Staff:**
- use_template_services
- use_template_hours
- staff_members (mit languages, skills, experience_level, certifications)
- custom_services
- post_setup_actions

### Technische Details:

Alle Einstellungen werden in der `settings` JSON-Spalte der Company gespeichert und beim Laden wieder korrekt ins Formular gefüllt. Die Features der Branches werden ebenfalls korrekt gespeichert und geladen.