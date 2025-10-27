<?php

namespace App\Services\Agents\ConversationFlow;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Node Graph Designer for Conversation Flow
 *
 * Designs the complete node-based state machine for appointment booking
 * optimized for all 4 call scenarios.
 */
class NodeGraphDesigner
{
    private array $nodes = [];
    private array $transitions = [];
    private array $variables = [];

    /**
     * Design complete conversation flow graph
     */
    public function design(array $baselineAnalysis): array
    {
        Log::info('Designing Conversation Flow node graph');

        // Define all nodes
        $this->defineNodes();

        // Define transitions between nodes
        $this->defineTransitions();

        // Define variable flow
        $this->defineVariables();

        $graph = [
            'timestamp' => now()->toIso8601String(),
            'version' => '1.0',
            'designed_for' => 'AskPro AI Appointment Booking',
            'optimized_for_scenarios' => ['scenario_1', 'scenario_2', 'scenario_3', 'scenario_4'],
            'total_nodes' => count($this->nodes),
            'total_transitions' => count($this->transitions),
            'nodes' => $this->nodes,
            'transitions' => $this->transitions,
            'variables' => $this->variables,
            'mermaid_diagram' => $this->generateMermaidDiagram()
        ];

        $this->saveGraph($graph);

        return $graph;
    }

