# 🤖 AskProAI Subagenten-Framework

Dieses erweiterte Framework kombiniert spezialisierte technische Analyse-Agenten aus dem AskProAI-Projekt mit den umfassenden Product Development Agenten von Contains Studio. Diese Synergie ermöglicht sowohl tiefgreifende technische Analysen als auch rapid prototyping und user-centered development.

**Total: 43 Agents** (8 AskProAI + 35 Contains Studio)

## 📁 Agent-Kategorien

### 🔧 Technische Analyse & Debugging (AskProAI-spezifisch)

| Agent Name | Zweck | Tools | Priorität | Aufruf-Beispiel |
|------------|-------|-------|-----------|-----------------|
| **ui-auditor** | Visuelle Regression, Tailwind-Probleme, UI-Konsistenz | `Browser`, `Read`, `Bash`, `Grep` | High | `> Use the ui-auditor subagent to check the admin panel for visual bugs on mobile devices` |
| **performance-profiler** | PHP/Laravel Performance, N+1 Queries, Memory Leaks | `Bash`, `Grep`, `Read` | High | `> Use the performance-profiler subagent to analyze slow queries in the appointment booking flow` |
| **security-scanner** | Sicherheitslücken, DSGVO-Compliance, Multi-Tenant-Isolation | `Grep`, `Bash`, `Read` | High | `> Use the security-scanner subagent to audit multi-tenant data isolation` |
| **calcom-api-validator** | Cal.com API v2 Tests, Event Sync, Booking Flow | `Http`, `Bash`, `Read`, `Grep` | Normal | `> Use the calcom-api-validator subagent to test availability slot calculations` |
| **retell-call-debugger** | Retell.ai Webhooks, Call Flow, Dynamic Variables | `Read`, `Bash`, `Grep`, `Http` | High | `> Use the retell-call-debugger subagent to trace why appointments are not created from calls` |
| **filament-resource-analyzer** | Filament Admin Resources, Policies, Authorization | `Read`, `Grep`, `Bash` | Medium | `> Use the filament-resource-analyzer subagent to check which resources lack proper authorization` |
| **multi-tenant-auditor** | Company Isolation, Cross-Tenant Leaks, Session Separation | `Read`, `Grep`, `Bash` | Medium | `> Use the multi-tenant-auditor subagent to verify tenant data isolation` |
| **webhook-flow-analyzer** | End-to-End Webhook Flows, Event Correlation, Queue Analysis | `Read`, `Grep`, `Bash`, `Http` | Medium | `> Use the webhook-flow-analyzer subagent to visualize the complete phone-to-appointment flow` |

### 🚀 Engineering & Development (Contains Studio)

| Agent Name | Zweck | Kategorie | Aufruf-Beispiel |
|------------|-------|-----------|-----------------|
| **engineering/rapid-prototyper** | MVP-Entwicklung, Prototyping neuer Features | Engineering | `> Use the engineering/rapid-prototyper subagent to create a notification service MVP` |
| **engineering/backend-architect** | API-Design, System-Architektur, Datenbank-Design | Engineering | `> Use the engineering/backend-architect subagent to design the new subscription API` |
| **engineering/test-writer-fixer** | Test-Coverage, Test-Reparatur, TDD | Engineering | `> Use the engineering/test-writer-fixer subagent to improve test coverage for the booking flow` |
| **engineering/ai-engineer** | AI/ML Integration, Prompt Engineering | Engineering | `> Use the engineering/ai-engineer subagent to optimize our Retell.ai prompts` |
| **engineering/devops-automator** | CI/CD, Infrastructure as Code, Deployment | Engineering | `> Use the engineering/devops-automator subagent to set up automated deployments` |
| **engineering/frontend-developer** | React/Vue Development, UI Implementation | Engineering | `> Use the engineering/frontend-developer subagent to build the customer portal frontend` |
| **engineering/mobile-app-builder** | Mobile App Development, React Native | Engineering | `> Use the engineering/mobile-app-builder subagent to create our mobile app MVP` |

### 📊 Product & Business (Contains Studio)

