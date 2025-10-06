# 🚀 SuperClaude /sc: Kommandos - Vollständige Referenz

**Erstellt:** 22.09.2025
**Framework:** SuperClaude mit MCP-Server Integration
**Analyse-Methode:** Deep Framework Analysis

## 📋 Übersicht aller /sc: Kommandos

SuperClaude bietet **23 spezialisierte Kommandos** für fortgeschrittene Software-Engineering-Workflows:

### 🔧 Core Development Commands

| Kommando | Beschreibung | MCP-Server | Komplexität |
|----------|--------------|------------|-------------|
| `/sc:implement` | Code-Implementierung mit Best Practices | context7, magic | Standard |
| `/sc:fix` | Bug-Fixes mit Root-Cause-Analyse | sequential | Basic |
| `/sc:build` | Build-Pipeline & Deployment | - | Basic |
| `/sc:test` | Test-Suite Management | playwright | Standard |
| `/sc:troubleshoot` | Systematische Fehlerdiagnose | - | Basic |

### 📊 Analysis & Documentation

| Kommando | Beschreibung | MCP-Server | Komplexität |
|----------|--------------|------------|-------------|
| `/sc:analyze` | Multi-Domain Code-Analyse | - | Basic |
| `/sc:explain` | Code-Erklärung & Tech-Docs | - | Basic |
| `/sc:document` | Projekt-Dokumentation | context7 | Standard |
| `/sc:index` | Knowledge-Base Generation | sequential, context7 | Standard |

### 🎯 Workflow & Task Management

| Kommando | Beschreibung | MCP-Server | Komplexität |
|----------|--------------|------------|-------------|
| `/sc:task` | Komplexe Task-Verwaltung | ALL | Advanced |
| `/sc:workflow` | PRD → Implementation | ALL | Advanced |
| `/sc:brainstorm` | Feature-Design & Ideenfindung | - | Basic |
| `/sc:estimate` | Zeit/Ressourcen-Schätzung | sequential | Standard |
| `/sc:spawn` | Sub-Agent Orchestrierung | - | Advanced |

### 🔄 Session & Version Control

| Kommando | Beschreibung | MCP-Server | Komplexität |
|----------|--------------|------------|-------------|
| `/sc:load` | Projekt-Kontext laden | serena | Standard |
| `/sc:save` | Session speichern | serena | Standard |
| `/sc:git` | Git-Workflow Management | - | Basic |
| `/sc:reflect` | Session-Analyse | - | Basic |

### ⚡ Optimization & Maintenance

| Kommando | Beschreibung | MCP-Server | Komplexität |
|----------|--------------|------------|-------------|
| `/sc:improve` | Code-Optimierung | morphllm | Standard |
| `/sc:cleanup` | Workspace-Bereinigung | - | Basic |
| `/sc:design` | System-Architektur | sequential, context7 | Standard |
| `/sc:select-tool` | Tool-Auswahl-Strategie | - | Basic |

---

## 🎭 Personas (Spezialisierte Agenten)

SuperClaude aktiviert verschiedene "Personas" je nach Task:

```yaml
architect:    System-Design, Architektur-Entscheidungen
analyzer:     Code-Qualität, Performance-Analyse
frontend:     UI/UX, React/Vue/Angular
backend:      APIs, Datenbanken, Server-Logic
security:     Vulnerability-Scanning, Best Practices
devops:       CI/CD, Deployment, Infrastructure
project-manager: Task-Koordination, Roadmap
```

---

## 🔌 MCP-Server Integration

### Verfügbare Server & deren Spezialgebiete:

#### **1. Sequential** (`--seq`)
- Multi-Step Reasoning
- Komplexe Analyse-Workflows
- Strukturierte Problem-Lösung

#### **2. Context7** (`--c7`)
- Framework-spezifische Patterns
- Library-Documentation
- Best Practices

#### **3. Magic** (`--magic`)
- UI-Komponenten-Generierung
- Design-System Integration
- Modern UI von 21st.dev

#### **4. Playwright** (`--play`)
- Browser-Automatisierung
- E2E-Testing
- Visual Testing

#### **5. Morphllm** (`--morph`)
- Bulk-Code-Transformationen
- Pattern-basierte Edits
- Large-Scale Refactoring

#### **6. Serena** (`--serena`)
- Memory-Management
- Cross-Session Persistenz
- Projekt-Kontext

---

## 🚀 Praktische Anwendungsbeispiele

### Beispiel 1: Neues Projekt starten
```bash
/sc:load                           # Projekt-Kontext laden
/sc:analyze --focus architecture  # Architektur verstehen
/sc:index --type structure        # Projekt-Struktur dokumentieren
```

### Beispiel 2: Feature implementieren
```bash
/sc:brainstorm "user authentication"     # Ideen entwickeln
/sc:design --type feature                # Design erstellen
/sc:workflow auth-spec.md --systematic  # Workflow generieren
/sc:implement --with-tests               # Code schreiben
/sc:test --type integration              # Tests ausführen
```

### Beispiel 3: 500-Fehler beheben (Ihr Fall!)
```bash
/sc:troubleshoot "500 error" --trace    # Fehler analysieren
/sc:analyze --focus security            # Security-Check
/sc:fix --validate --test                # Fix mit Validierung
/sc:document --type incident             # Incident dokumentieren
```

### Beispiel 4: Performance-Optimierung
```bash
/sc:analyze --focus performance         # Bottlenecks finden
/sc:improve --target critical --parallel # Optimierungen
/sc:test --type performance              # Performance testen
/sc:cleanup --remove-debt                # Technical Debt
```