    /**
     * Define all nodes in the conversation flow
     */
    private function defineNodes(): void
    {
        $this->nodes = [
            'node_01_initialization' => [
                'id' => 'node_01_initialization',
                'type' => 'function',
                'name' => 'INITIALIZATION',
                'description' => 'Entry point: Greeting + parallel function calls',
                'prompt' => 'Say immediately: "Willkommen bei Ask Pro AI. Guten Tag!" Then call current_time_berlin() and check_customer() in PARALLEL. WAIT for both responses!',
                'functions' => ['current_time_berlin', 'check_customer'],
                'execution_mode' => 'parallel',
                'expected_duration_seconds' => 2,
                'critical' => true
            ],
            'node_02_customer_routing' => [
                'id' => 'node_02_customer_routing',
                'type' => 'logic',
                'name' => 'CUSTOMER_ROUTING',
                'description' => 'Route based on customer status',
                'logic' => 'if (customer_status == "found") goto node_03a; else if (customer_status == "new_customer") goto node_03b; else goto node_03c',
                'expected_duration_seconds' => 0.1,
                'critical' => true
            ],
            'node_03a_known_customer' => [
                'id' => 'node_03a_known_customer',
                'type' => 'interaction',
                'name' => 'KNOWN_CUSTOMER',
                'description' => 'Personalized greeting for known customers',
                'prompt' => 'Use CUSTOMER_NAME from check_customer. Say: "Guten Tag {{CUSTOMER_NAME}}! Möchten Sie einen Termin buchen?" V85 rule: Use first name only, no Herr/Frau without gender.',
                'handles_scenario' => 'scenario_1',
                'expected_duration_seconds' => 2,
                'critical' => false
            ],
            'node_03b_new_customer' => [
                'id' => 'node_03b_new_customer',
                'type' => 'interaction',
                'name' => 'NEW_CUSTOMER',
                'description' => 'Generic greeting for new customers',
                'prompt' => 'Say: "Guten Tag! Möchten Sie einen Termin buchen oder haben Sie eine Frage?" Name will be collected during booking flow if needed.',
                'handles_scenario' => 'scenario_2',
                'expected_duration_seconds' => 2,
                'critical' => false
            ],
            'node_03c_anonymous_customer' => [
                'id' => 'node_03c_anonymous_customer',
                'type' => 'interaction',
                'name' => 'ANONYMOUS_CUSTOMER',
                'description' => 'Greeting for anonymous callers - MUST be fast!',
                'prompt' => 'Say IMMEDIATELY: "Guten Tag! Möchten Sie einen Termin buchen?" CRITICAL: No delay! This prevents Szenario 4 silence issue.',
                'handles_scenario' => ['scenario_3', 'scenario_4'],
                'expected_duration_seconds' => 1,
                'critical' => true,
                'optimization' => 'Speed is critical here to prevent 17s silence gap'
            ],
            'node_04_intent_capture' => [
                'id' => 'node_04_intent_capture',
                'type' => 'logic',
                'name' => 'INTENT_CAPTURE',
                'description' => 'Classify user intent',
                'logic' => 'classify_intent(user_utterance) → book|query|reschedule|cancel|info',
                'intents' => [
                    'book_appointment' => ['termin buchen', 'appointment', 'buchung', 'wann haben sie frei'],
                    'query_appointment' => ['wann ist mein termin', 'hab ich', 'mein termin'],
                    'reschedule' => ['verschieben', 'umbuchen', 'ändern'],
                    'cancel' => ['stornieren', 'absagen', 'löschen'],
                    'info' => ['frage', 'information']
                ],
                'expected_duration_seconds' => 0.5,
                'critical' => true
            ],
            'node_05_name_collection' => [
                'id' => 'node_05_name_collection',
                'type' => 'interaction',
                'name' => 'NAME_COLLECTION',
                'description' => 'Collect name for anonymous callers',
                'prompt' => 'Ask: "Für die Buchung benötige ich Ihren Namen. Wie heißen Sie bitte?" VALIDATION: Name must have 2+ characters. Retry max 3 times.',
                'handles_scenario' => ['scenario_3', 'scenario_4'],
                'max_attempts' => 3,
                'expected_duration_seconds' => 8,
                'critical' => true,
                'optimization' => 'Fast name collection prevents Szenario 3 long duration'
            ],
            'node_06_service_selection' => [
                'id' => 'node_06_service_selection',
                'type' => 'interaction',
                'name' => 'SERVICE_SELECTION',
                'description' => 'Determine service type',
                'prompt' => 'If service mentioned, extract it. Otherwise use "Beratung" as default. Optional: Ask "Für welche Dienstleistung?"',
                'default_service' => 'Beratung',
                'expected_duration_seconds' => 2,
                'critical' => false
            ],
            'node_07_datetime_collection' => [
                'id' => 'node_07_datetime_collection',
                'type' => 'interaction',
                'name' => 'DATETIME_COLLECTION',
                'description' => 'Collect date and time with validation',
                'prompt' => 'CRITICAL: NEVER invent date/time! Ask: "Für welchen Tag und welche Uhrzeit?" Parse relative dates using current_time_berlin data. ALWAYS confirm: "Das wäre {{weekday}}, der {{date}} um {{time}} Uhr. Richtig?"',
                'validation_rules' => [
                    'no_past_times' => true,
                    'relative_date_handling' => true,
                    'confirm_parsed_date' => true,
                    'ambiguous_handling' => '"15.1" = 15th of CURRENT month, NOT January'
                ],
                'expected_duration_seconds' => 10,
                'critical' => true,
                'optimization' => 'Prevents hallucination issues from Szenario 1 & 3'
            ],
            'node_08_availability_check' => [
                'id' => 'node_08_availability_check',
                'type' => 'function',
                'name' => 'AVAILABILITY_CHECK',
                'description' => 'Check slot availability (STEP 1: bestaetigung=false)',
                'function_name' => 'collect_appointment_data',
                'function_params' => [
                    'call_id' => '{{call_id}}',
                    'name' => '{{CUSTOMER_NAME}}',
                    'datum' => '{{REQUESTED_DATE}}',
                    'uhrzeit' => '{{REQUESTED_TIME}}',
                    'dienstleistung' => '{{SERVICE_NAME}}',
                    'bestaetigung' => false
                ],
                'speak_during_execution' => true,
                'execution_message' => 'Einen Moment, ich prüfe die Verfügbarkeit',
                'timeout_ms' => 3000,
                'expected_duration_seconds' => 2,
                'critical' => true
            ],
            'node_09a_booking_confirmation' => [
                'id' => 'node_09a_booking_confirmation',
                'type' => 'interaction',
                'name' => 'BOOKING_CONFIRMATION',
                'description' => 'Confirm booking with user (V85 race condition prevention)',
                'prompt' => 'Say: "Der Termin am {{weekday}}, den {{date}} um {{time}} Uhr ist noch frei. Darf ich den Termin auf Ihren Namen, {{CUSTOMER_NAME}}, buchen?" WAIT for explicit "Ja". If silence >3s, repeat. Never book without confirmation!',
                'confirmation_required' => true,
                'max_silence_seconds' => 3,
                'expected_duration_seconds' => 4,
                'critical' => true
            ],
            'node_09b_alternative_offering' => [
                'id' => 'node_09b_alternative_offering',
                'type' => 'interaction',
                'name' => 'ALTERNATIVE_OFFERING',
                'description' => 'Offer alternative slots when unavailable',
                'prompt' => 'If alternatives exist: "Der Termin um {{original_time}} ist leider belegt. Ich kann Ihnen anbieten: {{alt1}} oder {{alt2}}. Was passt besser?" If no alternatives: "Leider nicht verfügbar. Möchten Sie einen anderen Tag?"',
                'max_alternatives' => 2,
                'expected_duration_seconds' => 8,
                'critical' => false
            ],
            'node_09c_final_booking' => [
                'id' => 'node_09c_final_booking',
                'type' => 'function',
                'name' => 'FINAL_BOOKING',
                'description' => 'Execute booking (STEP 2: bestaetigung=true) with V85 double-check',
                'function_name' => 'collect_appointment_data',
                'function_params' => [
                    'call_id' => '{{call_id}}',
                    'name' => '{{CUSTOMER_NAME}}',
                    'datum' => '{{REQUESTED_DATE}}',
                    'uhrzeit' => '{{REQUESTED_TIME}}',
                    'dienstleistung' => '{{SERVICE_NAME}}',
                    'bestaetigung' => true
                ],
                'speak_during_execution' => true,
                'execution_message' => 'Ich buche jetzt den Termin für Sie',
                'timeout_ms' => 5000,
                'v85_double_check' => true,
                'expected_duration_seconds' => 3,
                'critical' => true
            ],
            'node_14_success_message' => [
                'id' => 'node_14_success_message',
                'type' => 'interaction',
                'name' => 'SUCCESS_MESSAGE',
                'description' => 'Confirm successful booking',
                'prompt' => 'Say: "Perfekt! Ihr Termin am {{weekday}}, den {{date}} um {{time}} Uhr ist gebucht. Sie erhalten eine Bestätigung per E-Mail. Auf Wiederhören!"',
                'ends_call' => true,
                'expected_duration_seconds' => 4,
                'critical' => false
            ],
            'node_15_race_condition_handler' => [
                'id' => 'node_15_race_condition_handler',
                'type' => 'logic_interaction',
                'name' => 'RACE_CONDITION_HANDLER',
                'description' => 'Handle slot taken between check and booking (V85 improvement)',
                'prompt' => 'Say: "Entschuldigung, der Slot wurde gerade vergeben. Ich kann Ihnen direkt anbieten: {{alt1}} oder {{alt2}}. Passt das?" If no alternatives: ask for different date.',
                'max_race_condition_count' => 3,
                'expected_duration_seconds' => 5,
                'critical' => true,
                'optimization' => 'Prevents booking failure, improves Szenario 1 & 3 success'
            ],
            'node_98_polite_end' => [
                'id' => 'node_98_polite_end',
                'type' => 'interaction',
                'name' => 'POLITE_END',
                'description' => 'Graceful call termination',
                'prompt' => 'Say: "Kein Problem! Rufen Sie gerne wieder an wenn Sie einen Termin möchten. Auf Wiederhören!"',
                'ends_call' => true,
                'expected_duration_seconds' => 3,
                'critical' => false
            ],
            'node_99_error_handler' => [
                'id' => 'node_99_error_handler',
                'type' => 'interaction',
                'name' => 'ERROR_HANDLER',
                'description' => 'Unrecoverable error handler',
                'prompt' => 'NEVER say "technisches Problem"! Say: "Entschuldigung, ich konnte Ihre Anfrage nicht verarbeiten. Bitte versuchen Sie es später erneut. Auf Wiederhören!"',
                'ends_call' => true,
                'logs_error' => true,
                'expected_duration_seconds' => 3,
                'critical' => false
            ]
        ];
    }

