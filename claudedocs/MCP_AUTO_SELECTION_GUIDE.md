# 🤖 MCP Auto-Selection Intelligence Guide

**Datum:** 2025-10-07
**Feature:** Automatische MCP Server Auswahl durch Claude Code
**Status:** ✅ Aktiv und intelligent

---

## 🎯 ANTWORT: JA, CLAUDE CODE ENTSCHEIDET AUTOMATISCH!

**Claude Code analysiert deine Anfrage und wählt automatisch den passenden MCP Server:**

- 🔍 **Erkennt Kontext** aus deiner Frage
- 🧠 **Wählt optimal** basierend auf Task-Type
- ⚡ **Führt aus** ohne explizite Anweisung
- 📊 **Kombiniert** mehrere MCP Server wenn nötig

---

## 🧠 WIE FUNKTIONIERT DIE AUTO-SELEKTION?

### Intelligente Trigger-Erkennung

Claude Code analysiert deine Anfrage auf Keywords, Kontext und Intent:

```yaml
Wenn du fragst:
  "Show me all tables in the database"

Claude Code denkt:
  1. Keywords: "tables", "database" → Database Query
  2. Intent: Schema Exploration
  3. MCP Auswahl: mysql-database
  4. Tool: list_tables()
  ✅ Automatisch ausgeführt!
```

### Multi-MCP Orchestration

Bei komplexen Aufgaben kombiniert Claude Code mehrere MCP Server:

```yaml
Wenn du fragst:
  "Login to admin panel and show me how many calls
   are in the database today"

Claude Code orchestriert:
  1. Puppeteer → Login to admin panel
  2. MySQL → Query calls table (WHERE date=today)
  3. Sequential → Combine results
  ✅ Automatisch koordiniert!
```

---

## 📊 MCP AUTO-SELECTION MATRIX

### Database Queries → mysql-database

**Trigger Keywords:**
```
"database", "table", "query", "SQL", "SELECT", "INSERT",
"users", "customers", "calls", "how many", "export",
"schema", "columns", "rows"
```

**Auto-Selected For:**
```bash
✅ "How many users are in the database?"
✅ "Show me all calls from today"
✅ "What tables exist?"
✅ "Export customers to CSV"
✅ "What's the structure of the billing_alerts table?"
```

**NOT Auto-Selected:**
```bash
❌ "What is a database?" (General question, no action)
❌ "Explain SQL syntax" (Educational, no query)
```

---

### Cache Operations → redis-cache

**Trigger Keywords:**
```
"cache", "Redis", "key", "session", "store", "retrieve",
"cached value", "cache statistics", "memory"
```

**Auto-Selected For:**
```bash
✅ "What's in the Redis cache?"
✅ "Get the value for key 'session_123'"
✅ "Show me cache statistics"
✅ "How many keys are cached?"
✅ "Clear the cache for user 456"
```

**NOT Auto-Selected:**
```bash
❌ "What is Redis?" (Educational)
❌ "Should I use caching?" (Advisory)
```

---

### Browser Testing → puppeteer

**Trigger Keywords:**
```
"login", "website", "page", "browser", "click", "navigate",
"screenshot", "admin panel", "test", "UI", "E2E"
```

**Auto-Selected For:**
```bash
✅ "Login to the admin panel"
✅ "Take a screenshot of https://api.askproai.de"
✅ "Check if the calls page loads correctly"
✅ "Test the admin login flow"
✅ "Navigate to /admin/customers and verify it works"
```

**Context-Aware:**
```bash
✅ "Check the admin dashboard"
   → Puppeteer: Navigate + Screenshot

✅ "Verify all admin pages work"
   → Puppeteer: Multi-page E2E test
```

---

### Complex Analysis → sequential

**Trigger Keywords:**
```
"analyze", "why", "debug", "investigate", "explain",
"optimize", "suggest", "evaluate", "compare", "root cause"
```

**Auto-Selected For:**
```bash
✅ "Why is the call logging slow?"
✅ "Analyze the database schema for optimization"
✅ "Debug the billing alert system"
✅ "Suggest improvements for the architecture"
✅ "Compare these two approaches"
```

**Combines With Other MCP:**
```bash
✅ "Analyze database performance"
   → Sequential (analysis) + MySQL (query metrics)

✅ "Debug why Redis cache is slow"
   → Sequential (reasoning) + Redis (statistics)
```

---

## 🎯 PRAKTISCHE BEISPIELE

### Beispiel 1: Einfache Database Query

