# Voice AI Agent Documentation - Quick Start Guide

**⏱️ 5 minutes to full productivity**

---

## 📖 What You Get

- **Interactive Testing** - Test every function with real API calls
- **Complete Specs** - Every parameter, response, and error documented
- **Data Flow Diagrams** - Understand system architecture visually
- **Copy-Paste Examples** - cURL commands ready to use
- **Feature Matrix** - Track implementation status at a glance

---

## 🚀 Quick Start (3 Steps)

### Step 1: Open Documentation

```bash
# Open in browser
open /var/www/api-gateway/public/docs/voice-agent/index.html

# Or visit hosted version
https://api.askpro.ai/docs/voice-agent/
```

### Step 2: Navigate to a Function

Click on any function in the sidebar (e.g., "collect_appointment_info")

### Step 3: Test It!

1. Click **"Interactive Test"** tab
2. Fill in the form
3. Click **"Test Function Call"**
4. See the response in real-time

**That's it!** You're now testing live API functions.

---

## 🎯 Common Tasks

### Task 1: Test a Function

**Goal**: Test `collect_appointment_info` with minimal data

**Steps**:
1. Navigate to **collect_appointment_info**
2. Click **"Interactive Test"** tab
3. Fill in:
   - `call_id`: `test-call-123`
   - `service_name`: `Herrenhaarschnitt`
   - `customer_phone`: `+4915123456789`
4. Click **"Test Function Call"**
5. ✅ See success response

**Time**: 30 seconds

---

### Task 2: Copy a cURL Example

**Goal**: Get a working cURL command to use in your terminal

**Steps**:
1. Navigate to any function
2. Click **"Examples"** tab
3. Find the example you need
4. Click **"Copy"** button
5. Paste in terminal
6. ✅ Execute API call

**Time**: 15 seconds

---

### Task 3: Understand System Architecture

**Goal**: See how Retell → Backend → Cal.com integrates

**Steps**:
1. Click **"Architecture"** in sidebar
2. View **"Overall System Flow"** diagram
3. Or navigate to specific function → **"Data Flow"** tab
4. ✅ Understand complete data flow

**Time**: 2 minutes

---

### Task 4: Check Implementation Status

**Goal**: See what's implemented and what's missing

**Steps**:
1. Click **"Feature Matrix"** in sidebar
2. View table with all functions
3. Check Status, Priority, Tests columns
4. Click **"View Docs"** for details
5. ✅ Complete project overview

**Time**: 1 minute

---

### Task 5: Find All Parameters

**Goal**: Know every parameter for a function

**Steps**:
1. Navigate to function
2. Click **"Documentation"** tab (default)
3. Scroll to **"Required Parameters"** table
4. Scroll to **"Optional Parameters"** table
5. ✅ Complete parameter reference

**Time**: 30 seconds

---

## 🔥 Power User Tips

### Tip 1: Use Keyboard Shortcuts

```
# Navigation
Ctrl/Cmd + F        → Search page
Arrow Keys          → Navigate sections
Tab                 → Navigate form fields
Enter               → Submit test form
```

### Tip 2: Export Function Specs

```javascript
// Click "Export JSON" in header
// Downloads complete function specifications
// Use in automation/scripts
```

### Tip 3: Copy Response for Bug Reports

```
1. Test function
2. Click "Copy Response" button
3. Paste in GitHub issue/Slack
4. Include status code and full response
```

### Tip 4: Use Examples as Templates

```bash
# Copy example cURL command
# Modify parameters
# Run in terminal
# Perfect for testing different scenarios
```

### Tip 5: Understand Errors with Diagrams

```
1. Navigate to function → "Data Flow" tab
2. View "Error Handling Flow" diagram
3. See what happens when things fail
4. Understand compensation/rollback logic
```

---

## 📚 Documentation Structure

```
Overview
├─ Dashboard              # Statistics and quick start
├─ Feature Matrix         # All functions status table
└─ Architecture           # System-wide diagrams

Functions (per function)
├─ Documentation          # Complete specification
│   ├─ Endpoint
│   ├─ Parameters (required + optional)
│   ├─ Validation Rules
│   ├─ Response Schema
│   └─ Error Codes
├─ Interactive Test       # Live API testing
│   ├─ Form Builder
│   └─ Response Display
├─ Examples              # Copy-paste examples
│   ├─ Minimal Fields
│   ├─ Complete Request
│   ├─ Error Cases
│   └─ cURL Commands
└─ Data Flow             # Diagrams
    ├─ Sequence Diagram
    ├─ Architecture Diagram
    └─ Error Flow Diagram

API Reference
├─ Webhooks              # Webhook specifications
├─ Endpoints             # All endpoints reference
└─ Schemas               # Data structure docs

Testing
├─ Interactive Playground # Test all functions
└─ Test Scenarios        # Pre-defined test cases
```

---

## 🎓 Learning Path

### Beginner (5 minutes)

1. ✅ Open documentation
2. ✅ Navigate to `collect_appointment_info`
3. ✅ Read "Documentation" tab
4. ✅ View "Examples" tab
5. ✅ Try "Interactive Test"

**You now know**: How to read specs and test functions

### Intermediate (15 minutes)

1. ✅ Complete Beginner path
2. ✅ Study "Data Flow" diagrams
3. ✅ Test all examples (happy path + errors)
4. ✅ Review "Feature Matrix"
5. ✅ Check "Architecture" section

**You now know**: System architecture and all functions

### Advanced (30 minutes)

1. ✅ Complete Intermediate path
2. ✅ Read JSON function definition
3. ✅ Understand JSON schema
4. ✅ Review validation rules
5. ✅ Study error handling flows
6. ✅ Test edge cases

**You now know**: Complete system internals and can extend documentation