| Agent Name | Zweck | Kategorie | Aufruf-Beispiel |
|------------|-------|-----------|-----------------|
| **product/feedback-synthesizer** | User-Feedback Analyse, Insights-Extraktion | Product | `> Use the product/feedback-synthesizer subagent to analyze customer call transcripts` |
| **product/sprint-prioritizer** | Sprint Planning, Feature-Priorisierung | Product | `> Use the product/sprint-prioritizer subagent to plan our next sprint based on user feedback` |
| **product/trend-researcher** | Markt-Trends, Competitor-Analyse | Product | `> Use the product/trend-researcher subagent to research AI phone assistant trends` |

### 🎨 Design & UX (Contains Studio)

| Agent Name | Zweck | Kategorie | Aufruf-Beispiel |
|------------|-------|-----------|-----------------|
| **design/ux-researcher** | User Research, Usability-Analyse | Design | `> Use the design/ux-researcher subagent to analyze user journey for appointment booking` |
| **design/ui-designer** | UI Design, Component-Design | Design | `> Use the design/ui-designer subagent to design a better call history interface` |
| **design/brand-guardian** | Brand Consistency, Design System Enforcement | Design | `> Use the design/brand-guardian subagent to ensure consistent branding across all features` |
| **design/visual-storyteller** | Visual Narratives, User Journey Visualization | Design | `> Use the design/visual-storyteller subagent to create compelling user stories` |
| **design/whimsy-injector** | Playful Elements, Fun UI Components | Design | `> Use the design/whimsy-injector subagent to add delightful interactions` |

### 📈 Marketing & Growth (Contains Studio)

| Agent Name | Zweck | Kategorie | Aufruf-Beispiel |
|------------|-------|-----------|-----------------|
| **marketing/app-store-optimizer** | ASO, App Store Listings | Marketing | `> Use the marketing/app-store-optimizer subagent to improve our app store presence` |
| **marketing/growth-hacker** | Growth Strategies, Viral Features | Marketing | `> Use the marketing/growth-hacker subagent to design referral mechanisms` |
| **marketing/content-creator** | Content Strategy, Blog Posts, Social Media | Marketing | `> Use the marketing/content-creator subagent to create engaging blog content` |
| **marketing/reddit-community-builder** | Community Management, Reddit Strategy | Marketing | `> Use the marketing/reddit-community-builder subagent to build our subreddit presence` |
| **marketing/tiktok-strategist** | TikTok Content, Viral Video Strategy | Marketing | `> Use the marketing/tiktok-strategist subagent to create viral TikTok content` |

### 🧪 Testing & QA (Contains Studio)

| Agent Name | Zweck | Kategorie | Aufruf-Beispiel |
|------------|-------|-----------|-----------------|
| **testing/api-tester** | API Testing, Integration Tests | Testing | `> Use the testing/api-tester subagent to test our webhook endpoints` |
| **testing/performance-benchmarker** | Performance Testing, Load Tests | Testing | `> Use the testing/performance-benchmarker subagent to benchmark our appointment creation flow` |
| **testing/test-results-analyzer** | Test Analysis, Coverage Reports, Failure Patterns | Testing | `> Use the testing/test-results-analyzer subagent to analyze test failure patterns` |
| **testing/tool-evaluator** | Tool Comparison, Library Evaluation | Testing | `> Use the testing/tool-evaluator subagent to evaluate new testing frameworks` |
| **testing/workflow-optimizer** | Workflow Analysis, Process Optimization | Testing | `> Use the testing/workflow-optimizer subagent to optimize our CI/CD pipeline` |

### 📊 Project Management (Contains Studio)

| Agent Name | Zweck | Kategorie | Aufruf-Beispiel |
|------------|-------|-----------|-----------------|
| **project-management/experiment-tracker** | A/B Tests, Experiment Management | PM | `> Use the project-management/experiment-tracker subagent to track feature experiments` |
| **project-management/project-shipper** | Release Management, Deployment Coordination | PM | `> Use the project-management/project-shipper subagent to coordinate the next release` |
| **project-management/studio-producer** | Sprint Management, Team Coordination | PM | `> Use the project-management/studio-producer subagent to organize our next sprint` |

### 🏢 Studio Operations (Contains Studio)

