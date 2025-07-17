# @askproai/ui

Shared UI component library für AskProAI Admin und Business Portals.

## Installation

```bash
npm install @askproai/ui
```

## Design System

### Design Tokens

Unser Design System basiert auf CSS Custom Properties (Design Tokens), die eine konsistente visuelle Sprache über beide Portale hinweg gewährleisten.

#### Basis-Tokens

```css
/* In deiner App importieren */
import '@askproai/ui/styles/globals.css'

/* Für Admin Portal zusätzlich */
import '@askproai/ui/styles/admin-tokens.css'

/* Für Business Portal zusätzlich */
import '@askproai/ui/styles/business-tokens.css'
```

#### Farb-System

**Grayscale**: 11 Abstufungen von 50 (hellstes) bis 950 (dunkelstes)
- Verwendet für Text, Borders, Backgrounds

**Semantic Colors**:
- Primary: Blau-Töne für primäre Aktionen
- Success: Grün für Erfolg/Bestätigung
- Warning: Amber für Warnungen
- Error: Rot für Fehler/Kritisch

**Dark Mode**: Automatische Anpassung aller Farben

#### Spacing

Basiert auf Tailwind's Standard-Spacing-System:
- 0.25rem Schritte für kleine Abstände
- 0.5rem Schritte für mittlere Abstände
- 1rem Schritte für große Abstände

#### Typography

- Font: Geist Sans (System-UI Fallback)
- Mono Font: Geist Mono (Monospace Fallback)
- Größen: 2xs bis 6xl
- Line Heights: Optimiert für Lesbarkeit

### Komponenten-Verwendung

```tsx
import { Button, Card, ThemeProvider } from '@askproai/ui'

function App() {
  return (
    <ThemeProvider defaultTheme="system">
      <Card hover gradient>
        <Button variant="primary" size="lg">
          Click me
        </Button>
      </Card>
    </ThemeProvider>
  )
}
```

### Tailwind Configuration

#### Für Admin Portal

```js
// tailwind.config.js
const adminConfig = require('@askproai/config/tailwind/admin')

module.exports = adminConfig
```

#### Für Business Portal

```js
// tailwind.config.js
const businessConfig = require('@askproai/config/tailwind/business')

module.exports = businessConfig
```

### Theme Provider

Der ThemeProvider verwaltet Light/Dark Mode:

```tsx
<ThemeProvider
  defaultTheme="system"      // 'light' | 'dark' | 'system'
  storageKey="theme-pref"    // LocalStorage key
  enableSystem={true}        // System-Präferenz respektieren
  disableTransitionOnChange  // Keine Transitions beim Wechsel
>
  {children}
</ThemeProvider>
```

### Responsive Design

Alle Komponenten sind mobile-first entwickelt:
- Touch-optimierte Interaktionen
- Responsive Spacing
- Adaptive Layouts
- Safe Area Insets für moderne Geräte

### Accessibility

- ARIA Labels und Roles
- Keyboard Navigation
- Focus Management
- Screen Reader Support
- Kontrast-konforme Farben

## Development

```bash
# Build
npm run build

# Watch Mode
npm run dev

# Type Check
npm run type-check
```

## Komponenten-Status

✅ **Fertig**:
- Button (mit Animationen)
- Card (mit Hover-Effekten)
- ThemeProvider
- Utility Hooks

🚧 **In Arbeit**:
- Dialog
- Dropdown Menu
- Input/Form Elemente
- Data Table
- Charts

📋 **Geplant**:
- Date Picker
- Time Picker
- File Upload
- Avatar
- Badge
- Progress