# 🎨 Visual Structure for Notion

## 🏠 Main Dashboard View

```
┌─────────────────────────────────────────────────────────────┐
│                 🏠 AskProAI Documentation Hub               │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐    │
│  │ 🚀           │  │ 💼           │  │ 🔌           │    │
│  │ Quick Start  │  │  Business    │  │ Integrations │    │
│  │              │  │  Platform    │  │     Hub      │    │
│  └──────────────┘  └──────────────┘  └──────────────┘    │
│                                                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐    │
│  │ 🛠️           │  │ 📚           │  │ 📋           │    │
│  │  Technical   │  │  Developer   │  │ Operations & │    │
│  │    Docs      │  │  Resources   │  │ Maintenance  │    │
│  └──────────────┘  └──────────────┘  └──────────────┘    │
│                                                             │
│  📊 Platform Status    🔍 Quick Search    📈 Recent Updates│
│  ─────────────────    ──────────────    ─────────────────│
│  ✅ All Systems        [Search box...]    • CI/CD Guide   │
│     Operational                           • API Updates    │
│                                          • New Features   │
└─────────────────────────────────────────────────────────────┘
```

## 🔌 Integrations Hub Layout

```
┌─────────────────────────────────────────────────────────────┐
│ 🏠 > 🔌 Integrations Hub                                    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  📞 Retell.ai          📅 Cal.com           💳 Stripe      │
│  ┌────────────┐       ┌────────────┐       ┌────────────┐ │
│  │ Phone AI   │       │ Calendar   │       │ Payments   │ │
│  │ • Setup    │       │ • Setup    │       │ • Setup    │ │
│  │ • Webhooks │       │ • API v2   │       │ • Webhooks │ │
│  │ • Agents   │       │ • Events   │       │ • Testing  │ │
│  │ Status: 🟢 │       │ Status: 🟢 │       │ Status: 🟢 │ │
│  └────────────┘       └────────────┘       └────────────┘ │
│                                                             │
│  📧 Email System      🤖 MCP Servers       🔗 Others       │
│  ┌────────────┐       ┌────────────┐       ┌────────────┐ │
│  │ SMTP/API   │       │ 20+ Servers│       │ • Twilio   │ │
│  │ • Config   │       │ • Setup    │       │ • Sentry   │ │
│  │ • Templates│       │ • Usage    │       │ • DeepL    │ │
│  │ Status: 🟢 │       │ Status: 🟢 │       │ Coming...  │ │
│  └────────────┘       └────────────┘       └────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

## 📋 Page Header Template

```
┌─────────────────────────────────────────────────────────────┐
│ 🏠 Home > 🔌 Integrations > 📞 Retell.ai                   │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  # Retell.ai Phone AI Integration                          │
│                                                             │
│  ┌─────────────────────────────────────────┐              │
│  │ ⚡ Quick Actions                         │              │
│  │ • Test Connection  • View Logs          │              │
│  │ • Sync Agents     • Check Webhooks      │              │
│  └─────────────────────────────────────────┘              │
│                                                             │
│  📍 On this page:                                          │
│  [Overview] [Setup] [Configuration] [Troubleshooting]      │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

## 🎯 Status Indicators

```
🟢 Operational - Everything working normally
🟡 Degraded - Some issues but functional
🔴 Down - Service unavailable
🔵 Maintenance - Scheduled maintenance
⚪ Unknown - Status cannot be determined
```

## 📊 Database View Examples

### API Endpoints Database View
```
┌─────────────────────────────────────────────────────────────┐
│ API Endpoints                               [+ New] [Filter]│
├────────────────┬────────┬─────────────┬──────────┬────────┤
│ Endpoint       │ Method │ Category    │ Auth     │ Status │
├────────────────┼────────┼─────────────┼──────────┼────────┤
│ /api/calls     │ GET    │ Calls       │ Required │ 🟢     │
│ /api/calls/:id │ GET    │ Calls       │ Required │ 🟢     │
│ /api/webhook   │ POST   │ Webhooks    │ Special  │ 🟢     │
│ /api/auth      │ POST   │ Auth        │ None     │ 🟢     │
└────────────────┴────────┴─────────────┴──────────┴────────┘
```

### Troubleshooting KB View
```
┌─────────────────────────────────────────────────────────────┐
│ Troubleshooting Knowledge Base             🔍 Search: [....]│
├─────────────────────────┬──────────┬──────────┬───────────┤
│ Issue                   │ Category │ Severity │ Updated   │
├─────────────────────────┼──────────┼──────────┼───────────┤
│ 🔴 Webhook not firing   │ Retell   │ High     │ Today     │
│ 🟡 Slow API responses   │ API      │ Medium   │ Yesterday │
│ 🟢 Email not sending    │ Email    │ Low      │ Last week │
└─────────────────────────┴──────────┴──────────┴───────────┘
```

## 🎨 Color Scheme

- **Primary**: Blue (#0066CC) - Links, buttons
- **Success**: Green (#00AA00) - Operational status
- **Warning**: Orange (#FF9900) - Degraded status
- **Danger**: Red (#CC0000) - Down status, errors
- **Info**: Blue (#0099FF) - Information callouts
- **Background**: Light gray (#F5F5F5) - Code blocks

## 📐 Layout Guidelines

1. **Hierarchy**: Max 3 levels deep
2. **Page Width**: Optimized for 1200px
3. **Sections**: Use H2 for main sections, H3 for subsections
4. **Lists**: Use bullet points for unordered, numbers for steps
5. **Code Blocks**: Always specify language for syntax highlighting
6. **Tables**: Use for structured data, keep columns minimal
7. **Callouts**: Use for warnings, tips, important notes

## 🔗 Navigation Patterns

### Breadcrumbs
```
🏠 Home > 🔌 Integrations > 📞 Retell.ai > Webhook Configuration
```

### Related Pages Section
```
## 🔗 Related Documentation
- [Cal.com Integration](link) - Calendar system integration
- [API Authentication](link) - How to authenticate API calls
- [Troubleshooting Guide](link) - Common issues and solutions
```

### Quick Jump Menu
```
## 📍 Quick Navigation
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Configuration](#configuration)
- [Testing](#testing)
- [Troubleshooting](#troubleshooting)
```