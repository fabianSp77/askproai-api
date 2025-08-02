# Admin Portal Tooltips Implementation Guide

## Overview
This guide explains how tooltips have been implemented in the AskProAI Admin Portal and how to use them in your resources and pages.

## What's Been Implemented

### 1. **Language File with Comprehensive Tooltips**
- Location: `/lang/de/admin.php`
- Contains 80+ pre-defined tooltip texts in German
- Organized by action categories (billing, system, data, export/import, etc.)

### 2. **HasTooltips Trait**
- Location: `/app/Filament/Admin/Traits/HasTooltips.php`
- Provides automatic tooltip application based on action names
- Supports table actions, bulk actions, form actions, and header actions

### 3. **JavaScript Tooltip System**
- Location: `/resources/js/admin-tooltips.js`
- Enhances Filament's built-in tooltips
- Adds touch device support (long press to show)
- Works with dynamically loaded content via Livewire

### 4. **CSS Enhancements**
- Location: `/resources/css/filament/admin/tooltips.css`
- Improves tooltip visibility and z-index
- Mobile-friendly adjustments
- Dark mode support

## How to Use Tooltips in Your Resources

### Basic Implementation

```php
use App\Filament\Admin\Traits\HasTooltips;

class YourResource extends Resource
{
    use HasTooltips;
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([/* ... */])
            // Wrap actions with tooltip helper
            ->actions(static::applyTableActionTooltips([
                Tables\Actions\ViewAction::make(),  // Gets automatic tooltip
                Tables\Actions\EditAction::make(),  // Gets automatic tooltip
                Tables\Actions\DeleteAction::make(), // Gets automatic tooltip
                
                // Custom action with automatic tooltip based on name
                Tables\Actions\Action::make('refresh')
                    ->label('Aktualisieren')
                    ->icon('heroicon-o-arrow-path'),
                    // Tooltip automatically applied: "Aktualisiert die Daten aus der Datenbank"
            ]))
            // Wrap bulk actions
            ->bulkActions(static::applyBulkActionTooltips([
                Tables\Actions\DeleteBulkAction::make(), // Gets automatic tooltip
                
                // Custom bulk action
                Tables\Actions\BulkAction::make('mark_as_non_billable')
                    ->label('Als nicht abrechenbar markieren'),
                    // Tooltip automatically applied based on action name
            ]));
    }
}
```

### Page Actions

```php
class ListYourRecords extends ListRecords
{
    use HasTooltips;
    
    protected function getHeaderActions(): array
    {
        return static::applyFormActionTooltips([
            Actions\CreateAction::make(),
            
            Actions\Action::make('export')
                ->label('Export')
                ->icon('heroicon-o-arrow-down-tray'),
                // Automatic tooltip applied
        ]);
    }
}
```

### Custom Tooltips

You can always override automatic tooltips:

```php
Tables\Actions\Action::make('custom_action')
    ->label('Special Action')
    ->tooltip('Your custom tooltip text here')
    ->icon('heroicon-o-sparkles')
```

### Using Tooltip Helper

```php
// In a resource or page with HasTooltips trait
Tables\Actions\Action::make('finalize')
    ->label('Finalisieren')
    ->tooltip(static::tooltip('finalize_invoice')) // Uses language file
    ->icon('heroicon-o-check')
```

## Automatic Tooltip Mapping

The trait automatically maps common action names to tooltip keys:

| Action Name | Tooltip Key | German Text |
|------------|-------------|-------------|
| `create` | `create_entry` | "Erstellt einen neuen Eintrag" |
| `edit` | `edit_entry` | "Öffnet das Bearbeitungsformular für diesen Eintrag" |
| `delete` | `delete_entry` | "Löscht diesen Eintrag dauerhaft..." |
| `refresh` | `refresh_data` | "Aktualisiert die Daten aus der Datenbank..." |
| `export` | `export_csv` | "Exportiert die gefilterten Daten als CSV-Datei..." |
| `mark_as_non_billable` | `mark_non_billable` | "Markiert diesen Eintrag als nicht abrechenbar..." |
| `create_credit_note` | `create_credit_note` | "Erstellt eine Gutschrift für diesen Kunden..." |
| `preflight_check` | `preflight_check` | "Führt eine vollständige Systemprüfung durch..." |

See `/lang/de/admin.php` for the complete list.

## Touch Device Support

On touch devices (tablets, phones):
- **Long press (500ms)** on any element with a tooltip to show it
- Tooltip automatically hides after 3 seconds
- Works with icon-only buttons

## JavaScript API

The tooltip system is available globally:

```javascript
// Add tooltip to any element
element.setAttribute('data-tooltip', 'Your tooltip text');

// The system will automatically detect and enhance it
```

## Examples in Existing Resources

### CallResource
- Uses tooltips for "Als nicht abrechenbar markieren" bulk action
- Uses tooltips for "Gutschrift erstellen" bulk action
- View and Share actions have tooltips

### InvoiceResource
- Preview action: "Zeigt eine Vorschau der Rechnung im PDF-Format"
- Finalize action: "Finalisiert die Rechnung. Nach der Finalisierung..."
- Download PDF action: "Lädt die Rechnung als PDF herunter"

### SystemMonitoringDashboard
- Refresh action: "Aktualisiert die Daten aus der Datenbank"
- Export action: "Exportiert die gefilterten Daten als CSV-Datei"
- Preflight Check: "Führt eine vollständige Systemprüfung durch..."

## Best Practices

1. **Use the Trait**: Always use `HasTooltips` trait in resources and pages
2. **Wrap Actions**: Use the helper methods to wrap your actions arrays
3. **Consistent Naming**: Use consistent action names to get automatic tooltips
4. **Language File**: Add new tooltips to the language file for reusability
5. **Keep it Brief**: Tooltips should be helpful but concise
6. **Test Touch Devices**: Verify tooltips work on mobile devices

## Adding New Tooltips

1. Add to language file:
```php
// In /lang/de/admin.php
'tooltips' => [
    // ...
    'your_new_action' => 'Beschreibung der Aktion',
],
```

2. Use in your resource:
```php
Tables\Actions\Action::make('your_new_action')
    ->label('Neue Aktion')
    // Tooltip automatically applied from language file
```

## Troubleshooting

### Tooltips Not Showing
1. Ensure you've used the `HasTooltips` trait
2. Wrap actions with the appropriate helper method
3. Check browser console for JavaScript errors
4. Verify CSS/JS assets are compiled: `npm run build`

### Touch Devices
- Increase touch hold duration if needed in `/resources/js/admin-tooltips.js`
- Check z-index conflicts with other UI elements

### Dark Mode
- Tooltips automatically adapt to dark mode
- Custom styles in `/resources/css/filament/admin/tooltips.css`