| Agent Name | Zweck | Kategorie | Aufruf-Beispiel |
|------------|-------|-----------|-----------------|
| **studio-operations/analytics-reporter** | Business Analytics, KPI Reporting | Operations | `> Use the studio-operations/analytics-reporter subagent to generate monthly KPI reports` |
| **studio-operations/finance-tracker** | Revenue Tracking, Financial Analysis | Operations | `> Use the studio-operations/finance-tracker subagent to analyze subscription revenue` |
| **studio-operations/infrastructure-maintainer** | Server Management, System Health | Operations | `> Use the studio-operations/infrastructure-maintainer subagent to audit infrastructure health` |
| **studio-operations/legal-compliance-checker** | DSGVO, Legal Requirements | Operations | `> Use the studio-operations/legal-compliance-checker subagent to verify GDPR compliance` |
| **studio-operations/support-responder** | Customer Support, Ticket Management | Operations | `> Use the studio-operations/support-responder subagent to analyze support tickets` |

### 🎮 Bonus Agents (Contains Studio)

| Agent Name | Zweck | Kategorie | Aufruf-Beispiel |
|------------|-------|-----------|-----------------|
| **bonus/joker** | Team Morale, Fun Activities | Bonus | `> Use the bonus/joker subagent to create fun team building activities` |
| **bonus/studio-coach** | Process Improvement, Team Coaching | Bonus | `> Use the bonus/studio-coach subagent to optimize our development workflow` |

## 🎯 Proaktive Agents

Einige Agents triggern automatisch in bestimmten Kontexten:
- **bonus/studio-coach**: Bei komplexen Multi-Agent-Tasks oder wenn Agents Führung brauchen
- **engineering/test-writer-fixer**: Nach Feature-Implementation, Bug-Fixes oder Code-Änderungen
- **design/whimsy-injector**: Nach UI/UX-Änderungen
- **project-management/experiment-tracker**: Wenn Feature-Flags hinzugefügt werden

## 🚀 Verwendung

### Einzelnen Agenten aufrufen
```
# Technische Agenten (ohne Kategorie-Prefix)
> Use the performance-profiler subagent to [specific task]

# Contains Studio Agenten (mit Kategorie-Prefix)
> Use the engineering/rapid-prototyper subagent to [specific task]
```

### 🎯 Empfohlene Workflows

#### 1. **Feature Development Workflow**
```
# Schritt 1: User Feedback analysieren
> Use the product/feedback-synthesizer subagent to analyze recent customer feedback

# Schritt 2: Trend-Research für Innovation
> Use the product/trend-researcher subagent to identify market trends

# Schritt 3: MVP entwickeln
> Use the engineering/rapid-prototyper subagent to create the feature prototype

# Schritt 4: Tests schreiben
> Use the engineering/test-writer-fixer subagent to add comprehensive tests

# Schritt 5: Performance prüfen
> Use the performance-profiler subagent to ensure optimal performance
```

#### 2. **Bug-to-Feature Workflow**
```
# Schritt 1: Bug analysieren
> Use the retell-call-debugger subagent to identify the root cause

# Schritt 2: User Impact verstehen
> Use the product/feedback-synthesizer subagent to understand user pain points

# Schritt 3: Lösung designen
> Use the engineering/backend-architect subagent to design a robust solution

# Schritt 4: UI verbessern
> Use the design/ui-designer subagent to improve the user interface
```

#### 3. **Performance Optimization Workflow**
```
# Schritt 1: Baseline erstellen
> Use the testing/performance-benchmarker subagent to establish baseline metrics

# Schritt 2: Bottlenecks identifizieren
> Use the performance-profiler subagent to find performance issues

# Schritt 3: Architektur optimieren
> Use the engineering/backend-architect subagent to redesign critical paths

# Schritt 4: Ergebnisse verifizieren
> Use the testing/performance-benchmarker subagent to verify improvements
```

#### 4. **Product Launch Workflow**
```
# Schritt 1: Feature finalisieren
> Use the engineering/rapid-prototyper subagent to complete the feature

# Schritt 2: UX optimieren
> Use the design/ux-researcher subagent to validate user experience

# Schritt 3: Growth-Strategie
> Use the marketing/growth-hacker subagent to plan viral features

# Schritt 4: App Store optimieren
> Use the marketing/app-store-optimizer subagent to improve visibility
```

#### 5. **Content & Community Workflow**
```
# Schritt 1: Content-Strategie entwickeln
> Use the marketing/content-creator subagent to plan content calendar

# Schritt 2: Visual Content erstellen
> Use the design/visual-storyteller subagent to create compelling visuals

# Schritt 3: Community aufbauen
> Use the marketing/reddit-community-builder subagent to engage with users

# Schritt 4: Brand Consistency sichern
> Use the design/brand-guardian subagent to ensure consistent messaging
```

