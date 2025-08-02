# 🎯 Agent Selection Matrix für AskProAI

Diese Matrix hilft bei der Auswahl des richtigen Agenten für verschiedene Aufgaben im AskProAI-Projekt.

**Hinweis**: Diese Matrix umfasst 43 Agents (8 AskProAI + 35 Contains Studio). Einige Contains Studio Agents wurden bewusst nicht integriert:
- `instagram-curator` und `twitter-engager` (B2B SaaS Fokus)
- `performance-benchmarker` (Überschneidung mit unserem `performance-profiler`)

## 📊 Aufgaben-zu-Agent Mapping

### 🔧 Technische Aufgaben

| Aufgabe | Empfohlener Agent | Alternativer Agent | Begründung |
|---------|-------------------|-------------------|------------|
| **Performance-Probleme analysieren** | `performance-profiler` | `testing/performance-benchmarker` | Profiler für Quick-Analysis, Benchmarker für detaillierte Metriken |
| **Sicherheitslücken finden** | `security-scanner` | `multi-tenant-auditor` | Scanner für allgemeine Security, Auditor für Tenant-spezifische Issues |
| **UI/UX Bugs identifizieren** | `ui-auditor` | `design/ux-researcher` | Auditor für technische Bugs, Researcher für UX-Probleme |
| **API-Integration debuggen** | `calcom-api-validator` oder `retell-call-debugger` | `testing/api-tester` | Spezifische Validator für bekannte APIs, Tester für allgemeine Tests |
| **Webhook-Flows verstehen** | `webhook-flow-analyzer` | - | Spezialisiert auf Event-Flow-Analyse |

### 🚀 Development Tasks

| Aufgabe | Empfohlener Agent | Alternativer Agent | Begründung |
|---------|-------------------|-------------------|------------|
| **Neues Feature entwickeln** | `engineering/rapid-prototyper` | `engineering/backend-architect` | Prototyper für schnelle MVPs, Architect für komplexe Features |
| **Tests schreiben/fixen** | `engineering/test-writer-fixer` | `testing/api-tester` | Writer für Unit/Integration Tests, Tester für API-spezifische Tests |
| **AI-Features verbessern** | `engineering/ai-engineer` | `retell-call-debugger` | AI-Engineer für neue Features, Debugger für bestehende Probleme |
| **System-Architektur planen** | `engineering/backend-architect` | - | Spezialisiert auf Architektur-Entscheidungen |

### 📊 Product & Business Tasks

| Aufgabe | Empfohlener Agent | Alternativer Agent | Begründung |
|---------|-------------------|-------------------|------------|
| **User Feedback analysieren** | `product/feedback-synthesizer` | - | Extrahiert Insights aus Feedback-Daten |
| **Sprint planen** | `product/sprint-prioritizer` | - | Optimiert Sprint-Planung basierend auf Value |
| **Markttrends researchen** | `product/trend-researcher` | - | Identifiziert relevante Trends und Opportunities |
| **Feature priorisieren** | `product/sprint-prioritizer` | `product/feedback-synthesizer` | Prioritizer für Planung, Synthesizer für datenbasierte Entscheidungen |

### 🎨 Design & UX Tasks

| Aufgabe | Empfohlener Agent | Alternativer Agent | Begründung |
|---------|-------------------|-------------------|------------|
| **User Journey optimieren** | `design/ux-researcher` | `ui-auditor` | Researcher für UX-Analyse, Auditor für technische Issues |
| **UI Components designen** | `design/ui-designer` | - | Spezialisiert auf visuelles Design |
| **Usability-Probleme finden** | `design/ux-researcher` | `product/feedback-synthesizer` | Researcher für direkte Analyse, Synthesizer für Feedback-basierte Insights |

### 📈 Marketing & Growth Tasks

| Aufgabe | Empfohlener Agent | Alternativer Agent | Begründung |
|---------|-------------------|-------------------|------------|
| **App Store Listing optimieren** | `marketing/app-store-optimizer` | - | ASO-Spezialist |
| **Viral Features planen** | `marketing/growth-hacker` | `product/trend-researcher` | Growth für Mechanismen, Trend für Inspiration |
| **Referral-System designen** | `marketing/growth-hacker` | `engineering/rapid-prototyper` | Growth für Strategie, Prototyper für Implementation |

## 🔄 Kombinierte Workflows

### Problem → Lösung Workflows

