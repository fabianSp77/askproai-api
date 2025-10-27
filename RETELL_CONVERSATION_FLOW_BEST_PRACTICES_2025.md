# Retell.ai Conversation Flow Best Practices (2024/2025)

**Research Date**: 2025-10-23
**Sources**: Official Retell.ai Documentation (docs.retellai.com)
**Focus**: Function Node Configuration, Edge Conditions, Common Pitfalls

---

## Executive Summary

This guide synthesizes official Retell.ai documentation on conversation flow best practices, with emphasis on function node configuration and reliable function triggering. Key findings reveal critical distinctions in `wait_for_result` and `speak_during_execution` settings that directly impact conversation flow timing and user experience.

---

## 1. Function Node Configuration

### 1.1 Core Concept

Function nodes execute API calls or pre-built functions **immediately upon entering the node**. They are NOT intended for conversation but can optionally speak during execution.

**Critical Insight**: The function is invoked the moment the agent transitions into the function node, not based on edge conditions. Edge conditions control WHEN to enter the node, not whether to call the function.

### 1.2 Node Settings Deep Dive

#### **wait_for_result** (Boolean)

Controls whether the agent waits for function execution to complete before transitioning to next node.

**When `wait_for_result: true`** (Recommended for most cases)
- Agent WAITS for function result before attempting transition
- Guarantees result is available in next node
- Transition timing:
  - If `speak_during_execution: true` → transition when result ready AND agent done talking
  - If `speak_during_execution: false` → transition when result ready
  - If user interrupts → transition when result ready AND user done speaking

**When `wait_for_result: false`** (Advanced use cases only)
- Agent does NOT wait for function result
- Function result may not be available in next node
- Transition timing:
  - If `speak_during_execution: true` → transition when agent done talking
  - If `speak_during_execution: false` → transition IMMEDIATELY upon entering node
  - If user interrupts → transition when user done speaking

**Use Cases**:
- `true`: Appointment booking, CRM lookups, availability checks (you need the result)
- `false`: Fire-and-forget logging, analytics tracking, background notifications

---

#### **speak_during_execution** (Boolean)

Controls whether agent speaks while function executes.

**When `speak_during_execution: true`**
- Text input box appears for instructions
- Agent generates utterance like "Let me check that for you"
- Two options:
  - **Prompt**: LLM generates dynamic response
  - **Static Sentence**: Fixed text every time

**When `speak_during_execution: false`**
- Agent is silent during function execution
- Function executes immediately upon entering node

**Best Practices**:
- Use `true` for longer operations (>1 second) to prevent awkward silence
- Use `false` for instant operations or when silence is acceptable
- Static sentences faster than prompts (no LLM generation)

**Example Prompts**:
```
"Tell the user you're checking their availability"
"Let them know you're looking up their account"
"Confirm you're processing their request"
```

---

### 1.3 Other Node Settings

**LLM Selection**
- Override global LLM for this specific node
- Used for:
  - Function argument generation
  - Speak during execution message generation

**Block Interruptions**
- When enabled, user cannot interrupt agent while speaking
- Use cautiously (can frustrate users)