---

## 🛠️ For Different Roles

### Frontend Developer

**Focus**:
- Interactive Test forms (see what data is needed)
- Response schemas (understand what you get back)
- Error responses (handle edge cases)

**Start Here**:
1. Feature Matrix → identify functions you'll use
2. Each function → Documentation tab → Response Schema
3. Each function → Examples → see real responses

**Time**: 10 minutes

---

### Backend Developer

**Focus**:
- Complete parameter specifications
- Validation rules
- Business logic flows
- Integration points

**Start Here**:
1. Architecture → Overall System Flow
2. Each function → Documentation → Parameters
3. Each function → Data Flow → Sequence Diagrams
4. JSON definitions for implementation details

**Time**: 20 minutes

---

### QA Engineer

**Focus**:
- Test scenarios
- Expected responses
- Error cases
- Edge cases

**Start Here**:
1. Feature Matrix → check test status
2. Interactive Test → try all functions
3. Examples → validation errors
4. Test Scenarios section

**Time**: 15 minutes

---

### Product Manager

**Focus**:
- Feature status
- Implementation priorities
- System capabilities
- What's missing

**Start Here**:
1. Overview → Dashboard (statistics)
2. Feature Matrix → complete status
3. Architecture → system capabilities
4. Roadmap (if available)

**Time**: 5 minutes

---

### Technical Writer

**Focus**:
- Documentation structure
- JSON schema
- Examples format
- How to update

**Start Here**:
1. Read `README.md` (complete guide)
2. Study `function-definition.schema.json`
3. Review `collect_appointment_info.json` as template
4. Learn `generate-voice-docs.py` script

**Time**: 30 minutes

---

## 🚨 Troubleshooting

### Problem: "Function test returns 401 Unauthorized"

**Solution**:
```javascript
// You need authentication
// Check with backend team for test credentials
// Or use examples which show expected responses
```

### Problem: "Diagrams not rendering"

**Solution**:
```javascript
// Mermaid.js may not have loaded
// Refresh page
// Check browser console for errors
// Verify internet connection (CDN dependency)
```

### Problem: "Can't find a specific function"

**Solution**:
```
1. Use browser search (Ctrl/Cmd + F)
2. Check Feature Matrix for complete list
3. Function may not be documented yet
4. Check JSON files in functions/ directory
```

### Problem: "Response shows error I don't understand"

**Solution**:
```
1. Check function → Data Flow → Error Handling diagram
2. Review function → Documentation → Error Responses
3. Copy error response
4. Create GitHub issue with details
```

---

## 📞 Getting Help

### Documentation Issues

```
# GitHub Issues
https://github.com/askpro-ai/api-gateway/issues

# Template
Title: [DOCS] Brief description
Body:
- What you were trying to do
- What you expected
- What happened instead
- Screenshots if applicable
```

### Technical Questions

```
# Slack
#voice-agent-docs channel

# Email
docs@askpro.ai
```

### Feature Requests

```
# GitHub Discussions
https://github.com/askpro-ai/api-gateway/discussions

# Template
Title: [REQUEST] Feature description
Body:
- Use case
- Expected behavior
- Why it's useful
- Priority (optional)
```

---

## 🎉 Next Steps

### You've Completed the Quick Start!

**You can now**:
- ✅ Navigate documentation
- ✅ Test functions live
- ✅ Copy examples
- ✅ Understand architecture
- ✅ Find what you need

### Go Deeper:

1. **Full Documentation**: Read `README.md` for complete details
2. **JSON Schema**: Study `function-definition.schema.json`
3. **Example Function**: Review `collect_appointment_info.json`
4. **Generator**: Learn `generate-voice-docs.py` for automation
5. **System Overview**: Read `VOICE_AGENT_DOCUMENTATION_SYSTEM.md`

### Start Building:

```bash
# Test all functions you'll use
# Note edge cases and errors
# Build error handling in your app
# Reference examples for integration
# Ask questions in Slack
```

---

## 📊 Cheat Sheet

### Quick Actions

| Action | Steps |
|--------|-------|
| Test function | Navigate → Interactive Test → Fill form → Test |
| Copy cURL | Navigate → Examples → Click Copy |
| View diagram | Navigate → Data Flow → View diagram |
| Check status | Feature Matrix → Check row |
| Export specs | Header → Export JSON |

### Common URLs

| Resource | URL |
|----------|-----|
| Local docs | `file:///var/www/api-gateway/public/docs/voice-agent/index.html` |
| Staging | `https://staging.askpro.ai/docs/voice-agent/` |
| Production | `https://api.askpro.ai/docs/voice-agent/` |
| GitHub | `https://github.com/askpro-ai/api-gateway` |

### File Locations

| File | Path |
|------|------|
| Main docs | `/var/www/api-gateway/public/docs/voice-agent/index.html` |
| This guide | `/var/www/api-gateway/public/docs/voice-agent/QUICKSTART.md` |
| Complete guide | `/var/www/api-gateway/public/docs/voice-agent/README.md` |
| JSON schema | `/var/www/api-gateway/public/docs/voice-agent/schemas/function-definition.schema.json` |
| Functions | `/var/www/api-gateway/public/docs/voice-agent/functions/*.json` |

---

## ✨ Tips for Success

1. **Start with examples** - Don't read specs first, see working examples
2. **Test early** - Use Interactive Test before writing code
3. **Understand flows** - Study Data Flow diagrams before implementing
4. **Handle errors** - Review all error cases, not just happy path
5. **Ask questions** - Documentation not clear? Let us know!

---

**Happy coding! 🚀**

---

**Document Version**: 1.0.0
**Last Updated**: 2025-11-06
**Estimated Read Time**: 5 minutes
**Estimated Learning Time**: 15-30 minutes