**Deine Frage:**
```
"How many appointments are in the database?"
```

**Claude Code denkt:**
```yaml
Analysis:
  Keywords: ["appointments", "database", "how many"]
  Intent: Count query
  Complexity: Simple

Auto-Selection:
  MCP: mysql-database
  Tool: read_query
  Query: "SELECT COUNT(*) FROM appointments"

Execution:
  ✅ Automatisch ausgeführt
  ✅ Ergebnis angezeigt
```

---

### Beispiel 2: Multi-MCP Orchestration

**Deine Frage:**
```
"Login to admin panel and check if there are any
 new calls in the last hour"
```

**Claude Code orchestriert:**
```yaml
Step 1 - Browser Automation:
  MCP: puppeteer
  Action: Navigate to /admin/login
  Action: Fill credentials
  Action: Click login button
  Result: ✅ Logged in

Step 2 - Database Query:
  MCP: mysql-database
  Query: "SELECT COUNT(*) FROM calls
          WHERE created_at >= NOW() - INTERVAL 1 HOUR"
  Result: ✅ 15 new calls

Step 3 - Response:
  Combines: Puppeteer session + Database result
  Output: "Logged in successfully. Found 15 new calls
           in the last hour."
```

---

### Beispiel 3: Intelligent Tool Selection

**Deine Frage:**
```
"Why is the billing system generating duplicate alerts?"
```

**Claude Code analysiert:**
```yaml
Analysis Phase (Sequential MCP):
  Complexity: High
  Type: Root cause analysis
  Requires: Multi-step reasoning

Investigation Steps:
  1. Sequential: Break down problem
  2. MySQL: Query billing_alerts table
  3. MySQL: Check billing_alert_suppressions
  4. Sequential: Analyze patterns
  5. MySQL: Verify duplicate detection logic

Auto-Selection:
  Primary: sequential (complex reasoning)
  Supporting: mysql-database (data analysis)

Execution:
  ✅ Systematic investigation
  ✅ Data-driven conclusions
  ✅ Actionable recommendations
```

---

## 🔧 OPTIMIERUNG DER AUTO-SELEKTION

### Settings für intelligente MCP-Nutzung

Claude Code nutzt bereits intelligente Heuristiken, aber du kannst das Verhalten beeinflussen:

#### 1. Explizite MCP-Auswahl (Override)

```bash
# Wenn du einen bestimmten MCP forcieren willst:
"Use Puppeteer to check the database size"
# → Puppeteer wird verwendet (obwohl MySQL besser wäre)

# Vs. automatisch:
"Check the database size"
# → MySQL wird automatisch gewählt (optimal)
```

#### 2. Kontext-Hinweise geben

```bash
# Besser spezifizieren für optimale Auswahl:
❌ Vage: "Check the system"
✅ Klar: "Check how many active users are in the database"

❌ Vage: "Test something"
✅ Klar: "Test the admin login flow with Puppeteer"
```

#### 3. Multi-Tool Workflows beschreiben

```bash
# Komplexe Workflows explizit beschreiben:
"Analyze the call system:
1. Query call statistics from database
2. Check Redis cache hit rates
3. Test the call UI in browser
4. Provide optimization recommendations"

→ Claude Code orchestriert automatisch:
   - MySQL für Statistics
   - Redis für Cache Analysis
   - Puppeteer für UI Test
   - Sequential für Recommendations
```

---

## 📋 DECISION TREE BEISPIELE

### Decision Tree: "Check the calls"

```
User: "Check the calls"
│
├─ Context: No URL mentioned
│  └─ Interpretation: Database query
│     └─ MCP: mysql-database
│        └─ Action: SELECT * FROM calls LIMIT 10
│
└─ Context: "on the website" mentioned
   └─ Interpretation: UI test
      └─ MCP: puppeteer
         └─ Action: Navigate to /admin/calls
```

### Decision Tree: "Optimize the system"

```
User: "Optimize the system"
│
├─ Step 1: Complex analysis needed
│  └─ MCP: sequential
│     └─ Break down into sub-tasks
│
├─ Step 2: Identify bottlenecks
│  └─ MCP: mysql-database
│     └─ Query slow query logs
│
├─ Step 3: Check cache efficiency
│  └─ MCP: redis-cache
│     └─ Analyze hit/miss rates
│
└─ Step 4: Synthesize recommendations
   └─ MCP: sequential
      └─ Data-driven suggestions
```

---

## ⚙️ ADVANCED: MCP PRIORITY RULES

