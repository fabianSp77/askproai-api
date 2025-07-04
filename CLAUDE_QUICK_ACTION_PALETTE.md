# 🎮 Quick Action Command Palette für CLAUDE.md

## Konzept
Eine interaktive Command Palette direkt in CLAUDE.md, die häufig benötigte Befehle kategorisiert und copy-paste-ready bereitstellt.

## 🚀 Implementierung

### Version 1: Statische Markdown-Variante
```markdown
## 🎮 Quick Commands

### 🚨 Emergency Actions
```bash
# System komplett neu starten
sudo systemctl restart php8.3-fpm && \
sudo systemctl restart nginx && \
php artisan horizon:terminate && \
php artisan optimize:clear

# Alle Caches leeren
php artisan optimize:clear && \
php artisan config:clear && \
php artisan cache:clear && \
php artisan route:clear && \
php artisan view:clear

# Notfall-Backup
php artisan askproai:backup --type=critical --encrypt
```

### 🔍 Diagnose & Debug
```bash
# Retell Status prüfen
php retell-health-check.php

# System-Diagnose
php artisan askproai:diagnose --full

# Queue Status
php artisan horizon:status && \
php artisan queue:monitor

# Letzte Fehler anzeigen
tail -n 50 storage/logs/laravel.log | grep ERROR
```

### 🛠️ Häufige Fixes
```bash
# Retell Webhook Fix
php artisan retell:verify-webhook && \
php artisan retell:sync-agents

# Database Connection Fix
php artisan db:show && \
php artisan migrate:status

# Permission Fix
chmod -R 775 storage bootstrap/cache && \
chown -R www-data:www-data storage bootstrap/cache
```

### 📊 Monitoring
```bash
# Live Logs
tail -f storage/logs/laravel.log

# Performance Check
php artisan performance:analyze

# API Health
curl -s https://api.askproai.de/api/health | jq

# Queue Monitoring
watch -n 5 'php artisan queue:monitor'
```
```

### Version 2: Interaktive HTML/JS Variante
```html
<!-- In CLAUDE.md einbetten -->
<div id="command-palette" style="background: #1a1a1a; padding: 20px; border-radius: 8px; margin: 20px 0;">
  <input 
    type="text" 
    id="cmd-search" 
    placeholder="🔍 Suche Commands... (z.B. 'retell', 'fix', 'deploy')"
    style="width: 100%; padding: 10px; background: #2a2a2a; color: white; border: 1px solid #444; border-radius: 4px;"
  >
  
  <div id="cmd-results" style="margin-top: 10px;">
    <!-- Dynamisch gefüllt -->
  </div>
</div>

<script>
const commands = {
  emergency: {
    icon: "🚨",
    items: [
      {
        name: "Full System Restart",
        cmd: "sudo systemctl restart php8.3-fpm && sudo systemctl restart nginx && php artisan horizon:terminate",
        desc: "Startet alle Services neu"
      },
      {
        name: "Clear All Caches", 
        cmd: "php artisan optimize:clear",
        desc: "Leert alle Laravel Caches"
      }
    ]
  },
  retell: {
    icon: "📞",
    items: [
      {
        name: "Retell Health Check",
        cmd: "php retell-health-check.php",
        desc: "Prüft Retell Integration"
      },
      {
        name: "Import Calls Manually",
        cmd: "php import-retell-calls-manual.php",
        desc: "Importiert fehlende Anrufe"
      }
    ]
  },
  database: {
    icon: "🗄️",
    items: [
      {
        name: "Show DB Status",
        cmd: "php artisan db:show",
        desc: "Zeigt Datenbankverbindung"
      },
      {
        name: "Run Migrations",
        cmd: "php artisan migrate --force",
        desc: "Führt Migrationen aus"
      }
    ]
  }
};

// Command Palette Logic
document.getElementById('cmd-search').addEventListener('input', (e) => {
  const query = e.target.value.toLowerCase();
  const results = document.getElementById('cmd-results');
  results.innerHTML = '';
  
  Object.entries(commands).forEach(([category, data]) => {
    const filtered = data.items.filter(item => 
      item.name.toLowerCase().includes(query) || 
      item.cmd.toLowerCase().includes(query) ||
      category.includes(query)
    );
    
    if (filtered.length > 0) {
      results.innerHTML += `
        <div style="margin-top: 15px;">
          <h4 style="color: #888; margin: 5px 0;">${data.icon} ${category.toUpperCase()}</h4>
          ${filtered.map(item => `
            <div style="background: #2a2a2a; padding: 10px; margin: 5px 0; border-radius: 4px; cursor: pointer;"
                 onclick="copyCommand('${item.cmd.replace(/'/g, "\\'")}')"
                 onmouseover="this.style.background='#3a3a3a'"
                 onmouseout="this.style.background='#2a2a2a'">
              <div style="color: #fff; font-weight: bold;">${item.name}</div>
              <code style="color: #4a9eff; font-size: 12px;">${item.cmd}</code>
              <div style="color: #666; font-size: 11px; margin-top: 3px;">${item.desc}</div>
            </div>
          `).join('')}
        </div>
      `;
    }
  });
  
  if (results.innerHTML === '') {
    results.innerHTML = '<div style="color: #666; padding: 20px; text-align: center;">Keine Commands gefunden</div>';
  }
});

function copyCommand(cmd) {
  navigator.clipboard.writeText(cmd);
  // Visual feedback
  event.target.style.background = '#0f7938';
  setTimeout(() => event.target.style.background = '#2a2a2a', 200);
}
</script>
```