    /**
     * Define transitions between nodes
     */
    private function defineTransitions(): void
    {
        $this->transitions = [
            // From INITIALIZATION
            ['from' => 'node_01_initialization', 'to' => 'node_02_customer_routing', 'condition' => 'both_functions_complete', 'priority' => 1],
            ['from' => 'node_01_initialization', 'to' => 'node_99_error_handler', 'condition' => 'timeout > 5s', 'priority' => 999],

            // From CUSTOMER_ROUTING
            ['from' => 'node_02_customer_routing', 'to' => 'node_03a_known_customer', 'condition' => 'customer_status == "found"', 'priority' => 1],
            ['from' => 'node_02_customer_routing', 'to' => 'node_03b_new_customer', 'condition' => 'customer_status == "new_customer"', 'priority' => 2],
            ['from' => 'node_02_customer_routing', 'to' => 'node_03c_anonymous_customer', 'condition' => 'customer_status == "anonymous"', 'priority' => 3],

            // From KNOWN_CUSTOMER
            ['from' => 'node_03a_known_customer', 'to' => 'node_04_intent_capture', 'condition' => 'user_responds', 'priority' => 1],
            ['from' => 'node_03a_known_customer', 'to' => 'node_98_polite_end', 'condition' => 'silence > 5s', 'priority' => 999],

            // From NEW_CUSTOMER
            ['from' => 'node_03b_new_customer', 'to' => 'node_04_intent_capture', 'condition' => 'user_responds', 'priority' => 1],
            ['from' => 'node_03b_new_customer', 'to' => 'node_98_polite_end', 'condition' => 'silence > 5s', 'priority' => 999],

            // From ANONYMOUS_CUSTOMER
            ['from' => 'node_03c_anonymous_customer', 'to' => 'node_05_name_collection', 'condition' => 'booking_intent && no_name', 'priority' => 1],
            ['from' => 'node_03c_anonymous_customer', 'to' => 'node_04_intent_capture', 'condition' => 'other_intent', 'priority' => 2],

            // From NAME_COLLECTION
            ['from' => 'node_05_name_collection', 'to' => 'node_04_intent_capture', 'condition' => 'valid_name_collected', 'priority' => 1],
            ['from' => 'node_05_name_collection', 'to' => 'node_99_error_handler', 'condition' => 'attempts >= 3', 'priority' => 999],

            // From INTENT_CAPTURE
            ['from' => 'node_04_intent_capture', 'to' => 'node_06_service_selection', 'condition' => 'intent == "book_appointment"', 'priority' => 1],
            // Note: Other intents would go to nodes 10-13 (not shown in this simplified version)

            // From SERVICE_SELECTION
            ['from' => 'node_06_service_selection', 'to' => 'node_07_datetime_collection', 'condition' => 'service_determined', 'priority' => 1],

            // From DATETIME_COLLECTION
            ['from' => 'node_07_datetime_collection', 'to' => 'node_08_availability_check', 'condition' => 'date_and_time_collected && future_time', 'priority' => 1],
            ['from' => 'node_07_datetime_collection', 'to' => 'node_07_datetime_collection', 'condition' => 'past_time || missing_info', 'priority' => 2],

            // From AVAILABILITY_CHECK
            ['from' => 'node_08_availability_check', 'to' => 'node_09a_booking_confirmation', 'condition' => 'slot_available', 'priority' => 1],
            ['from' => 'node_08_availability_check', 'to' => 'node_09b_alternative_offering', 'condition' => 'slot_unavailable', 'priority' => 2],
            ['from' => 'node_08_availability_check', 'to' => 'node_99_error_handler', 'condition' => 'error', 'priority' => 999],

            // From BOOKING_CONFIRMATION
            ['from' => 'node_09a_booking_confirmation', 'to' => 'node_09c_final_booking', 'condition' => 'user_confirmed', 'priority' => 1],
            ['from' => 'node_09a_booking_confirmation', 'to' => 'node_09b_alternative_offering', 'condition' => 'user_declined', 'priority' => 2],
            ['from' => 'node_09a_booking_confirmation', 'to' => 'node_98_polite_end', 'condition' => 'silence > 6s', 'priority' => 999],

            // From ALTERNATIVE_OFFERING
            ['from' => 'node_09b_alternative_offering', 'to' => 'node_09a_booking_confirmation', 'condition' => 'user_selects_alternative', 'priority' => 1],
            ['from' => 'node_09b_alternative_offering', 'to' => 'node_07_datetime_collection', 'condition' => 'user_requests_new_date', 'priority' => 2],
            ['from' => 'node_09b_alternative_offering', 'to' => 'node_98_polite_end', 'condition' => 'user_declines', 'priority' => 3],

            // From FINAL_BOOKING
            ['from' => 'node_09c_final_booking', 'to' => 'node_14_success_message', 'condition' => 'booking_success', 'priority' => 1],
            ['from' => 'node_09c_final_booking', 'to' => 'node_15_race_condition_handler', 'condition' => 'race_condition', 'priority' => 2],
            ['from' => 'node_09c_final_booking', 'to' => 'node_99_error_handler', 'condition' => 'booking_error', 'priority' => 999],

            // From RACE_CONDITION_HANDLER
            ['from' => 'node_15_race_condition_handler', 'to' => 'node_09a_booking_confirmation', 'condition' => 'alternatives_available', 'priority' => 1],
            ['from' => 'node_15_race_condition_handler', 'to' => 'node_07_datetime_collection', 'condition' => 'no_alternatives', 'priority' => 2],
            ['from' => 'node_15_race_condition_handler', 'to' => 'node_99_error_handler', 'condition' => 'race_condition_count >= 3', 'priority' => 999],
        ];
    }

