# Retell AI - Master Documentation Index

**Last Updated**: 2025-10-25  
**Purpose**: Central navigation for all Retell AI documentation

---

## 🚀 Quick Start Guides

### Agent Creation & Deployment
- **[RETELL_AGENT_CREATION_COMPLETE_GUIDE_2025.md](RETELL_AGENT_CREATION_COMPLETE_GUIDE_2025.md)** ⭐ **NEW** - Complete workflow LLM-based agents
- [RETELL_AGENT_DEPLOYMENT_COMPLETE_GUIDE.md](RETELL_AGENT_DEPLOYMENT_COMPLETE_GUIDE.md) - Deployment process
- [RETELL_AGENT_FLOW_CREATION_GUIDE.md](RETELL_AGENT_FLOW_CREATION_GUIDE.md) - Flow-based agents (legacy)
- [DEPLOYMENT_PROZESS_RETELL_FLOW.md](DEPLOYMENT_PROZESS_RETELL_FLOW.md) - Flow deployment

### Troubleshooting
- **[RETELL_TROUBLESHOOTING_GUIDE_2025.md](RETELL_TROUBLESHOOTING_GUIDE_2025.md)** ⭐ **NEW** - Common errors & solutions
- [ROOT_CAUSE_ANALYSIS_2025-10-22.md](ROOT_CAUSE_ANALYSIS_2025-10-22.md) - V16 issues
- [ROOT_CAUSE_ANALYSIS_2025-10-23_CALL_1541.md](ROOT_CAUSE_ANALYSIS_2025-10-23_CALL_1541.md) - Call analysis

### Quick References
- **[RETELL_API_QUICK_REFERENCE_2025.md](RETELL_API_QUICK_REFERENCE_2025.md)** ⭐ **NEW** - API endpoints & examples
- [VOICE_AI_QUICK_REFERENCE.md](VOICE_AI_QUICK_REFERENCE.md) - Voice AI patterns
- [AGENT_IDS_REFERENZ.md](AGENT_IDS_REFERENZ.md) - Agent IDs

---

## 📚 By Topic

### 1. API Integration
- [RETELL_CALCOM_INTEGRATION_SUMMARY_2025-09-26.md](RETELL_CALCOM_INTEGRATION_SUMMARY_2025-09-26.md)
- [RETELL_INTEGRATION_FIXED.md](RETELL_INTEGRATION_FIXED.md)

### 2. Voice AI UX
- [VOICE_AI_CONVERSATION_DESIGN_GUIDE_2025.md](VOICE_AI_CONVERSATION_DESIGN_GUIDE_2025.md) ⭐
- [VOICE_AI_DIALOG_EXAMPLES_BEFORE_AFTER.md](VOICE_AI_DIALOG_EXAMPLES_BEFORE_AFTER.md)
- [VOICE_AI_UX_ANALYSIS_INDEX.md](VOICE_AI_UX_ANALYSIS_INDEX.md)

### 3. Cal.com Integration
- [CALCOM_SERVICE_HOSTS_QUICK_START.md](CALCOM_SERVICE_HOSTS_QUICK_START.md)
- [CALCOM_HOSTS_QUICK_REFERENCE.md](CALCOM_HOSTS_QUICK_REFERENCE.md)
- [CALCOM_SERVICE_HOSTS_SETUP_GUIDE.md](CALCOM_SERVICE_HOSTS_SETUP_GUIDE.md)

### 4. Function Calls & Tools
- [FUNCTION_ANALYSIS_INDEX.md](FUNCTION_ANALYSIS_INDEX.md)
- [FUNCTION_NODES_ANALYSIS_2025-10-23.md](FUNCTION_NODES_ANALYSIS_2025-10-23.md)
- [FUNCTION_NODES_VISUAL_COMPARISON_2025-10-23.md](FUNCTION_NODES_VISUAL_COMPARISON_2025-10-23.md)

### 5. Cost Management
- [RETELL_COST_FIX_COMPLETE_2025-10-07.md](RETELL_COST_FIX_COMPLETE_2025-10-07.md)
- [RETELL_COST_DISPLAY_MAPPING.md](RETELL_COST_DISPLAY_MAPPING.md)

