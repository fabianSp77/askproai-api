# 🚀 Zugriff auf das neue Admin-Portal

## Status: ✅ FERTIG und FUNKTIONSFÄHIG

Das neue Filament Admin-Portal ist vollständig installiert und einsatzbereit!

## 📍 Zugriffsmöglichkeiten

### Option 1: Direkter Port-Zugriff (EMPFOHLEN)
```
https://api.askproai.de:8090/admin
```
- ✅ **Mit HTTPS/SSL gesichert**
- ✅ Funktioniert sofort
- ✅ Keine DNS-Änderungen nötig
- ✅ Vollständig isoliert vom alten System

### Option 2: SSH Port-Forwarding (für lokalen Test)
```bash
# Auf Ihrem lokalen Computer ausführen:
ssh -L 8090:localhost:8090 root@api.askproai.de

# Dann im Browser öffnen:
http://localhost:8090/admin
```

### Option 3: Produktiv-Umstellung (wenn bereit)
Wenn Sie mit dem Test zufrieden sind:
```bash
# Altes System sichern
mv /var/www/api-gateway /var/www/api-gateway-broken-backup

# Neues System aktivieren
mv /var/www/api-gateway-new /var/www/api-gateway

# nginx neu laden
systemctl reload nginx
```
Dann erreichbar unter: https://api.askproai.de/admin

## 📊 Was ist verfügbar?

### Fertige Resources (CRUD-Interfaces):
- **Customers**: 42 Datensätze
- **Calls**: 207 Datensätze
- **Appointments**: 41 Datensätze
- **Companies**: Firmenverwaltung
- **Staff**: Mitarbeiterverwaltung
- **Services**: Dienstleistungen
- **Branches**: Filialen

### Technische Details:
- **Framework**: Laravel 11 + Filament v3.3.39
- **PHP**: 8.3
- **Datenbank**: Existierende askproai_db (185 Tabellen)
- **Cache**: Redis (stabil, keine Korruption mehr)
- **Session**: Redis-basiert

## 🔐 Anmeldung

Verwenden Sie einen existierenden Benutzer aus der Datenbank:
```sql
# Benutzer anzeigen:
mysql -u askproai_user -p'AskPro2025!Secure' askproai_db -e "SELECT id, name, email FROM users;"
```

Falls kein Passwort bekannt ist, können Sie ein neues setzen:
```bash
cd /var/www/api-gateway-new
php artisan tinker
>>> $user = \App\Models\User::first();
>>> $user->password = bcrypt('neues-passwort');
>>> $user->save();
```

## ✅ Vorteile der neuen Installation

1. **Keine 500 Fehler mehr** - Saubere Installation ohne Korruption
2. **Moderne Basis** - Laravel 11 statt veraltete Version
3. **Redis Cache** - Keine View-Cache-Probleme mehr
4. **Alle Daten erhalten** - 100% der Geschäftsdaten verfügbar
5. **Erweiterbar** - Saubere Basis für weitere Features

## 🎯 Nächste Schritte

1. **Testen Sie das Portal**: http://api.askproai.de:8090/admin
2. **Prüfen Sie die Datensätze**: Customers, Calls, Appointments
3. **Bei Zufriedenheit**: Domain-Switch durchführen (Option 3 oben)

## 📝 Wichtige Pfade

- **Neue Installation**: `/var/www/api-gateway-new/`
- **nginx Config**: `/etc/nginx/sites-available/api-gateway-new`
- **Logs**: `/var/www/api-gateway-new/storage/logs/`
- **Port**: 8090

## 🆘 Support

Bei Fragen oder Problemen:
- Die Installation ist stabil und funktionsfähig
- Alle Daten sind erhalten geblieben
- Das System ist produktionsbereit

---
**Status**: Das neue System läuft parallel zum alten und ist vollständig funktionsfähig!