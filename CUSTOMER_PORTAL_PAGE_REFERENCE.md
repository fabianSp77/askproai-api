# Customer Portal - Page Reference Guide

Quick visual reference for all customer portal pages and their features.

---

## 🔐 Authentication Pages

### Invitation Page
**URL**: `/kundenportal/einladung/{token}`

```
┌─────────────────────────────────────┐
│         🔵 Willkommen!              │
│                                     │
│  Erstellen Sie Ihr Kundenkonto     │
│                                     │
│  ┌───────────────────────────────┐ │
│  │ 📧 max@beispiel.de (readonly) │ │
│  │ 📱 +4915112345678 (readonly)  │ │
│  └───────────────────────────────┘ │
│                                     │
│  Name: [____________]               │
│  Email: [____________]              │
│  Password: [____________] 👁        │
│  Confirm: [____________] 👁         │
│  ☑️ AGB akzeptieren                 │
│                                     │
│  [   Konto erstellen   ]           │
└─────────────────────────────────────┘
```

**Features**:
- Token validation on load
- Pre-filled phone/email
- Password visibility toggle
- Real-time validation
- Terms checkbox required

---

## 📅 Appointments List

### Index Page (Tabs View)
**URL**: `/meine-termine`

```
┌─────────────────────────────────────┐
│  [Anstehend (3)] [Vergangene (5)]   │
│  [Storniert (1)]                    │
│                                     │
│  ┌─────────────┐ ┌─────────────┐  │
│  │ ✅ Bestätigt│ │ ✅ Bestätigt│  │
│  │ ──────────  │ │ ──────────  │  │
│  │  📅 24 NOV  │ │  📅 25 NOV  │  │
│  │  Montag     │ │  Dienstag   │  │
│  │  🕐 10:00   │ │  🕐 14:00   │  │
│  │             │ │             │  │
│  │  Haarschnitt│ │  Dauerwelle │  │
│  │  👤 Maria   │ │  👤 Anna    │  │
│  │  📍 Filiale │ │  📍 Filiale │  │
│  │             │ │             │  │
│  │ [Details]   │ │ [Details]   │  │
│  │ [Umbuchen]  │ │ [Umbuchen]  │  │
│  │ [Stornieren]│ │ [Stornieren]│  │
│  └─────────────┘ └─────────────┘  │
└─────────────────────────────────────┘
```

**Features**:
- 3 tabs with badge counts
- Grid layout (responsive)
- Status indicators
- Quick actions per card
- Empty states per tab
- Pull to refresh

---

## 📋 Appointment Details

### Detail View
**URL**: `/meine-termine/{id}`

```
┌─────────────────────────────────────┐
│  ← Zurück zu meinen Terminen        │
│                                     │
│  ┌───────────────────────────────┐ │
│  │  ✅ Termin Bestätigt          │ │
│  │  ID: 123                      │ │
│  └───────────────────────────────┘ │
│                                     │
│  ┌──┐                               │
│  │24│ Montag, 24. November 2025    │
│  │📅│ 🕐 10:00 - 11:00 (60 Min.)  │
│  └──┘                               │
│                                     │
│  🔍 Dienstleistung                  │
│  ┌───────────────────────────────┐ │
│  │ ✂️ Herrenhaarschnitt          │ │
│  │ Klassischer Haarschnitt       │ │
│  └───────────────────────────────┘ │
│                                     │
│  👤 Mitarbeiter                     │
│  ┌───────────────────────────────┐ │
│  │ (M) Maria Schmidt             │ │
│  │ Friseurmeisterin              │ │
│  └───────────────────────────────┘ │
│                                     │
│  📍 Standort                        │
│  Hauptfiliale, Musterstraße 1      │
│                                     │
│  💶 Preis: 35 €                     │
│                                     │
│  📝 Notizen                         │
│  Bitte Seiten kürzer               │
│                                     │
│  [    📅 Termin umbuchen    ]      │
│  [    ❌ Termin stornieren  ]      │
└─────────────────────────────────────┘
```

**Features**:
- Large date display
- Complete information
- Staff avatar
- Price display
- Notes section
- Conditional actions
- Metadata (created, updated)

---

## 🔄 Reschedule Page

### Reschedule Flow
**URL**: `/meine-termine/{id}/umbuchen`