### 6. Call Analysis
- [RETELL_CALL_FLOWS_COMPLETE_2025-10-11.md](RETELL_CALL_FLOWS_COMPLETE_2025-10-11.md)
- [CALL_ANALYSIS_2025-10-22_V16_FIX.md](CALL_ANALYSIS_2025-10-22_V16_FIX.md)
- [RETELL_BOOKING_FAILURE_RCA_2025-10-13.md](RETELL_BOOKING_FAILURE_RCA_2025-10-13.md)

### 7. Agent Updates
- [RETELL_AGENT_UPDATE_GUIDE_2025-10-04.md](RETELL_AGENT_UPDATE_GUIDE_2025-10-04.md)
- [RETELL_AGENT_UPDATED_INSTRUCTIONS_2025-10-01.md](RETELL_AGENT_UPDATED_INSTRUCTIONS_2025-10-01.md)

### 8. Advanced Features
- [HIDDEN_NUMBER_SUPPORT_V85_FINAL.md](HIDDEN_NUMBER_SUPPORT_V85_FINAL.md)
- [RETELL_PROMPT_EXTENSION_RESCHEDULE_2025-10-04.md](RETELL_PROMPT_EXTENSION_RESCHEDULE_2025-10-04.md)

---

## 🔍 By Use Case

### Creating a New Agent
1. Read: [RETELL_AGENT_CREATION_COMPLETE_GUIDE_2025.md](RETELL_AGENT_CREATION_COMPLETE_GUIDE_2025.md) ⭐
2. Check: [RETELL_API_QUICK_REFERENCE_2025.md](RETELL_API_QUICK_REFERENCE_2025.md) for API examples
3. Follow: Step-by-step workflow
4. Deploy: Using deployment guide

### Debugging Function Call Issues
1. Check: [RETELL_TROUBLESHOOTING_GUIDE_2025.md](RETELL_TROUBLESHOOTING_GUIDE_2025.md) ⭐
2. Review: [FUNCTION_ANALYSIS_INDEX.md](FUNCTION_ANALYSIS_INDEX.md)
3. Analyze: Call logs using analysis guides

### Improving Voice UX
1. Study: [VOICE_AI_CONVERSATION_DESIGN_GUIDE_2025.md](VOICE_AI_CONVERSATION_DESIGN_GUIDE_2025.md)
2. Review: [VOICE_AI_DIALOG_EXAMPLES_BEFORE_AFTER.md](VOICE_AI_DIALOG_EXAMPLES_BEFORE_AFTER.md)
3. Apply: Best practices from examples

### Cal.com Integration
1. Start: [CALCOM_SERVICE_HOSTS_QUICK_START.md](CALCOM_SERVICE_HOSTS_QUICK_START.md)
2. Reference: [CALCOM_HOSTS_QUICK_REFERENCE.md](CALCOM_HOSTS_QUICK_REFERENCE.md)
3. Setup: [CALCOM_SERVICE_HOSTS_SETUP_GUIDE.md](CALCOM_SERVICE_HOSTS_SETUP_GUIDE.md)

---

## 🎯 Critical Knowledge

### Flow-based vs LLM-based Agents
- **Flow-based**: Prompt-based transitions, ~10% success rate, complex
- **LLM-based**: Natural function calling, ~99% success rate, simple
- **Recommendation**: Use LLM-based for new agents ⭐

### Common Pitfalls
1. ❌ Using wrong voice_id → 404 error
2. ❌ Prompt-based transitions → Functions not called
3. ❌ Missing bestaetigung parameter → Booking fails
4. ❌ Not checking `/list-voices` → Invalid voice

### Best Practices
1. ✅ Create Retell LLM first, then agent
2. ✅ Always verify voice_id with `/list-voices`
3. ✅ Use LLM-based agents for reliability
4. ✅ Monitor backend logs during testing
5. ✅ Test with real phone calls, not just API

---

## 📊 Version History

### Latest (2025-10-25)
- ✅ LLM-based agent creation workflow
- ✅ 404 error troubleshooting (voice_id)
- ✅ Complete API reference

### Previous Versions
- V85: Hidden number support
- V81: Prompt improvements
- V78: Architecture changes
- V17: Function call improvements

---

## 🔗 External Resources

- [Retell AI Official Docs](https://docs.retellai.com)
- [Retell AI Changelog](https://www.retellai.com/changelog)
- [Retell AI Dashboard](https://dashboard.retellai.com)

---

**Navigation**: This is the master index. All other documents are organized by topic above.
