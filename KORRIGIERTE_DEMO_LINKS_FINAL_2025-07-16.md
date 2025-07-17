# 🔗 KORRIGIERTE DEMO-LINKS (Finale Version)

**WICHTIG: Company ID 1 existiert, ist aber nicht die Demo-Company!**
**Verwende TechPartner GmbH (ID 309) für die Demo!**

## 📌 HAUPT-DEMO-FLOW MIT KORREKTEN IDs:

### 1. Admin Login
```
https://api.askproai.de/admin
```
Login: demo@askproai.de / demo123

### 2. Multi-Company Dashboard (HAUPTFEATURE!)
```
https://api.askproai.de/admin/business-portal-admin
```
→ Hier siehst du alle verwalteten Kunden!

### 3. Company Liste
```
https://api.askproai.de/admin/companies
```
→ Filtere nach "TechPartner" oder scrolle zu ID 309

### 4. TechPartner bearbeiten (KORREKTE ID!)
```
https://api.askproai.de/admin/companies/309/edit
```
→ Das ist der Reseller mit 20% Provision!

### 5. Child Companies (Kunden von TechPartner)
- **Zahnarztpraxis Dr. Schmidt** (ID: 310)
  ```
  https://api.askproai.de/admin/companies/310/edit
  ```

- **Physiotherapie Bewegung Plus** (ID: 311)
  ```
  https://api.askproai.de/admin/companies/311/edit
  ```

- **Autohaus Müller GmbH** (ID: 312)
  ```
  https://api.askproai.de/admin/companies/312/edit
  ```

### 6. Prepaid Guthaben Übersicht
```
https://api.askproai.de/admin/prepaid-balances
```
→ Zeigt Guthaben aller Kunden

### 7. Anrufe Übersicht
```
https://api.askproai.de/admin/calls
```
→ 270 Anrufe insgesamt!

### 8. Heutige Anrufe filtern
```
https://api.askproai.de/admin/calls?tableFilters[created_at][created_at]=today
```
→ Zeigt nur heutige Anrufe

---

## 🔄 BUSINESS PORTAL ZUGANG:

### Portal Login
```
https://api.askproai.de/business
```

### Kunden-Logins:
- **Dr. Schmidt**: admin@dr-schmidt.de / demo123
- **Müller**: admin@kanzlei-mueller.de / demo123
- **Bella**: admin@salon-bella.de / demo123

---

## 🎯 DEMO-SCRIPT MIT KORREKTEN LINKS:

### Phase 1: Admin Übersicht
1. Login: https://api.askproai.de/admin
2. Dashboard zeigen (Multi-Company Widget!)
3. Zu Kundenverwaltung: https://api.askproai.de/admin/business-portal-admin

### Phase 2: Reseller Details
4. TechPartner öffnen: https://api.askproai.de/admin/companies/309/edit
5. Zeige: 20% Provision, White-Label Settings

### Phase 3: Kunden-Management
6. Dr. Schmidt öffnen: https://api.askproai.de/admin/companies/310/edit
7. Guthaben zeigen: https://api.askproai.de/admin/prepaid-balances

### Phase 4: Aktivität
8. Alle Anrufe: https://api.askproai.de/admin/calls
9. "270 Anrufe, 68.9% außerhalb Geschäftszeiten!"

### Phase 5: Kunden-Portal
10. Neuer Tab: https://api.askproai.de/business
11. Login als Dr. Schmidt zeigen

---

## 💡 BACKUP-STRATEGIE:

Falls `/companies/309/edit` auch 500 Error gibt:
1. Bleibe bei der Listen-Ansicht: `/admin/companies`
2. Zeige die Tabelle mit allen Companies
3. Sage: "Hier sehen Sie die hierarchische Struktur"
4. Fokussiere auf Multi-Company Dashboard stattdessen

---

## 🚨 NOTFALL-DEMO (ohne Edit-Pages):

Wenn Edit-Pages nicht funktionieren, nutze nur:
1. `/admin` - Dashboard
2. `/admin/business-portal-admin` - Multi-Company
3. `/admin/companies` - Liste
4. `/admin/calls` - Anrufe
5. `/admin/prepaid-balances` - Guthaben

Diese Seiten reichen für eine überzeugende Demo!

---

**REMEMBER: Die Story verkauft, nicht perfekte Edit-Forms! 🚀**