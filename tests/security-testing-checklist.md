# Business Portal Security Testing Checklist

## 🔐 Übersicht

Diese Sicherheits-Checkliste stellt sicher, dass das Business Portal gegen die häufigsten Sicherheitsbedrohungen geschützt ist und den aktuellen Sicherheitsstandards entspricht.

---

## 🎯 Security Testing Scope

### Prioritäten
- **P0**: Kritische Sicherheitslücken (sofort beheben)
- **P1**: Wichtige Sicherheitsprobleme (vor Release beheben)
- **P2**: Mittlere Risiken (zeitnah beheben)
- **P3**: Geringe Risiken (bei Gelegenheit beheben)

### OWASP Top 10 Coverage
1. ✅ Injection
2. ✅ Broken Authentication
3. ✅ Sensitive Data Exposure
4. ✅ XML External Entities (XXE)
5. ✅ Broken Access Control
6. ✅ Security Misconfiguration
7. ✅ Cross-Site Scripting (XSS)
8. ✅ Insecure Deserialization
9. ✅ Using Components with Known Vulnerabilities
10. ✅ Insufficient Logging & Monitoring

---

## 🛡️ Authentication & Session Management

### AUTH-001: Login Security
**Priorität**: P0  
**Bereich**: Login-Prozess

| Test | Beschreibung | Status | Anmerkungen |
|------|--------------|---------|-------------|
| [ ] | Brute-Force-Schutz (Rate Limiting) | | Max 5 Versuche/Minute |
| [ ] | Account Lockout nach X Fehlversuchen | | 10 Versuche = 15 Min Sperre |
| [ ] | Sichere Passwort-Policy | | Min 8 Zeichen, Mix |
| [ ] | Timing-Attack-Resistenz | | Konstante Response-Zeit |
| [ ] | Username Enumeration verhindert | | Gleiche Meldung |
| [ ] | CAPTCHA nach mehreren Fehlversuchen | | Nach 3 Versuchen |

### AUTH-002: Session Management
**Priorität**: P0  
**Bereich**: Session-Sicherheit

| Test | Beschreibung | Status | Anmerkungen |
|------|--------------|---------|-------------|
| [ ] | Session-Fixation verhindert | | Neue Session-ID nach Login |
| [ ] | Session-Timeout implementiert | | 30 Min Inaktivität |
| [ ] | Sichere Session-Cookies | | httpOnly, secure, sameSite |
| [ ] | Session-Invalidierung bei Logout | | Server-seitig |
| [ ] | Concurrent Session Control | | Max 3 aktive Sessions |
| [ ] | Session-ID-Rotation | | Bei Privilege-Änderung |

### AUTH-003: Password Management
**Priorität**: P1  
**Bereich**: Passwort-Sicherheit

| Test | Beschreibung | Status | Anmerkungen |
|------|--------------|---------|-------------|
| [ ] | Sichere Passwort-Speicherung | | bcrypt/argon2 |
| [ ] | Passwort-Reset-Token-Sicherheit | | 1h Gültigkeit |
| [ ] | Keine Passwörter in Logs | | Maskierung aktiv |
| [ ] | Passwort-Historie | | Letzte 5 nicht wiederverwendbar |
| [ ] | Komplexitäts-Anforderungen | | Enforce via Frontend/Backend |
| [ ] | Kompromittierte Passwörter prüfen | | HaveIBeenPwned API |

---

## 🔒 Access Control & Authorization

### AC-001: Role-Based Access Control
**Priorität**: P0  
**Bereich**: Berechtigungen

| Test | Beschreibung | Status | Anmerkungen |
|------|--------------|---------|-------------|
| [ ] | Horizontale Privilege Escalation | | Keine fremden Daten |
| [ ] | Vertikale Privilege Escalation | | Keine Admin-Funktionen |
| [ ] | Direct Object Reference (IDOR) | | UUID statt ID |
| [ ] | Forced Browsing verhindert | | 403 bei fehlender Berechtigung |
| [ ] | API-Endpoint-Schutz | | Middleware aktiv |
| [ ] | File Upload Restrictions | | Whitelist Dateitypen |

### AC-002: Multi-Tenancy Security
**Priorität**: P0  
**Bereich**: Mandanten-Trennung

| Test | Beschreibung | Status | Anmerkungen |
|------|--------------|---------|-------------|
| [ ] | Tenant-Isolation funktioniert | | Global Scope aktiv |
| [ ] | Cross-Tenant-Zugriff verhindert | | Company-ID-Check |
| [ ] | Admin kann nicht alle Tenants sehen | | Nur Super-Admin |
| [ ] | API filtert nach Tenant | | Automatisch |
| [ ] | Subdomain-Isolation | | Cookies domain-spezifisch |

