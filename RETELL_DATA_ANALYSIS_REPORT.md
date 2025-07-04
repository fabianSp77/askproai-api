# Retell.ai Data Analysis Report

## Executive Summary

Retell.ai sends extensive data in their webhooks, but AskProAI is currently not utilizing most of the rich analytical and operational data available. This report details what data is available, what's being saved, and what's missing from the UI.

## Data Currently Being Received from Retell.ai

### 1. Call Analysis Data (`call_analysis`)
- **in_voicemail**: Boolean indicating if call went to voicemail
- **call_summary**: AI-generated summary of the call
- **user_sentiment**: Overall sentiment (Positive/Negative/Neutral)
- **call_successful**: Boolean indicating if call achieved its objective
- **custom_analysis_data**: Structured data including:
  - `first_visit`: Boolean
  - `no_show_count`: Number of previous no-shows
  - `appointment_date_time`: Extracted appointment details
  - `urgency_level`: Call urgency classification
  - `patient_full_name`: Extracted customer name
  - `reason_for_visit`: Why customer is calling
  - `insurance_type`: Insurance information
  - `health_insurance_company`: Specific insurance provider
  - `appointment_made`: Boolean if appointment was successfully booked
  - `reschedule_count`: Number of reschedules

### 2. Performance Metrics

#### Latency Metrics (`latency`)
```json
{
  "llm": {
    "p99": 1094.92,
    "min": 2,
    "max": 1099,
    "p90": 1058.2,
    "p50": 845
  },
  "e2e": {
    "p99": 3149.89,
    "min": 696,
    "max": 3191,
    "p50": 1476.00
  },
  "tts": {
    "p99": 434,
    "min": 256,
    "max": 444,
    "p50": 294
  }
}
```

#### Cost Breakdown (`call_cost`)
```json
{
  "total_duration_unit_price": 0.1393299,
  "product_costs": [
    {"product": "elevenlabs_tts", "cost": 11.3166667},
    {"product": "gemini_2_0_flash", "cost": 0.97},
    {"product": "llm_token_surcharge", "cost": 0.42},
    {"product": "background_voice_cancellation", "cost": 0.8083333},
    {"product": "post_call_analysis_gpt_4o", "cost": 1.7}
  ],
  "combined_cost": 15.215,
  "total_duration_seconds": 97
}
```

#### LLM Token Usage (`llm_token_usage`)
```json
{
  "values": [4762, 4814, 4824, 4894, 4942, 5111, 5244, 5267],
  "average": 4982.25,
  "num_requests": 8
}
```

### 3. Call Metadata
- **agent_name**: Full name of the AI agent including version
- **agent_version**: Version number of the agent
- **disconnection_reason**: How call ended (user_hangup, agent_hangup, etc.)
- **recording_url**: Audio recording URL
- **public_log_url**: Detailed call log URL
- **transcript_object**: Structured transcript with timestamps
- **transcript_with_tool_calls**: Transcript including function calls
- **custom_sip_headers**: Telephony metadata
- **retell_llm_dynamic_variables**: Dynamic data from call

### 4. Transcript Object Structure
```json
[
  {
    "role": "agent",
    "content": "Welcome message...",
    "words": [
      {
        "word": "Welcome",
        "start": 0.569814697265625,
        "end": 1.1605556640625
      }
    ]
  }
]
```

## What's Currently Being Saved

The `ProcessRetellCallEndedJob` saves the following to the database:
- ✅ Basic call info (phone numbers, duration, timestamps)
- ✅ Transcript (plain text)
- ✅ Recording URL
- ✅ Cost information
- ✅ raw_data (complete webhook payload)
- ✅ webhook_data (complete webhook event)
- ❌ call_analysis (NOT being saved separately)
- ❌ Latency metrics (NOT being extracted)
- ❌ Cost breakdown (NOT being extracted)
- ❌ LLM token usage (NOT being extracted)
- ❌ Structured transcript_object (saved but not utilized)

## What's Being Displayed in Filament

Current CallResource displays:
- ✅ Basic info (date, caller, duration, status)
- ✅ Customer link
- ✅ Simple sentiment (but from analysis['sentiment'], not user_sentiment)
- ✅ Recording indicator
- ✅ Total cost
- ✅ Appointment link if booked
- ❌ Call summary
- ❌ User sentiment from Retell
- ❌ Urgency level
- ❌ Conversation metrics
- ❌ Performance metrics
- ❌ Cost breakdown
- ❌ AI insights
- ❌ Custom analysis data fields

## Missing Data Opportunities

### 1. Business Intelligence
- **Call Success Rate**: `call_analysis.call_successful` field not displayed
- **No-Show Tracking**: `custom_analysis_data.no_show_count` available but not shown
- **Reschedule Patterns**: `custom_analysis_data.reschedule_count` not utilized
- **First Visit Indicator**: `custom_analysis_data.first_visit` not displayed

### 2. Operational Insights
- **Agent Performance**: Latency metrics show AI response times
- **Cost Analysis**: Detailed breakdown by service (TTS, LLM, analysis)
- **Token Efficiency**: LLM usage patterns for optimization
- **Call Quality**: End-to-end latency affects user experience

### 3. Customer Insights
- **Health Insurance**: Insurance company and type captured but not displayed
- **Visit Reasons**: `reason_for_visit` field available
- **Urgency Classification**: Could prioritize callbacks
- **Sentiment Analysis**: More accurate from Retell's analysis

### 4. Technical Metrics
- **Word-level Timestamps**: Available in transcript_object for playback sync
- **Tool Call Analysis**: Shows which AI functions were used
- **Disconnection Reasons**: Understand why calls end
- **SIP Headers**: Telephony provider metadata

## Recommendations

### Immediate Actions (High Value, Low Effort)
1. **Extract and display call_analysis data**
   - Add columns for call_successful, urgency_level
   - Show no_show_count and reschedule_count
   - Display reason_for_visit

2. **Add performance metrics widget**
   - Show average latency trends
   - Display cost breakdown by component
   - Track token usage efficiency

3. **Enhance sentiment display**
   - Use Retell's user_sentiment instead of basic analysis
   - Add call_summary to detail view
   - Show urgency badges

### Medium-term Improvements
1. **Create analytics dashboard**
   - Call success rates over time
   - Cost analysis by agent/branch
   - Customer behavior patterns

2. **Implement smart filtering**
   - Filter by urgency level
   - Find high no-show customers
   - Identify expensive calls

3. **Add playback features**
   - Use transcript_object for synchronized playback
   - Highlight important moments
   - Show tool calls in timeline

### Long-term Enhancements
1. **Predictive analytics**
   - Predict no-shows based on patterns
   - Forecast call volumes
   - Optimize agent configurations

2. **Integration improvements**
   - Auto-tag customers with insurance info
   - Create follow-up tasks for urgent calls
   - Sync visit reasons with services

## Technical Implementation Notes

1. **Database Changes Needed**:
   - Add indexes on JSON fields for performance
   - Consider extracting frequently queried fields to columns
   - Add computed columns for metrics

2. **Model Updates**:
   - Add accessors for nested JSON data
   - Implement casts for complex fields
   - Add scopes for analytics queries

3. **UI Enhancements**:
   - Create custom Filament components for metrics
   - Add interactive charts for trends
   - Implement real-time updates for live calls

## Conclusion

Retell.ai provides incredibly rich data that could transform AskProAI from a simple call logger to a comprehensive customer intelligence platform. The raw_data is being saved, but the valuable insights within are not being extracted or displayed. Implementing even the basic recommendations would significantly enhance the platform's value proposition.