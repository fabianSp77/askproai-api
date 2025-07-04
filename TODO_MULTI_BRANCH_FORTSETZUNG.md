# TODO: Multi-Branch Implementation Fortsetzung

## 🎯 Ziel
Implementierung eines funktionierenden Branch Selectors für das Multi-Branch-System

## 🔧 Quick Fix für morgen (damit es erstmal funktioniert)

```php
// In app/Filament/Admin/Pages/BranchSelector.php
// Zeile 21 ändern zu:
protected ?BranchContextManager $branchContext = null;

// In mount() Methode:
public function mount(): void
{
    $this->branchContext = app(BranchContextManager::class);
    $this->loadBranches();
}

// In switchBranch() Methode am Anfang hinzufügen:
public function switchBranch($branchId): void
{
    if (!$this->branchContext) {
        $this->branchContext = app(BranchContextManager::class);
    }
    // ... rest of code
}
```

## 📋 Offene Aufgaben aus Phase 1

### ✅ Erledigt
- [x] BranchContextManager Service
- [x] Integration Hub Page
- [x] Navigation Cleanup
- [x] Basic Branch Switching (mit Fehlern)

### ❌ Noch offen
- [ ] Funktionierender Branch Selector (ohne Fehler)
- [ ] Branch-basierte Datenfilterung testen
- [ ] Mobile Version des Branch Selectors

## 🚀 Phase 2: Branch-Level Configurations

### 1. Branch-spezifische Einstellungen
- [ ] Öffnungszeiten pro Filiale
- [ ] Eigene Telefonnummern pro Filiale
- [ ] Eigene Cal.com Event Types pro Filiale
- [ ] Eigene Retell Agenten pro Filiale

### 2. Staff-Branch Zuordnungen
- [ ] Mitarbeiter können mehreren Filialen zugeordnet werden
- [ ] Primäre Filiale (home_branch_id) festlegen
- [ ] Verfügbarkeiten pro Filiale definieren

### 3. Service-Branch Matrix
- [ ] Services können filialspezifisch sein
- [ ] Preise können pro Filiale variieren
- [ ] Verfügbarkeit von Services pro Filiale

## 🛠️ Technische Schulden

### Livewire in Filament Hooks
- Problem: Livewire Components funktionieren nicht in Filament Render Hooks
- Lösung: Filament-native Komponenten verwenden

### Alpine.js Dropdowns
- Problem: @click.away funktioniert nicht zuverlässig
- Lösung: Einfachere UI-Patterns verwenden

### Session Management
- BranchContextManager nutzt Laravel Sessions
- Funktioniert, aber könnte optimiert werden

## 📊 Test-Daten

```sql
-- Aktuelle Test-Umgebung
Company: AskProAI Test Company (ID: 1)
Branch: Hauptfiliale (ID: 35a66176-5376-11f0-b773-0ad77e7a9793)
User: fabian@askproai.de (Super Admin)

-- Weitere Test-Filialen anlegen
INSERT INTO branches (id, company_id, name, active) VALUES 
(UUID(), 1, 'Filiale Berlin', 1),
(UUID(), 1, 'Filiale München', 1);
```

## 🎨 UI/UX Alternativen

### Option A: Modal statt Dropdown
- Branch Selector öffnet Modal
- Mehr Platz für Informationen
- Keine Dropdown-Probleme

### Option B: Slide-Over Panel
- Von rechts einfahrendes Panel
- Platz für Zusatzinfos (Adresse, Telefon, etc.)
- Modern und mobil-freundlich

### Option C: Top-Bar mit Tabs
- Filialen als Tabs in der Top-Navigation
- Schneller Wechsel
- Immer sichtbar

## 🔍 Debug-Befehle

```bash
# Cache leeren
php artisan optimize:clear
php artisan view:clear
rm -rf storage/framework/views/*

# Livewire Komponente testen
php artisan tinker
> app(\App\Livewire\GlobalBranchSelector::class)->render();

# Branch Context testen
php artisan tinker
> $bc = app(\App\Services\BranchContextManager::class);
> $bc->getBranchesForUser();
> $bc->getCurrentBranch();

# PHP-FPM neustarten (bei hartnäckigen Problemen)
sudo systemctl restart php8.3-fpm
```

## 📝 Notizen für morgen

1. **Nicht vergessen**: Test-Call auf +493083793369 durchführen
2. **Performance**: Bei vielen Filialen könnte das Laden langsam werden
3. **Security**: Branch-Zugriff wird bereits in BranchContextManager geprüft
4. **UX**: User Feedback zeigt, dass ein Dropdown erwartet wird

---

**Erstellt**: 2025-06-27, 00:15 Uhr
**Für**: Fortsetzung der Multi-Branch Implementation