    /**
     * Define variable flow through nodes
     */
    private function defineVariables(): void
    {
        $this->variables = [
            // Phase 1: Initialization
            'CALL_ID' => ['source' => 'retell', 'set_at' => 'call_start', 'type' => 'string'],
            'FROM_NUMBER' => ['source' => 'check_customer', 'set_at' => 'node_01', 'type' => 'string'],
            'COMPANY_ID' => ['source' => 'lookup', 'set_at' => 'node_01', 'type' => 'integer'],
            'BRANCH_ID' => ['source' => 'lookup', 'set_at' => 'node_01', 'type' => 'integer'],

            'AKTUELL_DATUM' => ['source' => 'current_time_berlin', 'set_at' => 'node_01', 'type' => 'string', 'format' => 'YYYY-MM-DD'],
            'AKTUELL_ZEIT' => ['source' => 'current_time_berlin', 'set_at' => 'node_01', 'type' => 'string', 'format' => 'HH:MM'],
            'AKTUELL_WOCHENTAG' => ['source' => 'current_time_berlin', 'set_at' => 'node_01', 'type' => 'string'],

            'CUSTOMER_STATUS' => ['source' => 'check_customer', 'set_at' => 'node_01', 'type' => 'enum', 'values' => ['found', 'new_customer', 'anonymous']],
            'CUSTOMER_ID' => ['source' => 'check_customer', 'set_at' => 'node_01', 'type' => 'integer', 'nullable' => true],
            'CUSTOMER_NAME' => ['source' => 'check_customer or node_05', 'set_at' => 'node_01 or node_05', 'type' => 'string', 'nullable' => true],

            // Phase 2: Routing
            'GREETING_TYPE' => ['source' => 'node_02', 'set_at' => 'node_02', 'type' => 'enum', 'values' => ['known', 'new', 'anonymous']],
            'NAME_SOURCE' => ['source' => 'node_02 or node_05', 'set_at' => 'node_02 or node_05', 'type' => 'enum', 'values' => ['database', 'user_input']],

            // Phase 3: Intent
            'INTENT' => ['source' => 'node_04', 'set_at' => 'node_04', 'type' => 'enum', 'values' => ['book', 'query', 'reschedule', 'cancel', 'info']],

            // Phase 4: Booking Details
            'SERVICE_NAME' => ['source' => 'node_06', 'set_at' => 'node_06', 'type' => 'string', 'default' => 'Beratung'],
            'REQUESTED_DATE' => ['source' => 'node_07', 'set_at' => 'node_07', 'type' => 'string', 'format' => 'YYYY-MM-DD'],
            'REQUESTED_TIME' => ['source' => 'node_07', 'set_at' => 'node_07', 'type' => 'string', 'format' => 'HH:MM'],
            'DATETIME_STRING' => ['source' => 'node_07', 'set_at' => 'node_07', 'type' => 'string', 'format' => 'Weekday, DD.MM um HH:MM Uhr'],

            // Phase 5: Availability
            'SLOT_AVAILABLE' => ['source' => 'node_08', 'set_at' => 'node_08', 'type' => 'boolean'],
            'ALTERNATIVES' => ['source' => 'node_08', 'set_at' => 'node_08', 'type' => 'array'],

            // Phase 6: Confirmation & Booking
            'USER_CONFIRMED' => ['source' => 'node_09a', 'set_at' => 'node_09a', 'type' => 'boolean'],
            'BOOKING_ATTEMPT_COUNT' => ['source' => 'node_09c', 'set_at' => 'node_09c', 'type' => 'integer', 'default' => 0],
            'RACE_CONDITION_COUNT' => ['source' => 'node_15', 'set_at' => 'node_15', 'type' => 'integer', 'default' => 0],

            // Phase 7: Result
            'BOOKING_SUCCESS' => ['source' => 'node_09c', 'set_at' => 'node_09c', 'type' => 'boolean'],
            'APPOINTMENT_ID' => ['source' => 'node_09c', 'set_at' => 'node_09c', 'type' => 'integer', 'nullable' => true],

            // Audit Trail
            'CONVERSATION_PATH' => ['source' => 'system', 'set_at' => 'all_nodes', 'type' => 'array', 'description' => 'Array of node IDs visited'],
            'ERROR_OCCURRED' => ['source' => 'node_99', 'set_at' => 'node_99', 'type' => 'boolean', 'default' => false],
            'ERROR_TYPE' => ['source' => 'node_99', 'set_at' => 'node_99', 'type' => 'string', 'nullable' => true]
        ];
    }