---

## 💉 Injection Attacks

### INJ-001: SQL Injection
**Priorität**: P0  
**Bereich**: Datenbank-Queries

| Test | Beschreibung | Status | Anmerkungen |
|------|--------------|---------|-------------|
| [ ] | Login-Form SQL-Injection | | ' OR 1=1-- |
| [ ] | Search-Parameter SQL-Injection | | UNION SELECT |
| [ ] | Order-By SQL-Injection | | ; DROP TABLE |
| [ ] | Prepared Statements verwendet | | Eloquent ORM |
| [ ] | Raw Queries escaped | | DB::raw() vermeiden |
| [ ] | Stored Procedures sicher | | Parameter binding |

### INJ-002: Command Injection
**Priorität**: P0  
**Bereich**: System-Commands

| Test | Beschreibung | Status | Anmerkungen |
|------|--------------|---------|-------------|
| [ ] | File Upload Command Injection | | ; ls -la |
| [ ] | PDF Generation sicher | | Escapeshellargs() |
| [ ] | Email-Versand sicher | | Keine Shell-Commands |
| [ ] | Externe API-Calls validiert | | Input-Validierung |

### INJ-003: LDAP/XML/XPath Injection
**Priorität**: P1  
**Bereich**: Spezielle Injections

| Test | Beschreibung | Status | Anmerkungen |
|------|--------------|---------|-------------|
| [ ] | XML-Parser sicher konfiguriert | | XXE deaktiviert |
| [ ] | LDAP-Queries escaped | | Falls verwendet |
| [ ] | XPath-Injection verhindert | | Input-Validierung |
| [ ] | Template-Injection verhindert | | Blade escaped |

---

## 🎭 Cross-Site Scripting (XSS)

### XSS-001: Reflected XSS
**Priorität**: P0  
**Bereich**: Input/Output

| Test | Beschreibung | Status | Anmerkungen |
|------|--------------|---------|-------------|
| [ ] | Search-Parameter XSS | | <script>alert('XSS')</script> |
| [ ] | Error-Messages escaped | | HTML-Entities |
| [ ] | URL-Parameter validiert | | Whitelist |
| [ ] | Form-Inputs escaped | | {{ }} statt {!! !!} |
| [ ] | JSON-Response escaped | | json_encode sicher |

### XSS-002: Stored XSS
**Priorität**: P0  
**Bereich**: Persistente Daten

| Test | Beschreibung | Status | Anmerkungen |
|------|--------------|---------|-------------|
| [ ] | Kunden-Namen XSS | | In allen Ansichten |
| [ ] | Notizen/Kommentare XSS | | Rich-Text sicher |
| [ ] | File-Upload-Namen XSS | | Dateinamen escaped |
| [ ] | API-Response escaped | | Frontend-Validierung |
| [ ] | Email-Templates sicher | | HTML Purifier |

### XSS-003: DOM-based XSS
**Priorität**: P1  
**Bereich**: JavaScript

| Test | Beschreibung | Status | Anmerkungen |
|------|--------------|---------|-------------|
| [ ] | Alpine.js Expressions sicher | | x-text statt x-html |
| [ ] | JavaScript-Variables escaped | | JSON.parse() |
| [ ] | Event-Handler validiert | | Keine inline onclick |
| [ ] | Location-Hash XSS | | Fragment validiert |
| [ ] | PostMessage validiert | | Origin-Check |

---

## 🔐 Cryptography & Data Protection

### CRYPTO-001: Encryption at Rest
**Priorität**: P0  
**Bereich**: Datenverschlüsselung

| Test | Beschreibung | Status | Anmerkungen |
|------|--------------|---------|-------------|
| [ ] | API-Keys verschlüsselt | | AES-256 |
| [ ] | Sensitive Kundendaten verschlüsselt | | PII-Felder |
| [ ] | Backup-Verschlüsselung | | Encrypted backups |
| [ ] | Temporäre Dateien sicher | | Automatic cleanup |
| [ ] | Database-Encryption | | Transparent encryption |

### CRYPTO-002: Encryption in Transit
**Priorität**: P0  
**Bereich**: Übertragungssicherheit

| Test | Beschreibung | Status | Anmerkungen |
|------|--------------|---------|-------------|
| [ ] | HTTPS enforced | | HSTS Header |
| [ ] | TLS 1.2+ only | | Alte Versionen deaktiviert |
| [ ] | Strong Cipher Suites | | A+ Rating |
| [ ] | Certificate Pinning | | Mobile Apps |
| [ ] | API-Communication verschlüsselt | | Webhook HTTPS |

### CRYPTO-003: Key Management
**Priorität**: P1  
**Bereich**: Schlüsselverwaltung

