# AskProAI Documentation Tour 🗺️

Welcome! This guided tour will help you navigate our documentation efficiently. Each section builds on the previous one, so follow along in order for the best experience.

## 🎯 Tour Overview

**Duration**: 30-45 minutes  
**Goal**: Understand where to find information quickly  
**Format**: Interactive - open each document as you go  

---

## 📍 Stop 1: The Main Hub (5 min)

### 🏠 [CLAUDE.md](../../CLAUDE.md)
**What it is**: Your documentation home base  
**Why it matters**: Contains links to everything else  

**🔍 Find these sections:**
- Quick Access links at the top
- Current blockers/issues
- Table of contents
- Essential commands

**✏️ Exercise**: Find the database credentials and test the connection

---

## 📍 Stop 2: Quick Reference (5 min)

### ⚡ [CLAUDE_QUICK_REFERENCE.md](../../CLAUDE_QUICK_REFERENCE.md)
**What it is**: Cheat sheet for common tasks  
**Why it matters**: Saves time on repetitive tasks  

**🔍 Key sections to bookmark:**
- Copy-paste commands
- Common fixes
- API endpoints
- Testing commands

**✏️ Exercise**: Run the "Quick Health Check" commands

---

## 📍 Stop 3: Understanding the Flow (10 min)

### 🔄 [PHONE_TO_APPOINTMENT_FLOW.md](../../PHONE_TO_APPOINTMENT_FLOW.md)
**What it is**: Visual guide to our core business logic  
**Why it matters**: Understand how everything connects  

**🔍 Follow the flow:**
1. Customer calls in
2. AI processes the call
3. Appointment gets booked
4. Confirmations sent

**✏️ Exercise**: Identify which service handles webhook processing

---

## 📍 Stop 4: Troubleshooting (5 min)

### 🔧 [TROUBLESHOOTING_DECISION_TREE.md](../../TROUBLESHOOTING_DECISION_TREE.md)
**What it is**: Step-by-step debugging guide  
**Why it matters**: Solve issues systematically  

**🔍 Navigate a sample issue:**
- Start with "System is slow"
- Follow the decision tree
- Note the commands suggested

**✏️ Exercise**: Find the solution for "Webhook not processing"

---

## 📍 Stop 5: Error Patterns (5 min)

### ❌ [ERROR_PATTERNS.md](../../ERROR_PATTERNS.md)
**What it is**: Common errors and solutions  
**Why it matters**: Quick fixes for known issues  

**🔍 Explore:**
- Error categories
- Quick fix section
- Prevention tips

**✏️ Exercise**: Find the fix for a 419 CSRF error

---

## 📍 Stop 6: Best Practices (5 min)

### 🎯 [BEST_PRACTICES_IMPLEMENTATION.md](../../BEST_PRACTICES_IMPLEMENTATION.md)
**What it is**: Coding standards and patterns  
**Why it matters**: Write consistent, quality code  

**🔍 Review:**
- Service pattern examples
- Testing approaches
- Documentation standards

**✏️ Exercise**: Find the MCP server best practices

---

## 📍 Stop 7: Emergency Playbooks (5 min)

### 🚨 [EMERGENCY_RESPONSE_PLAYBOOK.md](../../EMERGENCY_RESPONSE_PLAYBOOK.md)
**What it is**: What to do when things go wrong  
**Why it matters**: Quick action in critical situations  

**🔍 Locate:**
- Severity levels
- Escalation paths
- Recovery procedures

**✏️ Exercise**: What's the first step for a system outage?

---

## 🗺️ Documentation Map

Here's how our documentation is organized:

```
📁 Project Root
├── 📄 CLAUDE.md (Main hub)
├── 📄 CLAUDE_QUICK_REFERENCE.md (Cheat sheet)
├── 📄 README.md (GitHub/public facing)
│
├── 📁 Core Documentation
│   ├── 📄 PHONE_TO_APPOINTMENT_FLOW.md
│   ├── 📄 ERROR_PATTERNS.md
│   ├── 📄 TROUBLESHOOTING_DECISION_TREE.md
│   └── 📄 BEST_PRACTICES_IMPLEMENTATION.md
│
├── 📁 Playbooks
│   ├── 📄 EMERGENCY_RESPONSE_PLAYBOOK.md
│   ├── 📄 5-MINUTEN_ONBOARDING_PLAYBOOK.md
│   └── 📄 CUSTOMER_SUCCESS_RUNBOOK.md
│
├── 📁 Technical Guides
│   ├── 📄 DEPLOYMENT_CHECKLIST.md
│   ├── 📄 INTEGRATION_HEALTH_MONITOR.md
│   └── 📄 KPI_DASHBOARD_TEMPLATE.md
│
├── 📁 docs/
│   ├── 📁 templates/ (Documentation templates)
│   ├── 📁 onboarding/ (This tour!)
│   └── 📁 api/ (API documentation)
│
└── 📁 Historical/Archive
    └── 📄 Various dated files (FEATURE_STATUS_2025-XX-XX.md)
```

## 🔍 Search Strategies

### Finding Information Quickly

1. **Use CLAUDE.md table of contents** - It's your index
2. **Search by date** - Many docs have dates in filenames
3. **Use grep for specifics**:
   ```bash
   grep -r "retell" *.md
   ```
4. **Check templates** - For creating new docs
5. **Look for STATUS files** - For feature-specific info

### Documentation Naming Patterns

- `FEATURE_STATUS_YYYY-MM-DD.md` - Status updates
- `FEATURE_FIX_YYYY-MM-DD.md` - Bug fix documentation  
- `*_PLAYBOOK.md` - Step-by-step guides
- `*_TEMPLATE.md` - Reusable templates
- `*_CHECKLIST.md` - Task lists

## 📝 Your First Documentation Contribution

Now that you've toured the docs, make your first contribution:

1. **Find a typo or outdated section**
2. **Create a branch**: `git checkout -b docs/fix-typo`
3. **Make the fix**
4. **Commit**: `git commit -m "docs: fix typo in documentation tour"`
5. **Push and create PR**

## 🎓 Advanced Documentation

Once comfortable with basics, explore:

- `/docs/templates/` - For creating new documentation
- Integration guides in `/docs/integrations/`
- Architecture documents in `/docs/architecture/`
- API specs in `/docs/api/`

## 💡 Pro Tips

1. **Bookmark these in your browser**:
   - CLAUDE.md
   - QUICK_REFERENCE.md
   - Current sprint's status doc

2. **Set up aliases**:
   ```bash
   alias docs='cd /var/www/api-gateway && ls *.md'
   alias readme='cat /var/www/api-gateway/CLAUDE.md'
   ```

3. **Use the search page**: `/docs/search.html`

4. **Join #documentation Slack channel** for updates

## ✅ Tour Complete!

You've now seen the major documentation areas. Remember:

- 📍 Start with CLAUDE.md for anything
- 🔍 Use search for specific topics  
- 📝 Keep docs updated as you work
- ❓ Ask in Slack if you can't find something

**Next Steps**:
1. Complete the [New Developer Checklist](./new-developer-checklist.md)
2. Read the [Contribution Guidelines](./contribution-guidelines.md)
3. Explore documentation for your first task

Welcome aboard! 🚀