#### 6. **Experiment & Release Workflow**
```
# Schritt 1: Experiment planen
> Use the project-management/experiment-tracker subagent to design A/B tests

# Schritt 2: Tests durchführen
> Use the testing/test-results-analyzer subagent to analyze experiment results

# Schritt 3: Release vorbereiten
> Use the project-management/project-shipper subagent to coordinate deployment

# Schritt 4: Workflow optimieren
> Use the testing/workflow-optimizer subagent to improve processes
```

#### 7. **DevOps & Infrastructure Workflow**
```
# Schritt 1: Infrastructure analysieren
> Use the studio-operations/infrastructure-maintainer subagent to audit current setup

# Schritt 2: CI/CD Pipeline erstellen
> Use the engineering/devops-automator subagent to set up automated deployments

# Schritt 3: Monitoring einrichten
> Use the studio-operations/analytics-reporter subagent to create system dashboards

# Schritt 4: Performance optimieren
> Use the testing/performance-benchmarker subagent to identify bottlenecks
```

#### 8. **Compliance & Legal Workflow**
```
# Schritt 1: DSGVO-Compliance prüfen
> Use the studio-operations/legal-compliance-checker subagent to audit data protection

# Schritt 2: Security Audit
> Use the security-scanner subagent to check for vulnerabilities

# Schritt 3: Dokumentation erstellen
> Use the studio-operations/support-responder subagent to create user-facing policies

# Schritt 4: Prozesse optimieren
> Use the bonus/studio-coach subagent to improve compliance processes
```

## 📊 Agent-Capabilities Matrix

| Capability | ui-auditor | performance-profiler | security-scanner | calcom-api-validator | retell-call-debugger | filament-resource-analyzer | multi-tenant-auditor | webhook-flow-analyzer |
|------------|:----------:|:-------------------:|:----------------:|:-------------------:|:-------------------:|:-------------------------:|:-------------------:|:--------------------:|
| Visual Testing | ✅ | ❌ | ❌ | ❌ | ❌ | ⚠️ | ❌ | ❌ |
| Query Analysis | ❌ | ✅ | ⚠️ | ⚠️ | ⚠️ | ✅ | ✅ | ⚠️ |
| API Testing | ❌ | ❌ | ❌ | ✅ | ✅ | ❌ | ❌ | ✅ |
| Security Audit | ❌ | ❌ | ✅ | ⚠️ | ❌ | ✅ | ✅ | ⚠️ |
| Flow Tracing | ❌ | ⚠️ | ❌ | ✅ | ✅ | ❌ | ⚠️ | ✅ |
| Code Analysis | ⚠️ | ✅ | ✅ | ⚠️ | ⚠️ | ✅ | ✅ | ✅ |

**Legende**: ✅ Primäre Fähigkeit | ⚠️ Sekundäre Fähigkeit | ❌ Nicht verfügbar

## 🛠️ Agent-Erweiterung

### Neuen Agenten hinzufügen
1. Erstelle `[agent-name].md` mit YAML-Front-Matter
2. Definiere klaren, einzelnen Verantwortungsbereich
3. Minimiere Tool-Set auf das Notwendige
4. Füge Agent zur README-Tabelle hinzu
5. Teste mit `claude agents validate`

### YAML-Front-Matter Template
```yaml
---
name: agent-name
description: |
  Kurze, präzise Beschreibung des Agent-Zwecks
  und seiner Haupt-Capabilities.
tools: [Tool1, Tool2, Tool3]
priority: high|normal|low
---
```

## 🔍 Best Practices

1. **Single Responsibility**: Jeder Agent hat genau einen Fokus
2. **Minimal Tools**: Nur Tools die wirklich benötigt werden
3. **No Code Changes**: Agenten analysieren nur, ändern nie Code
4. **Structured Output**: Konsistente Report-Formate
5. **Quality Checklists**: Jeder Agent hat eigene Qualitätskriterien

## 📞 Support & Feedback

Bei Fragen oder Verbesserungsvorschlägen:
- Erstelle ein Issue im Repository
- Tagge mit `subagent-framework`
- Beschreibe den Use Case für neue Agenten