1. **Performance-Problem**
   - Start: `performance-profiler` (Identifikation)
   - Dann: `engineering/backend-architect` (Lösung)
   - Final: `testing/performance-benchmarker` (Verifikation)

2. **User Complaint**
   - Start: `product/feedback-synthesizer` (Verstehen)
   - Dann: `design/ux-researcher` (Analysieren)
   - Final: `engineering/rapid-prototyper` (Lösen)

3. **Neue Integration**
   - Start: `engineering/backend-architect` (Design)
   - Dann: `engineering/rapid-prototyper` (Build)
   - Final: `testing/api-tester` (Verifizieren)

### Feature Development Workflows

1. **Data-Driven Feature**
   - Research: `product/trend-researcher`
   - Validate: `product/feedback-synthesizer`
   - Design: `engineering/backend-architect`
   - Build: `engineering/rapid-prototyper`
   - Test: `engineering/test-writer-fixer`

2. **Quick Win Feature**
   - Identify: `product/feedback-synthesizer`
   - Prototype: `engineering/rapid-prototyper`
   - Polish: `design/ui-designer`
   - Launch: `marketing/growth-hacker`

## 🎯 Best Practices

1. **Start spezifisch**: Nutze spezialisierte Agenten (z.B. `retell-call-debugger`) bevor du zu allgemeinen wechselst
2. **Kombiniere Perspektiven**: Technical + Product + Design Agents für ganzheitliche Lösungen
3. **Iteriere schnell**: Rapid-prototyper → Feedback → Iterate
4. **Messe alles**: Performance-benchmarker vor und nach Änderungen
5. **User-First**: Immer mit feedback-synthesizer oder ux-researcher validieren

### 🏢 Operations & Management Tasks

| Aufgabe | Empfohlener Agent | Alternativer Agent | Begründung |
|---------|-------------------|-------------------|------------|
| **KPIs & Analytics erstellen** | `studio-operations/analytics-reporter` | - | Business Intelligence Spezialist |
| **Revenue & Kosten tracken** | `studio-operations/finance-tracker` | - | Financial Analysis Expert |
| **Infrastructure überwachen** | `studio-operations/infrastructure-maintainer` | `engineering/devops-automator` | Maintainer für Monitoring, DevOps für Fixes |
| **DSGVO/Legal prüfen** | `studio-operations/legal-compliance-checker` | `security-scanner` | Legal für Compliance, Scanner für technische Checks |
| **Support-Anfragen analysieren** | `studio-operations/support-responder` | `product/feedback-synthesizer` | Support für Tickets, Feedback für Insights |

### 🚀 DevOps & Deployment Tasks

| Aufgabe | Empfohlener Agent | Alternativer Agent | Begründung |
|---------|-------------------|-------------------|------------|
| **CI/CD Pipeline bauen** | `engineering/devops-automator` | - | Infrastructure as Code Spezialist |
| **Frontend entwickeln** | `engineering/frontend-developer` | `design/ui-designer` | Developer für Code, Designer für Mockups |
| **Mobile App erstellen** | `engineering/mobile-app-builder` | `engineering/rapid-prototyper` | Mobile für Native, Prototyper für PWA |
| **Prozesse optimieren** | `bonus/studio-coach` | `testing/workflow-optimizer` | Coach für Team-Prozesse, Optimizer für Tech-Workflows |
| **Team-Morale boosten** | `bonus/joker` | - | Fun & Engagement Spezialist |

## 📝 Quick Reference

```bash
# Debugging
"Warum funktioniert X nicht?" → performance-profiler / retell-call-debugger / webhook-flow-analyzer

# Building
"Lass uns Y bauen" → engineering/rapid-prototyper → engineering/test-writer-fixer

# Understanding
"Was wollen die User?" → product/feedback-synthesizer → design/ux-researcher

# Optimizing
"Wie machen wir Z besser?" → testing/performance-benchmarker → engineering/backend-architect

# Growing
"Wie bekommen wir mehr User?" → marketing/growth-hacker → product/trend-researcher

# Operating
"Wie läuft das Business?" → studio-operations/analytics-reporter → studio-operations/finance-tracker

# Deploying
"Wie deployen wir das?" → engineering/devops-automator → project-management/project-shipper

# Complying
"Sind wir DSGVO-konform?" → studio-operations/legal-compliance-checker → security-scanner
```