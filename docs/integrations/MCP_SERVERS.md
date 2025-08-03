## MCP-Server Übersicht und Verwendung

### Verfügbare MCP-Server im Projekt

#### Interne MCP-Server (bereits implementiert)
1. **CalcomMCPServer** - Cal.com Integration für Kalenderfunktionen
2. **RetellMCPServer** - Retell.ai Integration für AI-Telefonie
3. **DatabaseMCPServer** - Sichere Datenbankoperationen
4. **WebhookMCPServer** - Webhook-Verarbeitung
5. **QueueMCPServer** - Queue-Management und Job-Status
6. **StripeMCPServer** - Payment-Processing
7. **KnowledgeMCPServer** - Knowledge Base Management
8. **AppointmentMCPServer** - Terminverwaltung
9. **CustomerMCPServer** - Kundenverwaltung
10. **CompanyMCPServer** - Multi-Tenant Management
11. **BranchMCPServer** - Filialverwaltung
12. **RetellConfigurationMCPServer** - Retell Agent Konfiguration
13. **RetellCustomFunctionMCPServer** - Custom AI Funktionen
14. **AppointmentManagementMCPServer** - Erweiterte Terminworkflows
15. **SentryMCPServer** - Error Tracking

#### Externe MCP-Server (konfigurierbar)
- **sequential_thinking** - Schrittweise Problemlösung (standardmäßig aktiv)
- **postgres** - Datenbankzugriff (mapped auf MySQL/MariaDB)
- **effect_docs** - Dokumentationsgenerierung
- **taskmaster_ai** - Erweiterte Aufgabenverwaltung (standardmäßig deaktiviert)

#### Claude-spezifische MCP-Tools
- **mcp__context7__resolve-library-id** - Library-Namen zu Context7 IDs auflösen
- **mcp__context7__get-library-docs** - Aktuelle Library-Dokumentation abrufen

### MCP-Server Verwendungsrichtlinien

#### Wann welchen MCP-Server nutzen:

**Für API-Integrationen und Dokumentation:**
- Nutze Context7 MCP für externe Library-Dokumentation (Laravel, Filament, etc.)
- Verwende interne MCP-Server für projektspezifische APIs

**Für Task Management:**
- Einfache Aufgaben: `TodoWrite`/`TodoRead` Tools
- Komplexe Projekte: Aktiviere `taskmaster_ai` MCP
- Detaillierte Planung mit Abhängigkeiten und Meilensteinen

**Für Datenoperationen:**
- DatabaseMCPServer für sichere Queries
- WebhookMCPServer für Event-Verarbeitung
- QueueMCPServer für asynchrone Jobs

**Best Practices:**
1. **Evaluiere zu Beginn jeder Aufgabe** verfügbare MCP-Server
2. **Kombiniere mehrere MCP-Server** für optimale Ergebnisse
3. **Dokumentiere MCP-Nutzung** in Implementierungsnotizen
4. **Prüfe externe MCP-Optionen** bei neuen Anforderungen

### MCP vs. Subagenten: Entscheidungshilfe

| Use Case | MCP-Server | Subagenten | Begründung |
|----------|------------|------------|------------|
| **API-Integration** | ✅ CalcomMCP, RetellMCP | ❌ | MCP für direkte API-Calls |
| **Code-Analyse** | ❌ | ✅ performance-profiler, security-scanner | Agents für komplexe Analyse |
| **UI/UX Debugging** | ❌ | ✅ ui-auditor, design/ux-researcher | Agents haben Browser-Tools |
| **Datenbank-Queries** | ✅ DatabaseMCP | ⚠️ | MCP für sichere DB-Ops |
| **Feature Development** | ⚠️ | ✅ engineering/rapid-prototyper | Agents für kreative Tasks |
| **Testing** | ⚠️ | ✅ testing/*, engineering/test-writer | Agents für Test-Strategien |
| **Dokumentation** | ✅ Context7 MCP | ⚠️ | MCP für Library-Docs |
| **Business Intelligence** | ⚠️ | ✅ studio-operations/analytics-reporter | Agents für Insights |

**Kombinierte Workflows:**
```bash
# 1. Research mit Agent → Implementation mit MCP
> Use the product/trend-researcher subagent to analyze market trends
# → Dann MCP für API-Integration

# 2. Debug mit Agent → Fix mit direktem Code
> Use the retell-call-debugger subagent to find the issue
# → Dann direkte Code-Änderung

# 3. Parallel: Agent für Analyse + MCP für Daten
> Use the performance-profiler subagent to analyze performance
# + Gleichzeitig DatabaseMCP für Query-Optimierung
```

### MCP-Server aktivieren:
```bash
# Externe MCP-Server in .env aktivieren
MCP_TASKMASTER_ENABLED=true
MCP_EFFECT_DOCS_ENABLED=true

# MCP-Status prüfen
php artisan mcp:status

# MCP-Health Check
php artisan mcp:health
```

## Essential Commands