### Beispiel 5: Projekt-Dokumentation
```bash
/sc:document --type api                  # API-Docs generieren
/sc:index --format md                    # Knowledge-Base
/sc:explain src/core --depth deep        # Code-Erklärungen
```

---

## 🏁 Wichtige Flags & Modifikatoren

### Analyse-Tiefe
```bash
--think        # Standard-Analyse (~4K tokens)
--think-hard   # Tiefe Analyse (~10K tokens)
--ultrathink   # Maximum Analyse (~32K tokens) ← Sie nutzen das!
```

### MCP-Server Control
```bash
--c7          # Context7 für Framework-Patterns
--seq         # Sequential für Multi-Step
--magic       # Magic für UI-Komponenten
--all-mcp     # Alle Server aktivieren
--no-mcp      # Nur native Tools
```

### Execution Control
```bash
--parallel    # Parallele Ausführung
--delegate    # Task-Delegation an Sub-Agents
--validate    # Pre-Execution Validation
--safe-mode   # Maximale Sicherheit
--uc          # Ultra-Compressed Output
```

### Scope Control
```bash
--scope file|module|project|system
--focus performance|security|quality|architecture
--depth quick|deep
--strategy systematic|agile|enterprise
```

---

## 💡 Power-User Workflows für Ihr API Gateway

### 1. **Tägliche Session-Routine**
```bash
# Start
/sc:load
/sc:analyze --depth quick

# Arbeit
/sc:task "implement customer export" --delegate
/sc:test --parallel

# Ende
/sc:save --checkpoint
```

### 2. **Kritische Fehler beheben**
```bash
/sc:troubleshoot --type bug --trace
/sc:analyze --focus security
/sc:fix --safe-mode --validate
/sc:test --type regression
/sc:document --type incident
```

### 3. **Performance-Sprint**
```bash
/sc:analyze --focus performance --ultrathink
/sc:improve --target critical --parallel
/sc:cleanup --remove-debt
/sc:test --type performance
```

### 4. **Neue Feature-Entwicklung**
```bash
/sc:brainstorm "feature idea"
/sc:design --type feature
/sc:estimate --detailed
/sc:workflow --strategy systematic
/sc:implement --with-tests --parallel
```

---

## 📊 Kommando-Kombinationen

### Effektive Kombinationen:

| Workflow | Kommando-Kette |
|----------|----------------|
| **Bug-Fix** | `troubleshoot` → `analyze` → `fix` → `test` |
| **Feature** | `brainstorm` → `design` → `workflow` → `implement` |
| **Optimierung** | `analyze` → `improve` → `cleanup` → `test` |
| **Dokumentation** | `analyze` → `document` → `index` → `explain` |
| **Refactoring** | `analyze` → `design` → `improve` → `test` |

---

## 🎯 Best Practices

### ✅ DO's:
1. **Immer `/sc:load` am Session-Start**
2. **`/sc:save --checkpoint` vor kritischen Änderungen**
3. **`--parallel` für unabhängige Tasks nutzen**
4. **`--validate` bei Production-Code**
5. **`--ultrathink` für komplexe Probleme**

### ❌ DON'Ts:
1. **Keine `/sc:fix` ohne vorheriges `/sc:troubleshoot`**
2. **Kein `--no-mcp` bei komplexen Tasks**
3. **Nicht `/sc:cleanup` ohne Backup**
4. **Keine parallelen Sessions ohne `/sc:save`**

---

## 🔮 Advanced Features

### Cross-Session Memory
```bash
/sc:save --type learnings  # Patterns speichern
/sc:load --type checkpoint  # Session fortsetzen
```

### Multi-Agent Orchestration
```bash
/sc:spawn --agents 3 --parallel
/sc:task --delegate auto
```

### Progressive Enhancement
```bash
/sc:workflow --strategy progressive
/sc:implement --incremental
```

---

## 📈 Metriken & Monitoring

SuperClaude trackt automatisch:
- **Task-Completion-Rate**
- **Code-Quality-Scores**
- **Performance-Metrics**
- **Error-Recovery-Time**
- **Session-Productivity**

---

## 🆘 Troubleshooting SuperClaude

### Kommando funktioniert nicht?
1. Check MCP-Server: `--all-mcp`
2. Erhöhe Analyse: `--ultrathink`
3. Aktiviere Debug: `--verbose`

### Session-Probleme?
1. `/sc:save --force`
2. `/sc:load --refresh`
3. `/sc:reflect --diagnose`

### Performance-Issues?
1. Nutze `--parallel`
2. Aktiviere `--uc` (ultra-compressed)
3. Scope begrenzen: `--scope module`

---

## 🏆 Zusammenfassung

SuperClaude transformiert Claude Code in ein **Enterprise-Level Development Tool** mit:

- **23 spezialisierte Kommandos**
- **6 MCP-Server Integration**
- **7 Domain-Personas**
- **Cross-Session Memory**
- **Multi-Agent Orchestration**
- **Progressive Task Management**

Für Ihr API Gateway Projekt sind besonders relevant:
- `/sc:troubleshoot` - Für 500-Fehler
- `/sc:analyze` - Für Code-Qualität
- `/sc:improve` - Für Performance
- `/sc:document` - Für Dokumentation

---

*Dokumentation erstellt mit SuperClaude UltraThink Analysis*
*Framework Version: Latest*
*MCP-Server Status: All Available*