### Version 3: Terminal-Style Command Runner
```markdown
## 🖥️ Command Runner

### Quick Actions (Klick zum Ausführen)

<details open>
<summary><b>🚨 Emergency</b></summary>

| Action | Command | Run |
|--------|---------|-----|
| System Restart | `systemctl restart all` | [▶️ Run](terminal://run?cmd=sudo%20systemctl%20restart%20php8.3-fpm) |
| Clear Caches | `artisan optimize:clear` | [▶️ Run](terminal://run?cmd=php%20artisan%20optimize%3Aclear) |
| Emergency Backup | `artisan backup --critical` | [▶️ Run](terminal://run?cmd=php%20artisan%20askproai%3Abackup%20--type%3Dcritical) |

</details>

<details>
<summary><b>📞 Retell</b></summary>

| Action | Command | Run |
|--------|---------|-----|
| Health Check | `retell-health-check.php` | [▶️ Run](terminal://run?cmd=php%20retell-health-check.php) |
| Sync Agents | `artisan retell:sync` | [▶️ Run](terminal://run?cmd=php%20artisan%20retell%3Async-agents) |
| View Logs | `tail retell logs` | [▶️ Run](terminal://run?cmd=tail%20-f%20storage%2Flogs%2Flaravel.log%20%7C%20grep%20-i%20retell) |

</details>
```

### Version 4: Alias-basierte Lösung
```bash
# In .bashrc oder .zshrc hinzufügen
alias aske='php artisan askproai:emergency'
alias askd='php artisan askproai:diagnose'
alias askr='php retell-health-check.php'
alias askh='php artisan horizon:status'
alias askl='tail -f storage/logs/laravel.log'

# Dann in CLAUDE.md:
## 🚀 Quick Aliases

Nach Installation der Aliases (siehe .bashrc):
- `aske` - Emergency actions
- `askd` - Diagnose system  
- `askr` - Retell health check
- `askh` - Horizon status
- `askl` - Live logs
```

## 🎯 Empfohlene Implementierung

### Phase 1: Sofort umsetzbar
1. **Statische Command-Sammlung** in CLAUDE.md
2. **Kategorisierte Befehle** mit Copy-Button-Feeling
3. **Visuelle Trennung** durch Farben/Icons

### Phase 2: Erweitert
1. **HTML/JS Command Palette** für Suche
2. **Keyboard Shortcuts** (Strg+K für Palette)
3. **Command History** im LocalStorage

### Phase 3: Vollintegration  
1. **Laravel Command** zur Generierung
2. **API Endpoint** für Command-Ausführung
3. **Real-time Feedback** über WebSockets

## 📋 Command-Kategorien

### Muss-haben:
- 🚨 **Emergency** - Kritische Fixes
- 🔍 **Diagnose** - Status & Debugging  
- 📞 **Retell** - Telefonie-spezifisch
- 🗄️ **Database** - DB-Operationen
- 🚀 **Deploy** - Deployment-Befehle

### Nice-to-have:
- 📊 **Analytics** - Reports & Metriken
- 🧪 **Testing** - Test-Befehle
- 🔧 **Maintenance** - Wartung
- 📦 **Backup** - Sicherung
- 🔐 **Security** - Sicherheit

## 🌟 Beispiel-Integration

```markdown
## 🎮 Quick Commands

> 💡 **Tipp**: Nutze `Strg+F` um Commands zu suchen

### 🚀 Top 5 Commands

```bash
# 1. System Health Check
php artisan askproai:diagnose --full

# 2. Retell Status  
php retell-health-check.php

# 3. Clear Everything
php artisan optimize:clear

# 4. View Logs
tail -f storage/logs/laravel.log

# 5. Queue Status
php artisan horizon:status
```

### 📁 Alle Commands

<details>
<summary>🚨 Emergency (5 commands)</summary>

```bash
# Full system restart
sudo systemctl restart php8.3-fpm nginx
php artisan horizon:terminate
php artisan optimize:clear

# Database repair
php artisan migrate:fresh --seed
php artisan db:seed --class=ProductionSeeder  

# more...
```

</details>

<details>
<summary>📞 Retell (8 commands)</summary>

```bash
# Check integration
php retell-health-check.php

# Sync agents
php artisan retell:sync-agents

# more...
```

</details>
```

Diese Struktur macht Commands sofort auffindbar und nutzbar!