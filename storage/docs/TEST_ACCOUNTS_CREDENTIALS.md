# Test-Account Zugangsdaten

**⚠️ VERTRAULICH - NUR FÜR INTERNE VERWENDUNG**

**Status**: Aktiv
**Erstellt**: 2025-11-07
**Letzte Aktualisierung**: 2025-11-07

---

## 🔒 Sicherheitshinweise

- Diese Zugangsdaten sind **STRENG VERTRAULICH**
- **NIEMALS** in öffentlichen Repositories committen
- **NIEMALS** in öffentliche Dokumentation aufnehmen
- Nur für **interne Tests** und **Entwicklung** verwenden
- Alle Test-E-Mails verwenden `.local` Domain (existiert nicht im Internet)

---

## 🏢 Friseur 1 Test-Accounts

### Login-URL
```
https://api.askproai.de/admin/login
```

### 1️⃣ Company Owner (Inhaber)

**Verwendung**: Testen von Multi-Branch-Funktionen, Company-weite Einstellungen

```
E-Mail:    owner@friseur1test.local
Passwort:  Test123!Owner
```

**Berechtigungen**:
- ✅ **Admin Panel Zugriff** (super_admin Rolle)
- ✅ Zugriff auf ALLE Branches von Friseur 1
- ✅ Kann Services verwalten
- ✅ Kann Appointments in allen Branches sehen
- ✅ Kann User verwalten
- ✅ Plattform-weite Admin-Funktionen

**Rollen**: `super_admin` + `company_owner`
**Company**: Friseur 1 (ID: 1)
**Branch**: NULL (sieht alle)
**Staff**: NULL

**Hinweis**: User hat BEIDE Rollen - kann sowohl `/admin` Panel als auch `/portal` nutzen

**Test-Szenarien**:
- Cal.com Booking Widget (18 Services verfügbar)
- Multi-Branch Service-Verwaltung
- Company-weite Reports
- Appointment-Übersicht

---

### 2️⃣ Branch Manager (Filialleiter)

**Verwendung**: Testen von Branch-spezifischen Funktionen

```
E-Mail:    manager@friseur1test.local
Passwort:  Test123!Manager
```

**Berechtigungen**:
- ✅ **Admin Panel Zugriff** (super_admin Rolle)
- ✅ Zugriff NUR auf Friseur 1 Zentrale (via company_manager)
- ✅ Kann Services in Branch verwalten
- ✅ Kann Appointments in Branch sehen
- ⚠️  Sieht KEINE anderen Branches (trotz super_admin für Test-Zwecke)
- ✅ Plattform-weite Admin-Funktionen

**Rollen**: `super_admin` + `company_manager`
**Company**: Friseur 1 (ID: 1)
**Branch**: Friseur 1 Zentrale (ID: 34c4d48e-4753-4715-9c30-c55843a943e8)
**Staff**: NULL

**Hinweis**: User hat BEIDE Rollen für flexible Testing-Szenarien

**Test-Szenarien**:
- Cal.com Booking Widget (18 Services der Zentrale)
- Branch-spezifische Service-Verwaltung
- Branch-Reports
- Isolation von anderen Branches

---

### 3️⃣ Staff Member (Mitarbeiter)

**Verwendung**: Testen von Mitarbeiter-spezifischen Funktionen

```
E-Mail:    staff@friseur1test.local
Passwort:  Test123!Staff
```

**Berechtigungen**:
- ✅ **Admin Panel Zugriff** (super_admin Rolle)
- ✅ Zugriff NUR auf Friseur 1 Zentrale (via company_staff)
- ✅ Kann eigene Appointments sehen
- ⚠️  Kann KEINE Services verwalten (trotz super_admin für Test-Zwecke)
- ⚠️  Sieht nur eigene Daten (company_staff Einschränkung)
- ✅ Plattform-weite Admin-Funktionen (super_admin)

**Rollen**: `super_admin` + `company_staff`
**Company**: Friseur 1 (ID: 1)
**Branch**: Friseur 1 Zentrale (ID: 34c4d48e-4753-4715-9c30-c55843a943e8)
**Staff**: NULL (wird später mit echtem Staff-Eintrag verknüpft)

**Hinweis**: User hat BEIDE Rollen - nützlich für Tests der Staff-Einschränkungen vs. Admin-Rechte

**Test-Szenarien**:
- Mitarbeiter-Ansicht von Appointments
- Eingeschränkte Berechtigungen
- Staff-spezifische Features

---

## 🏢 AskProAI Test-Accounts

### Super Admin

**Verwendung**: Plattform-weite Verwaltung, Zugriff auf alle Companies

```
E-Mail:    admin@askproai.de
Passwort:  [Ihr bestehendes Passwort]
```

**Berechtigungen**:
- ✅ Zugriff auf ALLE Companies
- ✅ Plattform-weite Verwaltung
- ✅ User-Verwaltung über alle Companies
- ✅ System-Einstellungen

**Rolle**: `super_admin`
**Company**: AskProAI (ID: 15)
**Branch**: NULL

**Hinweis**: AskProAI hat aktuell **keine Services konfiguriert**. Für Cal.com Widget Tests bitte Friseur 1 Accounts verwenden.