```
┌─────────────────────────────────────┐
│  ← Zurück zu Termindetails          │
│                                     │
│  📅 Aktueller Termin (grau)         │
│  ┌───────────────────────────────┐ │
│  │ 24. Nov 2025, 10:00 - 11:00   │ │
│  │ Herrenhaarschnitt             │ │
│  └───────────────────────────────┘ │
│                                     │
│  ⭐ Empfohlene Termine              │
│  [25.Nov 10:00] [25.Nov 14:00]     │
│  [26.Nov 09:00]                    │
│                                     │
│  📆 Kalenderansicht                 │
│  [◀️] KW 48 (24.11. - 30.11.) [▶️] │
│                                     │
│  Montag, 25. November               │
│  [09:00] [10:00] [14:00] [15:30]   │
│                                     │
│  Dienstag, 26. November             │
│  [09:00] [10:30] [13:00] [16:00]   │
│                                     │
│  ✅ Ausgewählter Termin             │
│  ┌───────────────────────────────┐ │
│  │ Dienstag, 26. November 2025   │ │
│  │ 10:30 Uhr                     │ │
│  └───────────────────────────────┘ │
│                                     │
│  ℹ️ Umbuchungsrichtlinien          │
│  • Kostenlos bis 24h vorher        │
│  • Bestätigung per E-Mail          │
│                                     │
│  [  ✅ Umbuchung bestätigen  ]     │
│  [       Abbrechen        ]        │
└─────────────────────────────────────┘
```

**Features**:
- Current appointment shown
- Quick suggestions
- Week navigation
- Slots grouped by day
- Visual selection
- Policy notice
- Confirmation box

---

## ❌ Cancellation Page

### Cancel Flow
**URL**: `/meine-termine/{id}/stornieren`

```
┌─────────────────────────────────────┐
│  ← Zurück zu Termindetails          │
│                                     │
│  ⚠️ ACHTUNG: Termin wird storniert │
│  Diese Aktion kann nicht            │
│  rückgängig gemacht werden.         │
│                                     │
│  📅 Zu stornieren                   │
│  ┌───────────────────────────────┐ │
│  │ │ 24. Nov 2025, 10:00-11:00  │ │
│  │ │ Herrenhaarschnitt          │ │
│  │ │ mit Maria Schmidt          │ │
│  └─│─────────────────────────────┘ │
│    red accent                       │
│                                     │
│  ⚠️ Stornierungsrichtlinien         │
│  • >24h: Kostenlose Stornierung    │
│  • <24h: Gebühr möglich            │
│                                     │
│  Grund der Stornierung (optional):  │
│  ┌───────────────────────────────┐ │
│  │                               │ │
│  │                               │ │
│  └───────────────────────────────┘ │
│                                     │
│  💡 Termin lieber umbuchen?         │
│  [→ Termin umbuchen]               │
│                                     │
│  ☑️ Ich bestätige die Stornierung  │
│     Richtlinien gelesen            │
│                                     │
│  [ ❌ Termin endgültig stornieren ] │
│  [          Zurück           ]     │
└─────────────────────────────────────┘

CONFIRM MODAL:
┌─────────────────────────────────────┐
│  ⚠️ Stornierung bestätigen          │
│                                     │
│  Sind Sie sicher? Diese Aktion      │
│  kann nicht rückgängig gemacht      │
│  werden.                            │
│                                     │
│  [Ja, stornieren] [Abbrechen]      │
└─────────────────────────────────────┘
```

**Features**:
- Warning banner (prominent)
- Dynamic policy display
- Optional reason field
- Reschedule suggestion
- Confirmation checkbox
- Double confirmation modal
- Color-coded UI (red theme)

---

## 🧩 Reusable Components

### Appointment Card
```
┌─────────────────────────┐
│ ✅ Bestätigt            │
│ ─────────────────────── │
│  📅 24  Montag          │
│  NOV  24. November      │
│        🕐 10:00-11:00   │
│        (60 Min.)        │
│                         │
│  ✂️ Herrenhaarschnitt   │
│  👤 Maria Schmidt       │
│  📍 Hauptfiliale        │
│                         │
│  [Details] [Umbuchen]   │
│  [Stornieren]           │
└─────────────────────────┘
```

### Time Slot Picker
```
┌─────────────────────────┐
│  [◀️] KW 48 [▶️]        │
│                         │
│  Montag, 25. November   │
│  [09:00] [10:00]       │
│  [14:00] [15:30]       │
│                         │
│  Dienstag, 26. Nov      │
│  [09:00] [10:30]       │
│  [13:00] [16:00]       │
└─────────────────────────┘
```

### Loading Spinner
```
┌─────────────────────────┐
│          ⚙️             │
│     (spinning icon)     │
│                         │
│  Daten werden geladen...│
└─────────────────────────┘
```

### Error Message
```
┌─────────────────────────┐
│  ❌ Fehler              │
│  ─────────────────────  │
│  Ein Fehler ist         │
│  aufgetreten.           │
│                         │
│  [Erneut versuchen]    │
└─────────────────────────┘
```

