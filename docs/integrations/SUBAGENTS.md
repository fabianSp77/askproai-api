## 🧠 Subagenten Framework (NEU August 2025)

**Status**: ✅ INTEGRIERT (43 Agents)

Das AskProAI-Projekt nutzt jetzt das erweiterte **Contains Studio Agents Framework** mit 43 spezialisierten AI-Agents für alle Aspekte der Produktentwicklung.

### Übersicht
- **8 Original AskProAI Agents**: Technische Analyse & Debugging
- **35 Contains Studio Agents**: Engineering, Product, Design, Marketing, Testing, PM, Operations
- **Selektive Integration**: Wichtigste Agents aus https://github.com/contains-studio/agents
- **Kategorien**: 8 verschiedene Agent-Kategorien für jeden Use Case

### Quick Start
```bash
# Technische Analyse (AskProAI Agents)
> Use the performance-profiler subagent to analyze slow queries
> Use the security-scanner subagent to check for vulnerabilities
> Use the ui-auditor subagent to find UI/UX issues

# Development (Contains Studio Agents)
> Use the engineering/rapid-prototyper subagent to build MVP features
> Use the engineering/frontend-developer subagent to fix React issues
> Use the engineering/devops-automator subagent to setup CI/CD

# Business Intelligence
> Use the studio-operations/analytics-reporter subagent to create KPI dashboards
> Use the studio-operations/finance-tracker subagent to analyze revenue
```

### Agent-Kategorien
1. **Technische Analyse** (8 Agents): Performance, Security, UI, APIs, Multi-Tenancy
2. **Engineering** (7): Backend, Frontend, Mobile, AI, DevOps, Prototyping
3. **Product** (3): Feedback Analysis, Sprint Planning, Trend Research  
4. **Design** (5): UX Research, UI Design, Brand, Visual Stories
5. **Marketing** (3): Growth Hacking, Content, ASO/Community
6. **Testing** (4): API Tests, Analysis, Tools, Workflows
7. **Project Management** (3): Experiments, Releases, Sprint Coordination
8. **Studio Operations** (5): Analytics, Finance, Infrastructure, Legal, Support
9. **Bonus** (2): Team Morale, Process Coaching

### Parallele Ausführung
Agents arbeiten automatisch parallel wenn mehrere aufgerufen werden:
```bash
# Diese 3 Agents laufen parallel:
> Use the ui-auditor subagent to check UI bugs
> Use the performance-profiler subagent to analyze performance  
> Use the security-scanner subagent to check security issues
```

### Proaktive Agents
Einige Agents triggern automatisch in bestimmten Kontexten:
- **studio-coach**: Bei komplexen Multi-Agent-Tasks oder wenn Agents Führung brauchen
- **test-writer-fixer**: Nach Feature-Implementation, Bug-Fixes oder Code-Änderungen
- **whimsy-injector**: Nach UI/UX-Änderungen
- **experiment-tracker**: Wenn Feature-Flags hinzugefügt werden

### Dokumentation
- **Hauptdokumentation**: [.claude/agents/README.md](./.claude/agents/README.md)
- **Agent-Auswahl**: [.claude/agents/AGENT_SELECTION_MATRIX.md](./.claude/agents/AGENT_SELECTION_MATRIX.md)
- **Integration Report**: [.claude/agents/COMPLETE_AGENT_INTEGRATION_2025-08-01.md](./.claude/agents/COMPLETE_AGENT_INTEGRATION_2025-08-01.md)

---

## 🚀 Best Practices & Automation (NEU 2025)

### Automatische MCP-Server Nutzung
```php
// Automatisch den besten MCP-Server finden und nutzen
$discovery = app(MCPAutoDiscoveryService::class);
$result = $discovery->executeTask('book appointment for tomorrow', $params);

// In Services mit UsesMCPServers Trait
$this->executeMCPTask('fetch customer data', ['phone' => $phoneNumber]);
```

### Subagenten & MCP Integration
```bash
# 1. Agent für Analyse → MCP für Execution
> Use the performance-profiler subagent to identify database bottlenecks
# → Dann DatabaseMCP für Query-Optimierung

# 2. Parallele Analyse mit mehreren Agents
> Use the ui-auditor subagent to check the admin panel
> Use the security-scanner subagent to audit authentication
> Use the multi-tenant-auditor subagent to verify data isolation

# 3. Agent Discovery für beste Lösung
> Use the product/feedback-synthesizer subagent to analyze user complaints
# → Agent findet Patterns und schlägt Lösungen vor
```

### Agent-First Development Workflow
1. **Analyse mit Agents**: Nutze spezialisierte Agents für tiefe Analyse
2. **Design mit Agents**: `engineering/backend-architect` für System-Design
3. **Prototyping mit Agents**: `engineering/rapid-prototyper` für MVPs
4. **Testing mit Agents**: `testing/*` Agents für Test-Strategien
5. **Deployment mit DevOps Agent**: `engineering/devops-automator`

### Data Flow Tracking
```php
// Jeden API-Call automatisch tracken
$correlationId = $this->dataFlowLogger->startFlow('webhook_incoming', 'retell', 'internal');
// ... API calls werden automatisch getrackt
$this->dataFlowLogger->completeFlow($correlationId);
```

### System Understanding & Impact Analysis
```bash
# Vor Änderungen: System verstehen
php artisan analyze:component App\\Services\\BookingService

# Vor Deployment: Impact analysieren
php artisan analyze:impact --git

# Mit Agents: Tiefere Analyse
> Use the webhook-flow-analyzer subagent to trace the booking flow
> Use the filament-resource-analyzer subagent to check permissions
```

### Neue Essential Commands
```bash
# MCP Discovery
php artisan mcp:discover "beschreibe deine aufgabe"
php artisan mcp:discover "create appointment" --execute

# Impact Analysis
php artisan analyze:impact --component=ServiceName
php artisan analyze:impact --git

# Documentation Health
php artisan docs:check-updates
php artisan docs:check-updates --auto-fix

# Code Quality
composer quality        # Alles auf einmal
composer pint          # Code formatieren
composer stan          # Static Analysis
composer test:coverage # Tests mit Coverage

# Quick Setup
composer setup         # Komplettes Setup inkl. Git Hooks
```

---

## Project Overview