| Test | Beschreibung | Status | Anmerkungen |
|------|--------------|---------|-------------|
| [ ] | Keine hardcoded Keys | | Env-Variables |
| [ ] | Key-Rotation möglich | | Quarterly |
| [ ] | Sichere Key-Speicherung | | HSM/Vault |
| [ ] | Separate Keys per Environment | | Dev/Staging/Prod |
| [ ] | Key-Access-Logging | | Audit Trail |

---

## 🚫 Cross-Site Request Forgery (CSRF)

### CSRF-001: Token Validation
**Priorität**: P0  
**Bereich**: CSRF-Schutz

| Test | Beschreibung | Status | Anmerkungen |
|------|--------------|---------|-------------|
| [ ] | CSRF-Token in allen Forms | | {{ csrf_field() }} |
| [ ] | AJAX-Requests mit Token | | X-CSRF-TOKEN Header |
| [ ] | Token-Rotation | | Per Session |
| [ ] | Double-Submit-Cookie | | Additional protection |
| [ ] | SameSite-Cookie-Attribute | | Strict/Lax |

---

## 📁 File Upload Security

### FILE-001: Upload Validation
**Priorität**: P0  
**Bereich**: Datei-Uploads

| Test | Beschreibung | Status | Anmerkungen |
|------|--------------|---------|-------------|
| [ ] | File-Type-Validation | | MIME + Extension |
| [ ] | File-Size-Limits | | Max 10MB |
| [ ] | Malware-Scanning | | ClamAV |
| [ ] | Separate Upload-Directory | | Outside webroot |
| [ ] | Filename-Sanitization | | Remove special chars |
| [ ] | No PHP/Script Execution | | .htaccess deny |

---

## 🔍 Security Headers

### HEADERS-001: Security Headers Check
**Priorität**: P1  
**Bereich**: HTTP-Headers

| Test | Beschreibung | Status | Anmerkungen |
|------|--------------|---------|-------------|
| [ ] | X-Content-Type-Options | | nosniff |
| [ ] | X-Frame-Options | | DENY |
| [ ] | X-XSS-Protection | | 1; mode=block |
| [ ] | Strict-Transport-Security | | max-age=31536000 |
| [ ] | Content-Security-Policy | | Restrictive CSP |
| [ ] | Referrer-Policy | | strict-origin |
| [ ] | Permissions-Policy | | Minimal permissions |

---

## 🔎 API Security

### API-001: Authentication
**Priorität**: P0  
**Bereich**: API-Zugriff

| Test | Beschreibung | Status | Anmerkungen |
|------|--------------|---------|-------------|
| [ ] | API-Key-Validation | | Bearer Token |
| [ ] | OAuth2 Implementation | | If applicable |
| [ ] | Rate-Limiting per API-Key | | 1000 req/hour |
| [ ] | API-Versioning | | /api/v1/ |
| [ ] | Webhook-Signature-Validation | | HMAC-SHA256 |

### API-002: Input Validation
**Priorität**: P0  
**Bereich**: API-Requests

| Test | Beschreibung | Status | Anmerkungen |
|------|--------------|---------|-------------|
| [ ] | JSON-Schema-Validation | | Request validation |
| [ ] | Parameter-Type-Checking | | Strong typing |
| [ ] | Max Request Size | | 1MB limit |
| [ ] | Nested Object Limits | | Max depth 5 |
| [ ] | Array Size Limits | | Max 1000 items |

---

## 📊 Security Testing Tools

### Automated Security Scanning

```bash
# OWASP ZAP Scan
docker run -t owasp/zap2docker-stable zap-baseline.py \
  -t https://api.askproai.de -r zap-report.html

# Nikto Web Scanner
nikto -h https://api.askproai.de -ssl -Format html -o nikto-report.html

# SQLMap for SQL Injection
sqlmap -u "https://api.askproai.de/business/search?q=test" \
  --cookie="session=..." --level=5 --risk=3

# Nmap Service Scan
nmap -sV -sC -O -A api.askproai.de -oX nmap-report.xml
```

### Laravel Security Audit

```bash
# Security Checker
composer require --dev enlightn/security-checker
php artisan security:check

# Laravel Microscope
composer require --dev imanghafoori/laravel-microscope --dev
php artisan check:all

# PHP Security Checker
curl -s https://security.symfony.com/check_lock | bash
```

### Manual Testing Scripts