### Toast Notification
```
    ┌──────────────────┐
    │ ✅ Erfolgreich! │
    │ Termin gebucht  │
    │            [✕]  │
    └──────────────────┘
      (bottom-right)
```

---

## 📱 Responsive Breakpoints

### Mobile (< 640px)
```
┌────────┐
│ Header │
│ ══════ │
│        │
│ Card 1 │
│        │
│ Card 2 │
│        │
│ Card 3 │
│        │
└────────┘
Single column
```

### Tablet (640px - 1023px)
```
┌──────────────────┐
│     Header       │
│ ════════════════ │
│                  │
│ Card 1 │ Card 2 │
│ Card 3 │ Card 4 │
│                  │
└──────────────────┘
Two columns
```

### Desktop (≥ 1024px)
```
┌────────────────────────────┐
│          Header            │
│ ══════════════════════════ │
│                            │
│ Card 1 │ Card 2 │ Card 3  │
│ Card 4 │ Card 5 │ Card 6  │
│                            │
└────────────────────────────┘
Three columns
```

---

## 🎨 Color System

### Status Colors
```
✅ Bestätigt    → Green  (#10b981)
⏳ Ausstehend   → Yellow (#f59e0b)
❌ Storniert    → Red    (#ef4444)
✔️ Abgeschlossen → Gray   (#6b7280)
```

### UI Colors
```
Primary:   #667eea (purple-blue)
Success:   #10b981 (green)
Warning:   #f59e0b (orange)
Danger:    #ef4444 (red)
Neutral:   #6b7280 (gray)
```

### Button Styles
```
Primary:    [Blue background, white text]
Secondary:  [White background, blue border]
Danger:     [Red background, white text]
Ghost:      [Transparent, gray text]
```

---

## 🔗 Navigation Flow

```
Invitation Email
       ↓
/kundenportal/einladung/{token}
       ↓ (Register)
/meine-termine
       ↓
[Select Appointment]
       ↓
/meine-termine/{id}
       ↓
[Choose Action]
       ↓
/meine-termine/{id}/umbuchen    OR    /meine-termine/{id}/stornieren
       ↓ (Confirm)                           ↓ (Confirm)
/meine-termine/{id}                    /meine-termine
```

---

## 🔐 Authentication States

### Not Authenticated
```
→ Page loads
→ Alpine checks localStorage
→ No token found
→ Redirect to /kundenportal/login
```

### Authenticated
```
→ Page loads
→ Alpine checks localStorage
→ Token found
→ Set Authorization header
→ Make API calls
```

### Token Expired
```
→ API call returns 401
→ Axios interceptor catches
→ Clear localStorage
→ Redirect to /kundenportal/login
```

---

## 📊 State Management

### Global State (Alpine.js)
```javascript
{
  auth: {
    token: "...",
    user: { name, email, phone }
  },
  toast: {
    show: false,
    type: "success",
    message: "..."
  }
}
```

### Page State (Per Component)
```javascript
{
  loading: true,
  error: null,
  data: [],
  selectedItem: null
}
```

---

## 🛠️ Utility Functions

### Global Utilities (Available in all pages)
```javascript
$root.formatDate(date, format)
$root.formatTime(time)
$root.showToast(message, type)
$root.handleApiError(error)
$root.login(token, user)
$root.logout()
$root.isAuthenticated()
```

### Component Utilities
```javascript
DateTimeUtils.formatDate()
ValidationUtils.isValidEmail()
StatusUtils.getStatusText()
StorageUtils.set()
ErrorUtils.getErrorMessage()
UIUtils.scrollTo()
```

---

## 📝 Form Validation

### Client-Side Rules
```
Name:       Required, min 2 chars
Email:      Required, valid format
Password:   Required, min 8 chars
Confirm:    Must match password
Phone:      German format (+49...)
Terms:      Must be checked
```

### Error Display
```
┌─────────────────┐
│ Name: [___]     │ ← Input
│ ⚠️ Zu kurz      │ ← Error message
└─────────────────┘
```

---

## ✅ Accessibility Features

### Keyboard Navigation
- Tab through all interactive elements
- Enter to activate buttons/links
- Escape to close modals
- Arrow keys in date pickers

### Screen Reader Support
- Semantic HTML elements
- ARIA labels on buttons
- SR-only text for icons
- Form labels associated
- Status announcements

### Visual Accessibility
- High contrast colors
- Focus indicators (2px ring)
- Large touch targets (44x44px)
- Clear error messages
- Icon + text labels

---

**Document Date**: 2025-11-24
**Purpose**: Quick reference for developers and designers
**Status**: Complete and ready for use