    /**
     * Generate Mermaid diagram
     */
    private function generateMermaidDiagram(): string
    {
        $mermaid = "graph TB\n";
        $mermaid .= "    START([Call Initiated]) --> INIT[Node 1: INITIALIZATION]\n\n";

        $mermaid .= "    INIT --> |Parallel Functions| FUNC_TIME[current_time_berlin]\n";
        $mermaid .= "    INIT --> |Parallel Functions| FUNC_CUSTOMER[check_customer]\n\n";

        $mermaid .= "    FUNC_TIME --> CONTEXT[Context Variables Set]\n";
        $mermaid .= "    FUNC_CUSTOMER --> CONTEXT\n\n";

        $mermaid .= "    CONTEXT --> ROUTE{Node 2: CUSTOMER_ROUTING}\n\n";

        $mermaid .= "    ROUTE --> |status='found'| KNOWN[Node 3a: KNOWN_CUSTOMER]\n";
        $mermaid .= "    ROUTE --> |status='new_customer'| NEW[Node 3b: NEW_CUSTOMER]\n";
        $mermaid .= "    ROUTE --> |status='anonymous'| ANON[Node 3c: ANONYMOUS_CUSTOMER]\n\n";

        $mermaid .= "    KNOWN --> INTENT{Node 4: INTENT_CAPTURE}\n";
        $mermaid .= "    NEW --> INTENT\n";
        $mermaid .= "    ANON --> NAME_COLLECT[Node 5: NAME_COLLECTION]\n\n";

        $mermaid .= "    NAME_COLLECT --> |Name provided| INTENT\n\n";

        $mermaid .= "    INTENT --> |Booking request| SERVICE[Node 6: SERVICE_SELECTION]\n";
        $mermaid .= "    SERVICE --> DATETIME[Node 7: DATETIME_COLLECTION]\n";
        $mermaid .= "    DATETIME --> AVAIL_CHECK[Node 8: AVAILABILITY_CHECK]\n\n";

        $mermaid .= "    AVAIL_CHECK --> |Available| CONFIRM[Node 9a: BOOKING_CONFIRMATION]\n";
        $mermaid .= "    AVAIL_CHECK --> |Unavailable| ALTERNATIVES[Node 9b: ALTERNATIVE_OFFERING]\n\n";

        $mermaid .= "    ALTERNATIVES --> |User selects alt| CONFIRM\n";
        $mermaid .= "    ALTERNATIVES --> |User requests new| DATETIME\n\n";

        $mermaid .= "    CONFIRM --> |User says \"yes\"| BOOK[Node 9c: FINAL_BOOKING]\n";
        $mermaid .= "    CONFIRM --> |User says \"no\"| ALTERNATIVES\n\n";

        $mermaid .= "    BOOK --> |Success| SUCCESS[Node 14: SUCCESS_MESSAGE]\n";
        $mermaid .= "    BOOK --> |Race condition| RACE_HANDLER[Node 15: RACE_CONDITION_HANDLER]\n\n";

        $mermaid .= "    RACE_HANDLER --> |Alt available| ALTERNATIVES\n";
        $mermaid .= "    RACE_HANDLER --> |No alt| DATETIME\n\n";

        $mermaid .= "    SUCCESS --> END_SUCCESS[Call End: SUCCESS]\n\n";

        $mermaid .= "    style START fill:#4a90e2,color:#fff\n";
        $mermaid .= "    style INIT fill:#7cb342,color:#fff\n";
        $mermaid .= "    style ROUTE fill:#ffa726,color:#fff\n";
        $mermaid .= "    style INTENT fill:#ffa726,color:#fff\n";
        $mermaid .= "    style AVAIL_CHECK fill:#ab47bc,color:#fff\n";
        $mermaid .= "    style BOOK fill:#ab47bc,color:#fff\n";
        $mermaid .= "    style SUCCESS fill:#4caf50,color:#fff\n";
        $mermaid .= "    style RACE_HANDLER fill:#ff9800,color:#fff\n";

        return $mermaid;
    }

    /**
     * Save graph to storage
     */
    private function saveGraph(array $graph): void
    {
        Storage::disk('local')->put(
            'conversation_flow/graphs/node_graph.json',
            json_encode($graph, JSON_PRETTY_PRINT)
        );

        Storage::disk('local')->put(
            'conversation_flow/graphs/conversation_flow.mermaid',
            $graph['mermaid_diagram']
        );

        Log::info('Conversation Flow node graph saved', [
            'total_nodes' => $graph['total_nodes'],
            'total_transitions' => $graph['total_transitions']
        ]);
    }
}
