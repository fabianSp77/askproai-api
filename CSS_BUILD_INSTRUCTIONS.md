# CSS Build Instructions - WICHTIG!

## Bei CSS-Änderungen IMMER diese Schritte ausführen:

### 1. Development Mode (während der Entwicklung)
```bash
npm run dev
# Lasse es laufen für Hot-Reload
```

### 2. Production Build (nach Änderungen)
```bash
npm run build
php artisan optimize:clear
```

### 3. Browser Cache leeren
- **Hard Refresh**: `Ctrl + Shift + R` (Windows/Linux) oder `Cmd + Shift + R` (Mac)
- **Entwicklertools**: F12 → Rechtsklick auf Reload → "Empty Cache and Hard Reload"

## Tailwind CSS Regeln

### ❌ NIEMALS dynamische Klassen:
```blade
{{-- FALSCH --}}
class="text-{{ $color }}-600"
class="bg-{{ $status }}-100"
```

### ✅ IMMER vollständige Klassen:
```blade
{{-- RICHTIG --}}
class="{{ $color === 'red' ? 'text-red-600' : 'text-green-600' }}"
class="{{ $status === 'active' ? 'bg-green-100' : 'bg-gray-100' }}"
```

## Safelist für dynamische Klassen

Wenn du dynamische Klassen brauchst, füge sie zur `tailwind.config.js` safelist hinzu:

```js
safelist: [
    'text-red-600',
    'text-green-600',
    'bg-green-100',
    'bg-gray-100',
    // ... alle möglichen Klassen
]
```

## Troubleshooting

### CSS-Änderungen nicht sichtbar?
1. `npm run build` ausführen
2. `php artisan optimize:clear`
3. Browser Cache leeren
4. Prüfen ob Klassen in der Safelist sind

### Vite Probleme?
```bash
rm -rf node_modules package-lock.json
npm install
npm run build
```