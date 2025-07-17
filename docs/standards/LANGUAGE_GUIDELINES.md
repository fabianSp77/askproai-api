# Language Guidelines for Documentation

> 📋 **Version**: 1.0  
> 📅 **Last Updated**: 2025-01-10  
> 👥 **Maintained By**: Documentation Team  
> 🌐 **Languages**: English (Primary), German (Secondary)

## Language Standards

### Primary Language: English
- All technical documentation in English
- Use American English spelling
- International date format: YYYY-MM-DD
- 24-hour time format: HH:MM

### Secondary Language: German
- User-facing documentation
- Marketing materials
- Legal documents
- Customer communications

## English Guidelines

### Spelling Conventions
Use American English:
- ✅ "initialize", "color", "center"
- ❌ "initialise", "colour", "centre"

### Common Technical Terms
| Term | Usage | Not |
|------|-------|-----|
| email | lowercase, one word | e-mail, Email |
| database | one word | data base |
| username | one word | user name |
| timestamp | one word | time stamp |
| real-time | hyphenated | realtime |

### Clarity Rules

#### Use Simple Words
```markdown
❌ Utilize the configuration parameters
✅ Use the configuration settings

❌ Prior to initialization
✅ Before starting

❌ In order to facilitate
✅ To enable
```

#### Avoid Jargon
```markdown
❌ "The system leverages a distributed architecture"
✅ "The system uses multiple servers"

❌ "Instantiate the singleton"
✅ "Create the single instance"
```

#### Be Specific
```markdown
❌ "The process may take some time"
✅ "The process takes 2-5 minutes"

❌ "Add the necessary configuration"
✅ "Add these configuration values:"
```

### Grammar Rules

#### Present Tense
```markdown
❌ "This will create a new user"
✅ "This creates a new user"

❌ "The function would return an array"
✅ "The function returns an array"
```

#### Active Voice
```markdown
❌ "The file is created by the system"
✅ "The system creates the file"

❌ "Errors are logged by the application"
✅ "The application logs errors"
```

#### Imperative Mood
```markdown
❌ "You should click the button"
✅ "Click the button"

❌ "Users need to configure"
✅ "Configure the settings"
```

### Technical Writing

#### API Documentation
```markdown
## Create User

Creates a new user account.

**Endpoint**: `POST /api/users`

**Request Body**:
```json
{
  "name": "string",
  "email": "string"
}
```

**Returns**: User object with generated ID
```

#### Error Messages
```markdown
// Clear, actionable error messages
❌ "Operation failed"
✅ "Cannot create user: Email already exists"

❌ "Invalid input"
✅ "Name must be at least 3 characters long"
```

## German Guidelines (Deutsch)

### Formality Level
- Use formal "Sie" for documentation
- Informal "du" only for internal docs
- Consistent formality throughout

### Technical Terms
Keep English technical terms when appropriate:
```markdown
Der Server verwendet eine REST API.
Die Datenbank nutzt MySQL.
```

### Common Translations
| English | German | Context |
|---------|--------|---------|
| User | Benutzer | General user |
| Customer | Kunde | Paying customer |
| Settings | Einstellungen | Configuration |
| Dashboard | Dashboard | Keep English |
| API | API | Keep English |

### German Examples

#### User Guide
```markdown
## Erste Schritte

Willkommen bei AskProAI. Diese Anleitung hilft Ihnen beim Einstieg.

### Voraussetzungen
Sie benötigen:
- Ein aktives Konto
- Administratorrechte
- Eine stabile Internetverbindung

### Anmeldung
1. Öffnen Sie Ihren Browser
2. Navigieren Sie zu https://app.askproai.de
3. Geben Sie Ihre Anmeldedaten ein
4. Klicken Sie auf "Anmelden"
```

#### Error Messages
```markdown
// German error messages
"Fehler: E-Mail-Adresse bereits vergeben"
"Warnung: Speicherplatz fast voll (90% belegt)"
"Erfolg: Daten erfolgreich gespeichert"
```

## Bilingual Documentation

