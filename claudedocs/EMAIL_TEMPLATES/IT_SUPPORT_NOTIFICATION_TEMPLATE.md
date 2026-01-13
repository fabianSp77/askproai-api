# IT Support Notification Email Template

**Created**: 2025-12-22
**Version**: 1.0
**Language**: German (de-DE)
**Template Path**: `resources/views/emails/service-cases/it-support-notification.blade.php`
**Mailable Class**: `App\Mail\ITSupportNotification`

---

## Overview

Professional HTML email template for IT support ticket notifications. Designed for internal IT staff with full technical details, clean layout, and JSON data attachment.

### Key Features

- Modern, email-safe HTML design (table-based layout)
- Priority-based color coding
- Mobile-responsive styling
- Clickable phone/email links
- Full JSON data export as attachment
- **No external provider references** (sanitized output)

---

## Template Structure

### Section 1: Quick Info (Header)

| Element | Description | Styling |
|---------|-------------|---------|
| Priority Bar | Color-coded header with priority level | Background + left border color |
| Ticket ID | Large, prominent ticket number | Monospace font, 32px |
| Timestamp | Creation date and time | Right-aligned, subtle |
| Status Badges | Priority, Status, Category pills | Rounded badges with colors |

**Priority Colors:**
- **Critical**: Red (`#DC2626` / `#FEE2E2`)
- **High**: Orange (`#D97706` / `#FEF3C7`)
- **Normal**: Blue (`#1D4ED8` / `#DBEAFE`)
- **Low**: Gray (`#4B5563` / `#F3F4F6`)

### Section 2: Contact Information

Blue-themed card (`#EFF6FF`) containing:
- Customer name
- Phone number (clickable `tel:` link)
- Email address (clickable `mailto:` link)
- Location/office

### Section 3: Issue Details

Orange-themed card (`#FFF7ED`) containing:
- Subject line (prominent)
- Description (in styled box)
- Problem since (if available)
- Others affected indicator (badge if true)

### Section 4: Technical Data

Gray-themed card (`#F8FAFC`) containing:
- Internal ID
- Call Reference (renamed from `retell_call_id`)
- Call-ID (if available)
- Full JSON data block (dark code block style)
- Note about JSON attachment

### Footer

- Send timestamp
- Company name
- Action button: "Ticket bearbeiten"

---

## Data Sanitization Rules

The template automatically removes/renames external provider references:

| Original Field | Action | New Field Name |
|----------------|--------|----------------|
| `retell_call_id` | Renamed | `anruf_referenz` |
| `retell_agent_id` | Removed | - |
| `retell_call_status` | Removed | - |
| `calcom_booking_id` | Removed | - |

---

## Usage

### Basic Usage

```php
use App\Mail\ITSupportNotification;
use App\Models\ServiceCase;
use Illuminate\Support\Facades\Mail;

$case = ServiceCase::with(['category', 'customer', 'company', 'call'])->find($id);

Mail::to('it-support@company.de')
    ->send(new ITSupportNotification($case));
```

### Queue Usage

```php
// The Mailable implements ShouldQueue by default
Mail::to('it-support@company.de')
    ->queue(new ITSupportNotification($case));
```

### Multiple Recipients

```php
$recipients = ['support@company.de', 'helpdesk@company.de'];

Mail::to($recipients)
    ->send(new ITSupportNotification($case));
```

---

## JSON Attachment Structure

The email includes a JSON file attachment with complete ticket data:

```json
{
  "ticket": {
    "id": "SC-2024-0001",
    "interne_id": 123,
    "erstellt_am": "2024-12-22 14:30:00",
    "aktualisiert_am": "2024-12-22 14:30:00"
  },
  "klassifizierung": {
    "prioritaet": "high",
    "prioritaet_label": "Hoch",
    "status": "new",
    "status_label": "Neu",
    "kategorie": "Hardware",
    "kategorie_id": 5
  },
  "inhalt": {
    "betreff": "Drucker funktioniert nicht",
    "beschreibung": "Der Drucker im Buero 302 druckt nicht mehr..."
  },
  "kontakt": {
    "name": "Max Mustermann",
    "telefon": "+49 123 456789",
    "email": "max.mustermann@example.de",
    "standort": "Buero 302"
  },
  "problem_details": {
    "problem_seit": "Heute Morgen",
    "andere_betroffen": true
  },
  "referenzen": {
    "anruf_id": 456,
    "anruf_referenz": "call_abc123xyz",
    "kunden_id": 789,
    "unternehmen_id": 1
  },
  "metadaten": {
    "customer_name": "Max Mustermann",
    "anruf_referenz": "call_abc123xyz"
  },
  "export": {
    "generiert_am": "2024-12-22 14:35:00",
    "version": "1.0"
  }
}
```

**Filename Format**: `ticket_SC-2024-0001_2024-12-22_143000.json`

---

## Email Client Compatibility

The template uses email-safe techniques for maximum compatibility:

| Technique | Purpose |
|-----------|---------|
| Table-based layout | Outlook, older clients |
| Inline CSS | Gmail, webmail clients |
| MSO conditionals | Microsoft Outlook |
| `-webkit-text-size-adjust` | iOS Safari |
| Role attributes | Screen readers |

### Tested Clients

- Gmail (Web, iOS, Android)
- Outlook (2016, 2019, 365, Web)
- Apple Mail (macOS, iOS)
- Thunderbird
- Yahoo Mail

---

## Customization

### Adding New Fields

1. Add field to `ai_metadata` extraction in the `@php` block
2. Add display logic in appropriate section
3. Add to JSON structure in `buildSanitizedTicketData()`

### Changing Colors

Update the color arrays in the `@php` block:

```php
$priorityColors = [
    'critical' => ['bg' => '#FEE2E2', 'text' => '#DC2626', 'border' => '#DC2626'],
    // ... modify as needed
];
```

### Adding New Priority Levels

1. Add to `$priorityColors` array
2. Add to `$priorityLabels` array
3. Update `getPriorityPrefix()` in Mailable class

---

## Related Files

| File | Purpose |
|------|---------|
| `app/Mail/ITSupportNotification.php` | Mailable class with attachment logic |
| `resources/views/emails/service-cases/notification-html.blade.php` | Original template (for reference) |
| `app/Models/ServiceCase.php` | Service case model |

---

## Troubleshooting

### JSON Not Displaying

Ensure the `ai_metadata` field is cast to array in the model:

```php
protected $casts = [
    'ai_metadata' => 'array',
];
```

### Missing Contact Information

Check fallback chain:
1. `ai_metadata['customer_name']`
2. `$case->customer?->name`
3. Default: 'Nicht angegeben'

### Attachment Not Showing

Verify `Attachment::fromData()` is supported (Laravel 9+).

---

## Security Considerations

- All user input is escaped via Blade `{{ }}` syntax
- JSON output uses `JSON_UNESCAPED_UNICODE` for proper character encoding
- No raw HTML injection possible
- Provider-specific IDs are masked/renamed

---

## Changelog

### v1.0 (2024-12-22)
- Initial template creation
- Four-section layout (Quick Info, Contact, Issue, Technical)
- JSON attachment support
- Provider reference sanitization
- Mobile-responsive design