**Global Node**
- Makes node accessible from anywhere in flow
- Requires explicit transition condition
- See [Global Node Patterns](#5-global-node-patterns)

**Fine-tuning Examples**
- Can fine-tune transition behavior
- Add examples of when to transition
- See: Retell.ai Finetune Examples documentation

---

## 2. Function Definition & Configuration

### 2.1 Function Setup Process

**Important**: Define functions FIRST, then select them in nodes. This allows node deletion without losing function definitions.

**Steps**:
1. Add function in Functions section
2. Select pre-configured function in function node
3. Configure node-specific settings

### 2.2 Custom Function Configuration

#### Function Details
```
Name: use_snake_case (must be unique)
Description: Clear description of what function does
HTTP Method: POST, GET, PUT, PATCH, DELETE
Endpoint URL: Your API endpoint (must be valid URL)
```

#### Headers (Optional)
- Define custom headers
- Can include dynamic variables
- Example: `Authorization: Bearer {{api_token}}`

#### Query Parameters (Optional)
- Append to request URL
- Can include dynamic variables
- Example: `?user_id={{customer_id}}&type=booking`

#### Parameters (POST/PUT/PATCH only)

Define using JSON Schema format:

**CRITICAL**: Always include `"type": "object"` at top level (most common mistake)

**Example - Appointment Booking**:
```json
{
  "type": "object",
  "properties": {
    "customer_name": {
      "type": "string",
      "description": "Full name of the customer"
    },
    "phone": {
      "type": "string",
      "description": "Customer's phone number"
    },
    "requested_time": {
      "type": "string",
      "description": "Requested appointment time in ISO 8601 format"
    },
    "service_type": {
      "type": "string",
      "description": "Type of service requested",
      "enum": ["consultation", "repair", "installation"]
    }
  },
  "required": ["customer_name", "requested_time"]
}
```

**Example - CRM Lookup**:
```json
{
  "type": "object",
  "properties": {
    "customer_id": {
      "type": "string",
      "description": "Unique customer identifier"
    },
    "lookup_type": {
      "type": "string",
      "description": "Type of information to retrieve",
      "enum": ["profile", "orders", "support_tickets"]
    }
  },
  "required": ["customer_id"]
}
```

**Parameter Best Practices**:
- Keep descriptions clear and specific
- Use enums for limited option sets
- Mark truly required fields in "required" array
- Avoid over-specifying (let LLM extract naturally)
- Reference OpenAI Function Calling Guide for complex schemas

---

### 2.3 Response Variables (Dynamic Variables)

Extract values from function response and save for later use.

**Example Response**:
```json
{
  "properties": {
    "user": {
      "name": "John Doe",
      "age": 26
    },
    "appointment": {
      "id": "appt_123",
      "time": "2025-10-24T14:00:00Z"
    }
  }
}
```

**Extraction Configuration**:
- Extract `user.name` → save as `{{customer_name}}`
- Extract `appointment.id` → save as `{{appointment_id}}`
- Extract `appointment.time` → save as `{{appointment_time}}`

**Usage in Later Nodes**:
```
"Great! I've booked your appointment for {{appointment_time}}. Your confirmation number is {{appointment_id}}."
```

---

### 2.4 Request & Response Specification

**Request Headers**:
```
X-Retell-Signature: Encrypted request body (for verification)
Content-Type: application/json
```

**Request Body** (POST/PUT/PATCH):
```json
{
  "name": "function_name",
  "call": {
    // Call object with context and transcript
    // See Get Call API reference
  },
  "args": {
    // Function arguments as JSON object
  }
}
```

**Response Requirements**:
- Status code: 200-299 (success)
- Format: string, buffer, JSON, blob (all converted to string)
- Max length: 15,000 characters (prevents context overload)
- Timeout: 2 minutes (or custom timeout)
- Retries: Up to 2 times on failure

---

### 2.5 Security

**Verify Request from Retell**:

```javascript
import { Retell } from "retell-sdk";

this.app.post("/check-weather", async (req, res) => {
  if (!Retell.verify(
    JSON.stringify(req.body),
    process.env.RETELL_API_KEY,
    req.headers["x-retell-signature"]
  )) {
    console.error("Invalid signature");
    return res.status(401).json({ error: "Unauthorized" });
  }

  // Process request
  const content = req.body;
  // ...
});
```

**IP Allowlisting**:
```
Retell IP: 100.20.5.228
```

---

## 3. Edge Conditions & Transition Logic

### 3.1 Transition Condition Types

**Two Types**:
1. **Equation** (evaluated first, top to bottom)
2. **Prompt** (evaluated after equations)

### 3.2 Equation Conditions

Hardcoded mathematical/logical evaluations using dynamic variables.

**Operators**:
```
Comparison: >, <, >=, <=, == (string), != (string)
Logical: AND, OR
String: CONTAINS, NOT CONTAINS
Existence: exists, does not exist
```

**Examples**:
```
{{user_age}} > 18
{{current_time}} > 9 AND {{current_time}} < 18
{{user_location}} == "New York"
{{user_location}} != "California"
"New York, Los Angeles" CONTAINS {{user_location}}
"New York, Los Angeles" NOT CONTAINS {{user_location}}
{{user_age}} < 18 OR {{user_location}} == "New York"
{{user_email}} exists
{{user_phone}} does not exist
```

**Critical Notes**:
- `==`, `!=`, `CONTAINS`, `NOT CONTAINS` are STRING comparisons
- Other operators (>, <, etc.) require NUMERICAL input
- Non-numeric input with numerical operators always evaluates to FALSE
- Equations evaluated top-to-bottom (order matters)
- First TRUE condition wins

**Use Cases**:
- Branch based on pre-call data (location, age, account type)
- Check if required information was collected
- Route based on business hours
- Handle different customer segments

---

### 3.3 Prompt Conditions

LLM-evaluated conditions based on conversation context.

**Examples**:
```
"User wants to book an appointment"
"User mentions cancelling their meeting"
"User said they are over 18"
"User indicated they live in New York"
"User wants to speak to a human agent"
"User asked about pricing"
"CRM lookup returned successful result"
"Customer provided all required information"
```

**Best Practices**:

1. **Be Clear & Specific**:
   - ✅ "User wants to book an appointment"
   - ❌ "Booking scenario"

2. **Don't Over-Reference Node Instructions**:
   - ✅ "User declines the invitation"
   - ❌ "User responds as per the instruction above"

3. **Cover All Scenarios**:
   - Add edge for each possible user response
   - Use global nodes for common cases (objections, questions)
   - Prevent agent from getting stuck

4. **Function Node Conditions Can Reference Results**:
   - ✅ "CRM lookup returned successful result"
   - ✅ "Appointment was successfully booked"
   - ✅ "No available slots were found"

5. **Natural Language Detection**:
   - "User indicates they want to book a meeting"
   - "User responds to question about their age"
   - "User provides email address"

---

### 3.4 Transition Timing by Node Type

#### Conversation Nodes
- Usually after user speaks
- Can transition during agent speech if interrupted

#### Function Nodes
- See [1.2 Node Settings Deep Dive](#12-node-settings-deep-dive)
- Timing depends on `wait_for_result` + `speak_during_execution`

#### Press Digit Nodes
- After digit sequence sent

#### Call Transfer Nodes
- Can define fallback destination if transfer fails

---

### 3.5 Edge Configuration Location

**Where to Define Edges**:
- Conversation Nodes → edges out
- Function Nodes → edges out
- Press Digit Nodes → edges out
- Call Transfer Nodes → fallback edge
- Skip Response feature → destination node
- Global Nodes → edge IN (transition condition to enter)

---

## 4. How to Tell User the Result

**Critical Pattern**: Function nodes are NOT for conversation.

**Recommended Architecture**:
```
[Conversation Node: Collect Info]
         ↓
[Function Node: Call API]
         ↓ (wait_for_result: true)
    Conditional Edges:
         ↓ "Function returned success"
[Conversation Node: Success Message]
         ↓ "Function returned error"
[Conversation Node: Error Message]
```

**Example Flow**:
```
Node: collect_appointment_info
  ↓ "User provided date and time"

Node: check_availability (Function)
  Settings:
    - wait_for_result: true
    - speak_during_execution: "Let me check our availability"
  Edges:
    ↓ "Available slots found"

Node: confirm_booking (Conversation)
  Instruction: "Tell user about available slots and ask to confirm"
  Edges:
    ↓ "User confirms"

Node: book_appointment (Function)
  Settings:
    - wait_for_result: true
    - speak_during_execution: "Booking your appointment now"
  Edges:
    ↓ "Booking successful"

Node: booking_success (Conversation)
  Instruction: "Confirm appointment details using {{appointment_time}} and {{appointment_id}}"
```

---

## 5. Global Node Patterns

**What Are Global Nodes?**
- Accessible from anywhere in the conversation flow
- Require explicit transition condition to enter
- Used for common scenarios that can happen anytime

**Common Use Cases**:
```
- Objection handling
- Request to speak to human
- Pricing questions
- Hours of operation questions
- General FAQ
- Call termination requests
```

**Configuration**:
1. Enable "Global Node" setting
2. Define clear transition condition
3. Usually transition back to previous node or logical next step

**Example**:
```
Node: speak_to_human (Global)
  Transition IN: "User wants to speak to a human agent"
  Type: Call Transfer Node
  Destination: Human agent queue
```

---

## 6. Common Mistakes & Pitfalls

### 6.1 Function Configuration Errors

❌ **Missing `"type": "object"` in JSON schema**
```json
// WRONG
{
  "properties": {
    "name": { "type": "string" }
  }
}

// CORRECT
{
  "type": "object",
  "properties": {
    "name": { "type": "string" }
  }
}
```

❌ **Invalid endpoint URL**
- Must be fully qualified URL
- Must be accessible from public internet (or use IP allowlisting)

❌ **Not verifying X-Retell-Signature**
- Security risk: anyone can call your endpoint
- Always verify signature in production

---

### 6.2 Transition Condition Errors

❌ **Not covering all scenarios**
```
// WRONG - What if user says something else?
Edges:
  → "User wants to book"

// CORRECT - Handle all cases
Edges:
  → "User wants to book"
  → "User wants to cancel"
  → "User has a question"
Global Node: handles_objections
```

❌ **Vague transition conditions**
```
// WRONG
"User responds"
"Booking scenario"

// CORRECT
"User provides appointment date and time"
"User wants to book an appointment"
```

❌ **Using non-numeric values with numeric operators**
```
// WRONG - if user_age is "twenty", always false
{{user_age}} > 18

// CORRECT - ensure dynamic variable is numeric
// Or use prompt condition: "User said they are over 18"
```

---

### 6.3 Timing & Flow Errors

❌ **Using `wait_for_result: false` when you need the result**
```
// WRONG
Node: check_availability
  wait_for_result: false
  ↓
Node: tell_user_slots
  Instruction: "Tell user about {{available_slots}}"
  // {{available_slots}} might not be available yet!

// CORRECT
Node: check_availability
  wait_for_result: true  // ← Wait for result
  ↓
Node: tell_user_slots
  Instruction: "Tell user about {{available_slots}}"
```

❌ **Trying to have conversation in function node**
```
// WRONG
Node: book_appointment (Function)
  Instruction: "Ask user to confirm appointment details"
  // Function nodes NOT for conversation!

// CORRECT
Node: confirm_details (Conversation)
  Instruction: "Ask user to confirm appointment details"
  ↓
Node: book_appointment (Function)
  wait_for_result: true
```

❌ **Not using speak_during_execution for slow APIs**
```
// WRONG - awkward 3 second silence
Node: crm_lookup (Function)
  speak_during_execution: false
  // API takes 3 seconds...

// CORRECT
Node: crm_lookup (Function)
  speak_during_execution: true
  Prompt: "Let me pull up your account information"
```

---

### 6.4 Parameter Extraction Errors

❌ **Over-specifying parameters**
```json
// WRONG - too rigid
{
  "type": "object",
  "properties": {
    "date": {
      "type": "string",
      "pattern": "^\\d{4}-\\d{2}-\\d{2}$"
    }
  }
}

// CORRECT - let LLM handle formats
{
  "type": "object",
  "properties": {
    "date": {
      "type": "string",
      "description": "Appointment date in YYYY-MM-DD format"
    }
  }
}
```

❌ **Requiring too many parameters**
- LLM might call function before collecting all info
- Make optional what can be optional
- Use separate nodes for collection if needed

---

## 7. Optimization Strategies

### 7.1 Performance

**Reduce Latency**:
- Use static sentences instead of prompts for speak_during_execution (faster)
- Set appropriate timeouts for function calls
- Cache common responses on your API side
- Use GET requests when possible (faster than POST)

**Improve Reliability**:
- Implement retry logic on your endpoint
- Return clear error messages
- Keep function responses under 15,000 characters
- Use equation conditions for deterministic routing (faster than prompts)

---

### 7.2 User Experience

**Natural Conversation**:
- Use speak_during_execution for operations >1 second
- Provide context-aware messages
- Acknowledge user input before calling function

**Error Handling**:
```
Node: book_appointment (Function)
  Edges:
    → "Booking successful"
    → Node: success_message

    → "Booking failed"
    → Node: error_message

    → "No availability"
    → Node: offer_alternatives
```

**Graceful Degradation**:
- Always have fallback paths
- Use global nodes for common issues
- Provide human transfer option

---

### 7.3 Maintainability

**Organization**:
- Name nodes clearly: `collect_user_info`, `check_availability`, `confirm_booking`
- Group related nodes visually
- Use "Organize" button to auto-arrange

**Documentation**:
- Document complex transition logic
- Add comments to function descriptions
- Track node transitions in call transcripts (visible in history)

**Testing**:
- Test all edge paths
- Verify dynamic variables are set correctly
- Check function responses are under 15,000 chars
- Use LLM Playground for testing conditions
- Use LLM Simulation Testing for flow validation

---

## 8. Pre-built Functions

Retell provides ready-to-use functions:

| Function | Purpose | Common Use Cases |
|----------|---------|------------------|
| End Call | Terminate conversation | Task complete, user requests end |
| Transfer Call | Route to human agents | Escalation, department routing |
| Press Digits | Send DTMF tones | IVR navigation, code entry |
| Check Availability | Query time slots | Appointment scheduling |
| Book Calendar | Create appointments | Calendar integration |
| Send SMS | Text messages | Confirmations, follow-ups |

---

## 9. Architecture Decision Tree

**Choose Single/Multi Prompt vs Conversation Flow**:

```
Single Prompt:
  - Simple use case
  - <500 words prompt
  - <3 functions

Multi-Prompt:
  - Moderate complexity
  - Multiple states needed
  - Better function control per state

Conversation Flow:
  - Complex scenarios
  - Need precise control
  - Multiple branching paths
  - Predictable behavior required
```

**When to Use Conversation Flow**:
1. Structured conversations with clear steps
2. Complex branching logic
3. Need for fine-tuned transitions
4. Multiple function calls in sequence
5. State-dependent behavior

---

## 10. Real-World Example: Appointment Booking Flow

```
START → greeting
  ↓ "User wants to book appointment"

collect_service_type
  Instruction: "Ask what service they need"
  Edges:
    ↓ "User specified service type"

collect_preferred_time
  Instruction: "Ask when they'd like to come in"
  Edges:
    ↓ "User provided date and time preference"

check_availability (Function)
  Settings:
    - wait_for_result: true
    - speak_during_execution: "Let me check our availability"
  Function: check_calendar_availability
  Parameters:
    - service_type: {{service_type}}
    - requested_time: {{requested_time}}
  Response: {{available_slots}}
  Edges:
    ↓ {{available_slots}} exists

present_options (Conversation)
  Instruction: "Tell user about {{available_slots}} and ask which works best"
  Edges:
    ↓ "User selected a time slot"

collect_contact_info (Conversation)
  Instruction: "Ask for name and phone if not already collected"
  Edges:
    ↓ {{customer_name}} exists AND {{customer_phone}} exists

confirm_details (Conversation)
  Instruction: "Repeat appointment details and ask for confirmation"
  Edges:
    ↓ "User confirms"

book_appointment (Function)
  Settings:
    - wait_for_result: true
    - speak_during_execution: "Booking your appointment now"
  Function: book_calendar
  Parameters:
    - customer_name: {{customer_name}}
    - customer_phone: {{customer_phone}}
    - appointment_time: {{selected_slot}}
    - service_type: {{service_type}}
  Response: {{appointment_id}}, {{confirmation_code}}
  Edges:
    ↓ "Booking successful"

booking_success (Conversation)
  Instruction: "Confirm booking with {{appointment_time}} and {{confirmation_code}}"
  Edges:
    ↓ "Done speaking"

END
```

---

## 11. Debugging Guide

**When Transitions Fail**:
1. Check transition condition clarity
2. Add fine-tune examples
3. Review call transcript to see current node
4. Verify dynamic variables are set
5. Check equation condition order (top-to-bottom)

**When Functions Fail**:
1. Verify X-Retell-Signature on your endpoint
2. Check endpoint URL is accessible
3. Verify JSON schema has `"type": "object"`
4. Check function response <15,000 chars
5. Review timeout settings
6. Check retry logic

**When Agent Gets Stuck**:
1. Review transition conditions (cover all cases?)
2. Add global nodes for common scenarios
3. Simplify complex conditions
4. Use equation conditions for deterministic routing

---

## 12. Resources

**Official Documentation**:
- Function Node Overview: https://docs.retellai.com/build/conversation-flow/function-node
- Custom Function: https://docs.retellai.com/build/conversation-flow/custom-function
- Transition Conditions: https://docs.retellai.com/build/conversation-flow/transition-condition
- Function Calling Overview: https://docs.retellai.com/build/single-multi-prompt/function-calling

**External References**:
- OpenAI Function Calling Guide: https://platform.openai.com/docs/guides/function-calling
- Function Calling Tutorial: https://semaphoreci.com/blog/function-calling

---

## 13. Quick Reference Card

**Function Node Settings Matrix**:

| wait_for_result | speak_during_execution | Transition Timing | Use Case |
|-----------------|------------------------|-------------------|----------|
| true | true | Result ready + speaking done | Most appointment bookings |
| true | false | Result ready | Silent operations needing result |
| false | true | Speaking done | Fire-and-forget with user feedback |
| false | false | Immediate | Background logging, analytics |

**Transition Condition Checklist**:
- [ ] All user response scenarios covered
- [ ] Global nodes handle common cases
- [ ] Equation conditions before prompt conditions
- [ ] Conditions are clear and specific
- [ ] Function results referenced correctly
- [ ] Dynamic variables exist checks where needed

**Function Definition Checklist**:
- [ ] `"type": "object"` in JSON schema
- [ ] Clear, unique function name (snake_case)
- [ ] Descriptive parameter descriptions
- [ ] Only required params in "required" array
- [ ] X-Retell-Signature verification implemented
- [ ] Response under 15,000 characters
- [ ] Appropriate timeout set
- [ ] Error handling implemented

---

**Last Updated**: 2025-10-23
**Version**: 1.0
**Author**: Research via Retell.ai Official Documentation