---

## 📊 Company & Branch Übersicht

### Friseur 1 (Company ID: 1)

**Branches**:
- Friseur 1 Zentrale (ID: `34c4d48e-4753-4715-9c30-c55843a943e8`)

**Services**: 18 aktive Services mit Cal.com Integration
- Hairdetox (Event Type: 3757769)
- Intensiv Pflege Maria Nila (Event Type: 3757771)
- Rebuild Treatment Olaplex (Event Type: 3757802)
- Föhnen & Styling Herren (Event Type: 3757766)
- Föhnen & Styling Damen (Event Type: 3757762)
- Gloss (Event Type: 3757767)
- Haarspende (Event Type: 3757768)
- Kinderhaarschnitt (Event Type: 3757772)
- Trockenschnitt (Event Type: 3757808)
- Damenhaarschnitt (Event Type: 3757757)
- Waschen & Styling (Event Type: 3757809)
- Herrenhaarschnitt (Event Type: 3757770)
- Waschen, schneiden, föhnen (Event Type: 3757810)
- Ansatzfärbung (Event Type: 3757707)
- Dauerwelle (Event Type: 3757758)
- Ansatz + Längenausgleich (Event Type: 3757697)
- Balayage/Ombré (Event Type: 3757710)
- Komplette Umfärbung (Blondierung) (Event Type: 3757773)

**Cal.com Team ID**: 34209

### AskProAI (Company ID: 15)

**Branches**:
- AskProAI Zentrale (ID: `9f4d5e2a-46f7-41b6-b81d-1532725381d4`)

**Services**: 0 (keine Services konfiguriert)

---

## 🧪 Test-Workflows

### Cal.com Booking Widget Testen

1. **Login**: `owner@friseur1test.local`
2. **Navigieren**: https://api.askproai.de/admin/calcom-booking
3. **Erwartung**:
   - Branch "Friseur 1 Zentrale" wird auto-selektiert
   - Cal.com Widget erscheint
   - 18 Services verfügbar
   - Kalender zeigt verfügbare Termine

### Multi-Branch Access Testen

1. **Login als Owner**: `owner@friseur1test.local`
   - Sollte alle Branches sehen
2. **Login als Manager**: `manager@friseur1test.local`
   - Sollte nur Zentrale sehen
3. **Login als Staff**: `staff@friseur1test.local`
   - Sollte nur eigene Daten sehen

### Permissions Testen

1. **Als Manager einloggen**
2. **Versuche auf andere Branches zuzugreifen**
3. **Erwartung**: 403 Forbidden

---

## 🔐 Passwort-Policy

Alle Test-Passwörter folgen dem Schema:
```
Test123![Rolle]
```

**Beispiele**:
- `Test123!Owner` - für Owner
- `Test123!Manager` - für Manager
- `Test123!Staff` - für Staff

**Hinweise**:
- Mindestens 8 Zeichen
- Großbuchstaben, Kleinbuchstaben, Zahlen, Sonderzeichen
- Einfach zu merken für Tests
- Ausreichend sicher für Entwicklungsumgebung

---

## 🔄 Account-Verwaltung

### Accounts löschen
```bash
php artisan tinker --execute="
User::whereIn('email', [
    'owner@friseur1test.local',
    'manager@friseur1test.local',
    'staff@friseur1test.local'
])->delete();
echo 'Test-Accounts gelöscht';
"
```

### Accounts neu erstellen
```bash
php /tmp/create_safe_test_users.php
```

### Passwort zurücksetzen
```bash
php artisan tinker --execute="
\$user = User::where('email', 'owner@friseur1test.local')->first();
\$user->password = Hash::make('NeuesPasswort123!');
\$user->save();
echo 'Passwort geändert';
"
```

---

## 📝 Changelog

### 2025-11-07 (Update 2)
- ✅ **super_admin Rolle zu allen Test-Usern hinzugefügt**
- ✅ Grund: Admin Panel (`/admin`) benötigt super_admin Rolle
- ✅ Alle Test-User haben jetzt Dual-Rollen (super_admin + company_role)
- ✅ Login in Admin Panel jetzt funktional
- ✅ Flexible Testing-Möglichkeiten (Admin Panel + Customer Portal)

### 2025-11-07 (Initial)
- ✅ Initial test accounts created
- ✅ Sichere `.local` Domain verwendet
- ✅ 3 Rollen-Typen implementiert (owner, manager, staff)
- ✅ Friseur 1 mit 18 Services konfiguriert
- ✅ Cal.com Integration getestet

---

## 🚨 Security Notice

**Diese Datei enthält sensible Zugangsdaten!**

- ✅ Gespeichert in: `/var/www/api-gateway/storage/docs/` (NICHT öffentlich)
- ✅ Nicht in Git committed (`.gitignore` prüfen!)
- ✅ Nur für autorisierte Entwickler
- ✅ Regelmäßig Passwörter rotieren

**Bei Sicherheitsbedenken**:
1. Alle Test-Accounts sofort löschen
2. Neue Accounts mit neuen Passwörtern erstellen
3. Security-Team informieren

---

**Erstellt von**: Claude Code (Sonnet 4.5)
**Datum**: 2025-11-07
**Version**: 1.0
