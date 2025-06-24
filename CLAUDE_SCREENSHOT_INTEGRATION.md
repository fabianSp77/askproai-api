# Claude Screenshot Integration

## Automatische Screenshot-Erstellung für Claude

### Installation abgeschlossen ✅

1. **Browsershot Package installiert**: `spatie/browsershot` wurde erfolgreich installiert
2. **CaptureScreenshotJob**: Asynchrone Screenshot-Erstellung implementiert
3. **UI Capture Command**: Vervollständigt mit tatsächlicher Screenshot-Funktionalität
4. **Screenshot Service**: Zentraler Service für alle Screenshot-Operationen
5. **Auto-Screenshot Command**: Speziell für Claude-Integration

### Verwendung

#### 1. Einzelnen Screenshot erstellen (für Claude optimiert)
```bash
# Screenshot erstellen und auf Completion warten
php artisan claude:screenshot /admin/appointments --wait

# Mit Authentifizierung (noch nicht vollständig implementiert)
php artisan claude:screenshot /admin/appointments --wait --auth
```

#### 2. Batch Screenshots
```bash
# Alle wichtigen Routen erfassen
php artisan ui:capture --all

# Spezifische Route
php artisan ui:capture --route=/admin/customers
```

#### 3. Geplante Screenshots (für Monitoring)
```bash
# Einmalig ausführen
php artisan screenshots:scheduled

# In Kernel.php für stündliche Ausführung:
$schedule->command('screenshots:scheduled')->hourly();
```

### Claude-Workflow

1. **Screenshot anfordern**:
   ```
   "Bitte erstelle einen Screenshot von /admin/appointments"
   ```

2. **Automatische Ausführung**:
   ```bash
   php artisan claude:screenshot /admin/appointments --wait
   ```

3. **Pfad erhalten**:
   ```
   Screenshot location: /var/www/api-gateway/storage/app/screenshots/claude/claude_auto_admin_appointments_20250622_143022.png
   ```

4. **Mit Read Tool öffnen**:
   Claude kann dann automatisch den Screenshot mit dem Read Tool öffnen und analysieren.

### Wichtige Hinweise

1. **Chrome/Chromium Installation erforderlich**:
   ```bash
   # Debian/Ubuntu
   sudo apt-get install chromium-browser
   
   # Oder Chrome
   wget -q -O - https://dl-ssl.google.com/linux/linux_signing_key.pub | sudo apt-key add -
   sudo sh -c 'echo "deb [arch=amd64] http://dl.google.com/linux/chrome/deb/ stable main" >> /etc/apt/sources.list.d/google.list'
   sudo apt-get update
   sudo apt-get install google-chrome-stable
   ```

2. **Für Server ohne Display**:
   ```bash
   # Xvfb für headless operation
   sudo apt-get install xvfb
   ```

3. **Authentifizierung**:
   - Aktuell noch nicht vollständig implementiert
   - Benötigt Service-Account oder API-Token
   - Kann über Cookies oder Basic Auth erfolgen

### Nächste Schritte

1. **Chrome installieren** (falls noch nicht vorhanden)
2. **Test ausführen**: `php artisan claude:screenshot /admin --wait`
3. **Authentifizierung implementieren** für geschützte Routen

### Troubleshooting

**Problem**: "The command "node" failed
- **Lösung**: Node.js installieren: `sudo apt-get install nodejs`

**Problem**: "Chrome not found"
- **Lösung**: Chrome/Chromium installieren (siehe oben)

**Problem**: Screenshots sind schwarz
- **Lösung**: `--wait` Flag verwenden oder `waitUntilNetworkIdle` Option

### API für Programmatische Nutzung

```php
use App\Services\ScreenshotService;

$screenshotService = app(ScreenshotService::class);

// Einzelner Screenshot
$path = $screenshotService->capture('/admin/appointments', [
    'sync' => true,
    'fullPage' => true
]);

// Batch Screenshots
$paths = $screenshotService->captureBatch([
    'dashboard' => '/admin',
    'appointments' => '/admin/appointments',
    'customers' => '/admin/customers'
]);
```