```php
// tests/Security/XSSTest.php
namespace Tests\Security;

use Tests\TestCase;

class XSSTest extends TestCase
{
    protected $xssPayloads = [
        '<script>alert("XSS")</script>',
        '"><script>alert("XSS")</script>',
        '<img src=x onerror=alert("XSS")>',
        '<svg/onload=alert("XSS")>',
        'javascript:alert("XSS")',
        '<iframe src="javascript:alert(`XSS`)">',
        '<input onfocus=alert("XSS") autofocus>',
        '<select onfocus=alert("XSS")>',
        '<textarea onfocus=alert("XSS") autofocus>',
        '<keygen onfocus=alert("XSS") autofocus>',
        '<video><source onerror="alert(\'XSS\')">',
        '<audio src=x onerror=alert("XSS")>',
        '<details open ontoggle=alert("XSS")>',
        '<marquee onstart=alert("XSS")>',
    ];
    
    /** @test */
    public function test_search_parameter_is_escaped()
    {
        $user = User::factory()->create();
        
        foreach ($this->xssPayloads as $payload) {
            $response = $this->actingAs($user)
                ->get('/business/calls?search=' . urlencode($payload));
            
            $response->assertDontSee($payload, false);
            $response->assertDontSee('<script>', false);
        }
    }
}
```

```php
// tests/Security/SQLInjectionTest.php
namespace Tests\Security;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class SQLInjectionTest extends TestCase
{
    protected $sqlPayloads = [
        "' OR '1'='1",
        "' OR '1'='1' --",
        "' OR '1'='1' /*",
        "' UNION SELECT * FROM users--",
        "'; DROP TABLE users--",
        "' AND 1=CAST(0x5f21403264696c656d6d61 AS INT)--",
        "' AND SLEEP(5)--",
        "' AND BENCHMARK(5000000,MD5('test'))--",
    ];
    
    /** @test */
    public function test_login_is_protected_against_sql_injection()
    {
        foreach ($this->sqlPayloads as $payload) {
            $response = $this->post('/business/login', [
                'email' => $payload,
                'password' => $payload,
            ]);
            
            // Should not cause database error
            $response->assertSessionDoesntHaveErrors();
            
            // Should not authenticate
            $this->assertGuest();
        }
        
        // Ensure no raw queries were executed
        $this->assertEmpty(DB::getQueryLog());
    }
}
```

---

## 🚨 Security Incident Response

### Incident Response Plan

1. **Erkennung**
   - Alert von Monitoring
   - User-Report
   - Security Scan

2. **Eindämmung**
   - Betroffene Services isolieren
   - Firewall-Regeln anpassen
   - Temporäre Fixes

3. **Untersuchung**
   - Log-Analyse
   - Forensik
   - Root Cause Analysis

4. **Behebung**
   - Patch entwickeln
   - Security Fix deployen
   - Validierung

5. **Wiederherstellung**
   - Services reaktivieren
   - Monitoring verstärken
   - User informieren

6. **Nachbereitung**
   - Incident Report
   - Lessons Learned
   - Prozess-Verbesserung

---

## 📋 Security Compliance Checklist

### DSGVO/GDPR Compliance
- [ ] Datenschutzerklärung aktuell
- [ ] Cookie-Consent implementiert
- [ ] Recht auf Löschung
- [ ] Datenportabilität
- [ ] Verschlüsselung personenbezogener Daten
- [ ] Data Processing Agreements
- [ ] Breach Notification Process

### PCI DSS (if applicable)
- [ ] Keine Kreditkartendaten speichern
- [ ] Tokenization verwenden
- [ ] Sichere Übertragung
- [ ] Access Control
- [ ] Regular Security Scans

### ISO 27001 Controls
- [ ] Access Control Policy
- [ ] Information Classification
- [ ] Incident Management
- [ ] Business Continuity
- [ ] Supplier Security
- [ ] Security Awareness Training

---

## 📊 Security Metrics

### Key Security Indicators
| Metric | Target | Current | Status |
|--------|--------|---------|---------|
| Vulnerabilities (Critical) | 0 | ___ | ⚪ |
| Vulnerabilities (High) | < 5 | ___ | ⚪ |
| Patch Time (Critical) | < 24h | ___ | ⚪ |
| Security Training Completion | 100% | ___% | ⚪ |
| Failed Login Attempts/Day | < 100 | ___ | ⚪ |
| Security Incidents/Month | 0 | ___ | ⚪ |

---

## ✅ Security Sign-Off

**Security Test durchgeführt von**: _________________  
**Datum**: _________________  
**Version**: _________________  

### Test Results Summary
- **Critical Issues**: ___
- **High Issues**: ___
- **Medium Issues**: ___
- **Low Issues**: ___

### Release Decision
[ ] **Approved for Release** - No critical/high issues  
[ ] **Conditional Release** - With documented risks  
[ ] **Release Blocked** - Critical issues must be fixed  

**Security Lead Approval**: _________________  
**Date**: _________________  
**Signature**: _________________