### Structure
```markdown
# Feature Name / Funktionsname

[English] | [Deutsch]

## Overview
This feature enables...

## Überblick
Diese Funktion ermöglicht...
```

### Switching Languages
```markdown
<!-- Language selector -->
🇬🇧 [English](./en/guide.md) | 🇩🇪 [Deutsch](./de/guide.md)
```

### Synchronized Updates
- Maintain version parity
- Update both languages together
- Mark untranslated sections

## Inclusive Language

### Gender-Neutral Terms
| Avoid | Use |
|-------|-----|
| mankind | humanity |
| manpower | workforce |
| chairman | chairperson |
| he/she | they |

### Accessibility
```markdown
❌ "Click the red button"
✅ "Click the 'Delete' button (red)"

❌ "As you can see in the image"
✅ "The dashboard shows three panels"
```

### Cultural Sensitivity
- Avoid idioms and colloquialisms
- Use universal examples
- Consider timezone differences
- Respect cultural norms

## Common Phrases

### English Phrases
```markdown
# Starting actions
- "To begin, ..."
- "First, ..."
- "Start by ..."

# Providing information
- "This feature allows you to..."
- "Use this option to..."
- "This setting controls..."

# Warnings
- "Important: ..."
- "Note: ..."
- "Warning: Do not..."

# Results
- "This creates..."
- "You should see..."
- "The system returns..."
```

### German Phrases
```markdown
# Aktionen starten
- "Um zu beginnen, ..."
- "Zuerst ..."
- "Starten Sie mit ..."

# Informationen bereitstellen
- "Diese Funktion ermöglicht es Ihnen..."
- "Verwenden Sie diese Option, um..."
- "Diese Einstellung steuert..."

# Warnungen
- "Wichtig: ..."
- "Hinweis: ..."
- "Warnung: Nicht..."

# Ergebnisse
- "Dies erstellt..."
- "Sie sollten sehen..."
- "Das System gibt zurück..."
```

## Translation Workflow

### Process
1. Write in English first
2. Technical review
3. Translate to German
4. Native speaker review
5. Synchronize updates

### Tools
- Translation memory: MemoQ/Phrase
- Terminology database: Internal glossary
- Review: Native speakers
- Version control: Git

### Quality Checks
- [ ] Terminology consistency
- [ ] Technical accuracy
- [ ] Cultural appropriateness
- [ ] Formatting preserved
- [ ] Links updated

## Glossary Management

### Technical Terms
```yaml
# glossary.yml
api:
  en: "API (Application Programming Interface)"
  de: "API (Programmierschnittstelle)"
  
authentication:
  en: "Authentication"
  de: "Authentifizierung"
  
dashboard:
  en: "Dashboard"
  de: "Dashboard" # Keep English
```

### Business Terms
```yaml
appointment:
  en: "Appointment"
  de: "Termin"
  
customer:
  en: "Customer"
  de: "Kunde"
  
branch:
  en: "Branch"
  de: "Filiale"
```

## Regional Considerations

### Date Formats
```markdown
# International (ISO 8601)
2025-01-10

# Display formats
EN: January 10, 2025
DE: 10. Januar 2025
```

### Number Formats
```markdown
# Decimal separators
EN: 1,234.56
DE: 1.234,56

# Currency
EN: €1,234.56
DE: 1.234,56 €
```

### Phone Numbers
```markdown
# International format
+49 123 456789

# Local display
DE: 0123 456789
International: +49 123 456789
```

## Quality Guidelines

### Readability Scores
- Flesch Reading Ease: > 60
- Grade level: < 12
- Sentence length: < 25 words
- Paragraph length: < 5 sentences

### Consistency Checks
- Use same terms throughout
- Consistent style/tone
- Same formatting rules
- Unified voice

### Review Checklist
- [ ] Grammar and spelling
- [ ] Technical accuracy
- [ ] Terminology consistency
- [ ] Cultural appropriateness
- [ ] Format compliance

---

> 🔄 **Auto-Updated**: This documentation is automatically checked for updates. Last verification: 2025-01-10