Claude Code verwendet folgende Prioritätsregeln:

### 1. Specificity Rules
```yaml
Höchste Priorität: Explizite MCP-Nennung
  "Use Puppeteer to..."

Hohe Priorität: Eindeutige Keywords
  "database query" → mysql-database
  "browser test" → puppeteer

Moderate Priorität: Kontext-Inferenz
  "check the admin panel" → puppeteer (inferred)

Niedrige Priorität: Default Reasoning
  Vage Frage → sequential (analysis first)
```

### 2. Complexity-Based Routing
```yaml
Simple Tasks:
  Direct tool execution
  Example: "Count users" → mysql-database only

Moderate Tasks:
  2-3 MCP server combination
  Example: "Login and check data"
    → puppeteer + mysql-database

Complex Tasks:
  Full orchestration with sequential
  Example: "Analyze and optimize system"
    → sequential + mysql-database + redis-cache + puppeteer
```

### 3. Context Retention
```yaml
Session Memory:
  Claude Code remembers previous context

  Example:
    You: "Login to admin panel"
    Claude: [Uses Puppeteer, logs in]

    You: "Now check the database"
    Claude: [Uses MySQL, maintains Puppeteer session]

    You: "Take a screenshot"
    Claude: [Uses Puppeteer with existing session]
```

---

## 🎓 BEST PRACTICES

### 1. Lass Claude Code entscheiden (meistens)

```bash
✅ EMPFOHLEN:
"Show me database statistics"
→ Claude wählt automatisch mysql-database

❌ UNNÖTIG:
"Use the mysql-database MCP server to show me database statistics"
→ Explizit, aber redundant
```

### 2. Sei spezifisch bei Multi-Tool Tasks

```bash
✅ KLAR:
"Login to admin, check calls table, and analyze patterns"
→ Puppeteer + MySQL + Sequential

❌ VAGE:
"Check the system"
→ Unklar, was genau gemeint ist
```

### 3. Vertraue der Orchestration

```bash
✅ VERTRAUEN:
"Debug why billing alerts are duplicated"
→ Claude koordiniert automatisch:
   - Sequential für Reasoning
   - MySQL für Data Analysis
   - Systematische Investigation

❌ MICRO-MANAGEMENT:
"First use sequential to think, then mysql to query,
 then sequential to analyze..."
→ Unnötig detailliert
```

---

## 📊 MCP USAGE STATISTICS TRACKING

Claude Code kann MCP-Nutzung tracken (wenn konfiguriert):

```yaml
Session Statistics:
  mysql-database:
    Calls: 15
    Automatic: 14
    Explicit: 1
    Success Rate: 100%

  puppeteer:
    Calls: 3
    Automatic: 3
    Explicit: 0
    Success Rate: 100%

  sequential:
    Calls: 2
    Automatic: 2
    Explicit: 0
    Success Rate: 100%
```

---

## 🚀 QUICK REFERENCE

### Automatische MCP-Auswahl - Cheat Sheet

| Deine Frage enthält... | Auto-Selected MCP | Beispiel |
|------------------------|-------------------|----------|
| "database", "table", "SQL" | mysql-database | "Show tables" |
| "cache", "Redis", "key" | redis-cache | "Get cache value" |
| "login", "browser", "screenshot" | puppeteer | "Check admin panel" |
| "analyze", "why", "debug" | sequential | "Debug issue" |
| Multiple indicators | Multi-MCP | "Login + query data" |

### Override-Keywords

| Keyword | Effect |
|---------|--------|
| "Use [MCP]..." | Forces specific MCP |
| "Don't use [MCP]" | Excludes specific MCP |
| "Only [MCP]" | Restricts to one MCP |

---

## ✅ ZUSAMMENFASSUNG

**JA, Claude Code entscheidet automatisch:**

✅ **Intelligent:** Analysiert Kontext und Intent
✅ **Optimal:** Wählt besten MCP für die Aufgabe
✅ **Kombiniert:** Orchestriert mehrere MCP bei Bedarf
✅ **Transparent:** Du siehst welcher MCP verwendet wird
✅ **Override:** Du kannst explizit wählen wenn nötig

**Du musst NICHTS manuell wählen** - Claude Code macht es automatisch richtig!

---

**Best Practice:** Formuliere deine Fragen klar, und Claude Code wählt automatisch die optimalen MCP Server! 🚀

---

**Created:** 2025-10-07
**Mode:** Auto-Selection Intelligence
**Status:** ✅ Active and Optimized
