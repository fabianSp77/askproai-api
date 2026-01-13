<?php

namespace Database\Seeders;

use App\Models\WebhookPreset;
use Illuminate\Database\Seeder;

/**
 * WebhookPresetSeeder
 *
 * Seeds system-provided webhook presets for common integrations:
 * - Jira (incident, request)
 * - ServiceNow (incident)
 * - OTRS (ticket)
 * - Zendesk (ticket)
 * - Slack (message blocks)
 * - Microsoft Teams (adaptive cards)
 */
class WebhookPresetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $presets = [
            $this->jiraIncidentPreset(),
            $this->jiraServiceRequestPreset(),
            $this->serviceNowIncidentPreset(),
            $this->otrsTicketPreset(),
            $this->zendeskTicketPreset(),
            $this->slackMessagePreset(),
            $this->teamsAdaptiveCardPreset(),
            $this->genericWebhookPreset(),
        ];

        foreach ($presets as $preset) {
            WebhookPreset::updateOrCreate(
                ['slug' => $preset['slug']],
                $preset
            );
        }

        $this->command->info('Seeded ' . count($presets) . ' webhook presets.');
    }

    /**
     * Jira Incident (Bug) preset
     */
    private function jiraIncidentPreset(): array
    {
        return [
            'name' => 'Jira - Incident (Bug)',
            'slug' => 'jira-incident',
            'description' => 'Creates a Bug issue in Jira for incident tracking. Maps priority and includes full problem description.',
            'target_system' => WebhookPreset::SYSTEM_JIRA,
            'category' => WebhookPreset::CATEGORY_TICKETING,
            'payload_template' => [
                'fields' => [
                    'project' => [
                        'key' => '{{jira.project_key|default:SUPPORT}}',
                    ],
                    'issuetype' => [
                        'name' => 'Bug',
                    ],
                    'summary' => '{{case.subject}}',
                    'description' => [
                        'type' => 'doc',
                        'version' => 1,
                        'content' => [
                            [
                                'type' => 'heading',
                                'attrs' => ['level' => 2],
                                'content' => [
                                    ['type' => 'text', 'text' => 'Problembeschreibung'],
                                ],
                            ],
                            [
                                'type' => 'paragraph',
                                'content' => [
                                    ['type' => 'text', 'text' => '{{case.description}}'],
                                ],
                            ],
                            [
                                'type' => 'heading',
                                'attrs' => ['level' => 3],
                                'content' => [
                                    ['type' => 'text', 'text' => 'Kontaktinformationen'],
                                ],
                            ],
                            [
                                'type' => 'bulletList',
                                'content' => [
                                    [
                                        'type' => 'listItem',
                                        'content' => [
                                            [
                                                'type' => 'paragraph',
                                                'content' => [
                                                    ['type' => 'text', 'text' => 'Name: {{customer.name|default:Unbekannt}}'],
                                                ],
                                            ],
                                        ],
                                    ],
                                    [
                                        'type' => 'listItem',
                                        'content' => [
                                            [
                                                'type' => 'paragraph',
                                                'content' => [
                                                    ['type' => 'text', 'text' => 'Telefon: {{customer.phone|default:Nicht angegeben}}'],
                                                ],
                                            ],
                                        ],
                                    ],
                                    [
                                        'type' => 'listItem',
                                        'content' => [
                                            [
                                                'type' => 'paragraph',
                                                'content' => [
                                                    ['type' => 'text', 'text' => 'Problem seit: {{context.problem_since|default:Unbekannt}}'],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'priority' => [
                        'name' => '{{case.priority|default:Medium}}',
                    ],
                    'labels' => ['ai-generated', 'service-gateway'],
                    'customfield_10001' => '{{ticket.reference}}', // External reference
                ],
            ],
            'headers_template' => [
                'Content-Type' => 'application/json',
                'X-Source' => 'ServiceGateway',
            ],
            'variable_schema' => [
                'case.subject' => ['type' => 'string', 'required' => true],
                'case.description' => ['type' => 'string', 'required' => true],
                'case.priority' => ['type' => 'string', 'required' => false],
                'jira.project_key' => ['type' => 'string', 'required' => false],
            ],
            'default_values' => [
                'jira' => [
                    'project_key' => 'SUPPORT',
                ],
            ],
            'auth_type' => WebhookPreset::AUTH_BASIC,
            'auth_instructions' => 'Use Jira API token as password. Username is your Atlassian email.',
            'version' => '1.0.0',
            'is_active' => true,
            'is_system' => true,
            'documentation_url' => 'https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-issues/',
            'example_response' => [
                'id' => '10001',
                'key' => 'SUPPORT-123',
                'self' => 'https://your-domain.atlassian.net/rest/api/3/issue/10001',
            ],
        ];
    }

    /**
     * Jira Service Request (Task) preset
     */
    private function jiraServiceRequestPreset(): array
    {
        return [
            'name' => 'Jira - Service Request (Task)',
            'slug' => 'jira-service-request',
            'description' => 'Creates a Task issue in Jira for service requests. Suitable for non-incident work items.',
            'target_system' => WebhookPreset::SYSTEM_JIRA,
            'category' => WebhookPreset::CATEGORY_TICKETING,
            'payload_template' => [
                'fields' => [
                    'project' => [
                        'key' => '{{jira.project_key|default:SUPPORT}}',
                    ],
                    'issuetype' => [
                        'name' => 'Task',
                    ],
                    'summary' => '[Anfrage] {{case.subject}}',
                    'description' => [
                        'type' => 'doc',
                        'version' => 1,
                        'content' => [
                            [
                                'type' => 'paragraph',
                                'content' => [
                                    ['type' => 'text', 'text' => '{{case.description}}'],
                                ],
                            ],
                            [
                                'type' => 'rule',
                            ],
                            [
                                'type' => 'paragraph',
                                'content' => [
                                    ['type' => 'text', 'marks' => [['type' => 'strong']], 'text' => 'Kontakt: '],
                                    ['type' => 'text', 'text' => '{{customer.name}} ({{customer.phone}})'],
                                ],
                            ],
                        ],
                    ],
                    'priority' => [
                        'name' => '{{case.priority|default:Medium}}',
                    ],
                    'labels' => ['service-request', 'ai-generated'],
                ],
            ],
            'variable_schema' => [
                'case.subject' => ['type' => 'string', 'required' => true],
                'case.description' => ['type' => 'string', 'required' => true],
            ],
            'default_values' => [
                'jira' => ['project_key' => 'SUPPORT'],
            ],
            'auth_type' => WebhookPreset::AUTH_BASIC,
            'auth_instructions' => 'Use Jira API token as password.',
            'version' => '1.0.0',
            'is_active' => true,
            'is_system' => true,
            'documentation_url' => 'https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-issues/',
            'example_response' => [
                'id' => '10002',
                'key' => 'SUPPORT-124',
            ],
        ];
    }

    /**
     * ServiceNow Incident preset
     */
    private function serviceNowIncidentPreset(): array
    {
        return [
            'name' => 'ServiceNow - Incident',
            'slug' => 'servicenow-incident',
            'description' => 'Creates an Incident record in ServiceNow with proper categorization and urgency mapping.',
            'target_system' => WebhookPreset::SYSTEM_SERVICENOW,
            'category' => WebhookPreset::CATEGORY_TICKETING,
            'payload_template' => [
                'short_description' => '{{case.subject}}',
                'description' => "{{case.description}}\n\n--- Caller Information ---\nName: {{customer.name|default:Unknown}}\nPhone: {{customer.phone|default:Not provided}}\nEmail: {{customer.email|default:Not provided}}\n\n--- Additional Context ---\nProblem since: {{context.problem_since|default:Unknown}}\nOthers affected: {{#if context.others_affected}}Yes{{/if}}{{#unless context.others_affected}}No{{/unless}}\nCategory: {{case.category}}\n\nReference: {{ticket.reference}}",
                'urgency' => '{{servicenow.urgency|default:2}}',
                'impact' => '{{servicenow.impact|default:2}}',
                'category' => '{{servicenow.category|default:Inquiry / Help}}',
                'subcategory' => '{{servicenow.subcategory|default:General}}',
                'caller_id' => '{{customer.email}}',
                'contact_type' => '{{case.contact_type}}', // Dynamically mapped from source
                'state' => '1', // New
                'assignment_group' => '{{servicenow.assignment_group|default:Service Desk}}',
                'correlation_id' => '{{ticket.reference}}',
                '{{#if enrichment.audio_url}}u_recording_url{{/if}}' => '{{enrichment.audio_url}}',
            ],
            'headers_template' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'variable_schema' => [
                'case.subject' => ['type' => 'string', 'required' => true],
                'case.description' => ['type' => 'string', 'required' => true],
                'servicenow.urgency' => ['type' => 'integer', 'required' => false],
                'servicenow.impact' => ['type' => 'integer', 'required' => false],
            ],
            'default_values' => [
                'servicenow' => [
                    'urgency' => 2,
                    'impact' => 2,
                    'category' => 'Inquiry / Help',
                    'subcategory' => 'General',
                    'assignment_group' => 'Service Desk',
                ],
            ],
            'auth_type' => WebhookPreset::AUTH_BASIC,
            'auth_instructions' => 'Use ServiceNow integration user credentials. Enable Basic Auth for the Table API.',
            'version' => '1.0.0',
            'is_active' => true,
            'is_system' => true,
            'documentation_url' => 'https://developer.servicenow.com/dev.do#!/reference/api/tokyo/rest/c_TableAPI',
            'example_response' => [
                'result' => [
                    'sys_id' => 'a1b2c3d4e5f6g7h8',
                    'number' => 'INC0010001',
                    'sys_created_on' => '2025-01-04 10:00:00',
                ],
            ],
        ];
    }

    /**
     * OTRS Ticket preset
     */
    private function otrsTicketPreset(): array
    {
        return [
            'name' => 'OTRS - Ticket',
            'slug' => 'otrs-ticket',
            'description' => 'Creates a ticket in OTRS (Znuny) with full customer and problem information.',
            'target_system' => WebhookPreset::SYSTEM_OTRS,
            'category' => WebhookPreset::CATEGORY_TICKETING,
            'payload_template' => [
                'Ticket' => [
                    'Title' => '{{case.subject}}',
                    'Queue' => '{{otrs.queue|default:Raw}}',
                    'Type' => '{{#if case.case_type}}{{case.case_type}}{{/if}}{{#unless case.case_type}}Incident{{/unless}}',
                    'State' => 'new',
                    'Priority' => '{{case.priority|default:3 normal}}',
                    'CustomerUser' => '{{customer.email|default:unknown@example.com}}',
                ],
                'Article' => [
                    'CommunicationChannel' => 'Phone',
                    'SenderType' => 'customer',
                    'Subject' => '{{case.subject}}',
                    'Body' => "{{case.description}}\n\n--\nAnrufer: {{customer.name}}\nTelefon: {{customer.phone}}\nProblem seit: {{context.problem_since|default:Unbekannt}}\n\nReferenz: {{ticket.reference}}",
                    'ContentType' => 'text/plain; charset=utf8',
                ],
            ],
            'variable_schema' => [
                'case.subject' => ['type' => 'string', 'required' => true],
                'case.description' => ['type' => 'string', 'required' => true],
            ],
            'default_values' => [
                'otrs' => [
                    'queue' => 'Raw',
                ],
            ],
            'auth_type' => WebhookPreset::AUTH_BASIC,
            'auth_instructions' => 'Configure OTRS GenericInterface with REST connector and BasicAuth.',
            'version' => '1.0.0',
            'is_active' => true,
            'is_system' => true,
            'documentation_url' => 'https://doc.znuny.org/doc/api/otrs/7.0/Perl/index.html',
            'example_response' => [
                'TicketID' => '123',
                'TicketNumber' => '2025010410000001',
                'ArticleID' => '456',
            ],
        ];
    }

    /**
     * Zendesk Ticket preset
     */
    private function zendeskTicketPreset(): array
    {
        return [
            'name' => 'Zendesk - Ticket',
            'slug' => 'zendesk-ticket',
            'description' => 'Creates a support ticket in Zendesk with proper priority and tags.',
            'target_system' => WebhookPreset::SYSTEM_ZENDESK,
            'category' => WebhookPreset::CATEGORY_TICKETING,
            'payload_template' => [
                'ticket' => [
                    'subject' => '{{case.subject}}',
                    'comment' => [
                        'body' => "{{case.description}}\n\n---\nAnrufer: {{customer.name}}\nTelefon: {{customer.phone}}\nE-Mail: {{customer.email|default:Nicht angegeben}}\nProblem seit: {{context.problem_since|default:Unbekannt}}\n\nReferenz: {{ticket.reference}}",
                    ],
                    'priority' => '{{case.priority|default:normal}}',
                    'type' => '{{#if case.case_type}}{{case.case_type}}{{/if}}{{#unless case.case_type}}problem{{/unless}}',
                    'tags' => ['ai-generated', 'phone-call', 'service-gateway'],
                    'requester' => [
                        'name' => '{{customer.name|default:Unknown Caller}}',
                        'email' => '{{customer.email}}',
                    ],
                    'external_id' => '{{ticket.reference}}',
                ],
            ],
            'variable_schema' => [
                'case.subject' => ['type' => 'string', 'required' => true],
                'case.description' => ['type' => 'string', 'required' => true],
            ],
            'auth_type' => WebhookPreset::AUTH_BASIC,
            'auth_instructions' => 'Use email/token authentication: {email}/token:{api_token}',
            'version' => '1.0.0',
            'is_active' => true,
            'is_system' => true,
            'documentation_url' => 'https://developer.zendesk.com/api-reference/ticketing/tickets/tickets/',
            'example_response' => [
                'ticket' => [
                    'id' => 12345,
                    'url' => 'https://yoursubdomain.zendesk.com/api/v2/tickets/12345.json',
                ],
            ],
        ];
    }

    /**
     * Slack Message preset
     */
    private function slackMessagePreset(): array
    {
        return [
            'name' => 'Slack - Rich Message',
            'slug' => 'slack-message',
            'description' => 'Sends a formatted message to a Slack channel using Block Kit.',
            'target_system' => WebhookPreset::SYSTEM_SLACK,
            'category' => WebhookPreset::CATEGORY_MESSAGING,
            'payload_template' => [
                'text' => 'Neuer Service Case: {{case.subject}}',
                'blocks' => [
                    [
                        'type' => 'header',
                        'text' => [
                            'type' => 'plain_text',
                            'text' => '{{case.category}} - {{case.priority}}',
                            'emoji' => true,
                        ],
                    ],
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => "*{{case.subject}}*\n{{case.description}}",
                        ],
                    ],
                    [
                        'type' => 'divider',
                    ],
                    [
                        'type' => 'section',
                        'fields' => [
                            [
                                'type' => 'mrkdwn',
                                'text' => "*Anrufer:*\n{{customer.name|default:Unbekannt}}",
                            ],
                            [
                                'type' => 'mrkdwn',
                                'text' => "*Telefon:*\n{{customer.phone|default:N/A}}",
                            ],
                            [
                                'type' => 'mrkdwn',
                                'text' => "*Problem seit:*\n{{context.problem_since|default:Unbekannt}}",
                            ],
                            [
                                'type' => 'mrkdwn',
                                'text' => "*Referenz:*\n`{{ticket.reference}}`",
                            ],
                        ],
                    ],
                    [
                        'type' => 'context',
                        'elements' => [
                            [
                                'type' => 'mrkdwn',
                                'text' => 'Erstellt: {{case.created_at}} | Service Gateway',
                            ],
                        ],
                    ],
                ],
            ],
            'variable_schema' => [
                'case.subject' => ['type' => 'string', 'required' => true],
            ],
            'auth_type' => WebhookPreset::AUTH_NONE,
            'auth_instructions' => 'Use Slack Incoming Webhook URL. No additional auth required.',
            'version' => '1.0.0',
            'is_active' => true,
            'is_system' => true,
            'documentation_url' => 'https://api.slack.com/messaging/webhooks',
            'example_response' => [
                'ok' => true,
            ],
        ];
    }

    /**
     * Microsoft Teams Adaptive Card preset
     */
    private function teamsAdaptiveCardPreset(): array
    {
        return [
            'name' => 'Microsoft Teams - Adaptive Card',
            'slug' => 'teams-adaptive-card',
            'description' => 'Sends a rich Adaptive Card message to a Teams channel.',
            'target_system' => WebhookPreset::SYSTEM_TEAMS,
            'category' => WebhookPreset::CATEGORY_MESSAGING,
            'payload_template' => [
                'type' => 'message',
                'attachments' => [
                    [
                        'contentType' => 'application/vnd.microsoft.card.adaptive',
                        'contentUrl' => null,
                        'content' => [
                            '$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
                            'type' => 'AdaptiveCard',
                            'version' => '1.4',
                            'body' => [
                                [
                                    'type' => 'TextBlock',
                                    'size' => 'Large',
                                    'weight' => 'Bolder',
                                    'text' => '{{case.category}} - Neuer Service Case',
                                    'wrap' => true,
                                ],
                                [
                                    'type' => 'TextBlock',
                                    'text' => '{{case.subject}}',
                                    'weight' => 'Bolder',
                                    'wrap' => true,
                                ],
                                [
                                    'type' => 'TextBlock',
                                    'text' => '{{case.description}}',
                                    'wrap' => true,
                                ],
                                [
                                    'type' => 'FactSet',
                                    'facts' => [
                                        ['title' => 'Prioritaet', 'value' => '{{case.priority}}'],
                                        ['title' => 'Anrufer', 'value' => '{{customer.name|default:Unbekannt}}'],
                                        ['title' => 'Telefon', 'value' => '{{customer.phone|default:N/A}}'],
                                        ['title' => 'Problem seit', 'value' => '{{context.problem_since|default:Unbekannt}}'],
                                        ['title' => 'Referenz', 'value' => '{{ticket.reference}}'],
                                    ],
                                ],
                            ],
                            'msteams' => [
                                'width' => 'Full',
                            ],
                        ],
                    ],
                ],
            ],
            'variable_schema' => [
                'case.subject' => ['type' => 'string', 'required' => true],
            ],
            'auth_type' => WebhookPreset::AUTH_NONE,
            'auth_instructions' => 'Use Teams Incoming Webhook URL. No additional auth required.',
            'version' => '1.0.0',
            'is_active' => true,
            'is_system' => true,
            'documentation_url' => 'https://learn.microsoft.com/en-us/microsoftteams/platform/webhooks-and-connectors/how-to/add-incoming-webhook',
            'example_response' => [
                '1' => 'Message sent successfully',
            ],
        ];
    }

    /**
     * Generic webhook preset (starting point for custom integrations)
     */
    private function genericWebhookPreset(): array
    {
        return [
            'name' => 'Generic - REST API',
            'slug' => 'generic-rest',
            'description' => 'A flexible starting template for custom REST API integrations. Includes all common fields.',
            'target_system' => WebhookPreset::SYSTEM_CUSTOM,
            'category' => WebhookPreset::CATEGORY_CUSTOM,
            'payload_template' => [
                'event' => 'service_case.created',
                'timestamp' => '{{timestamp}}',
                'reference' => '{{ticket.reference}}',
                'ticket' => [
                    'subject' => '{{case.subject}}',
                    'description' => '{{case.description}}',
                    'type' => '{{case.case_type}}',
                    'priority' => '{{case.priority}}',
                    'category' => '{{case.category}}',
                    'status' => '{{case.status}}',
                    'created_at' => '{{case.created_at}}',
                ],
                'customer' => [
                    'name' => '{{customer.name}}',
                    'phone' => '{{customer.phone}}',
                    'email' => '{{customer.email}}',
                ],
                'context' => [
                    'problem_since' => '{{context.problem_since}}',
                    'others_affected' => '{{context.others_affected}}',
                ],
                'enrichment' => [
                    'audio_url' => '{{enrichment.audio_url}}',
                    'audio_expires_at' => '{{enrichment.audio_url_expires_at}}',
                    'transcript_available' => '{{enrichment.transcript_available}}',
                ],
            ],
            'headers_template' => [
                'Content-Type' => 'application/json',
                'X-Source' => 'ServiceGateway',
                'X-Event' => 'service_case.created',
            ],
            'variable_schema' => [
                'case.subject' => ['type' => 'string', 'required' => true],
                'case.description' => ['type' => 'string', 'required' => true],
            ],
            'auth_type' => WebhookPreset::AUTH_HMAC,
            'auth_instructions' => 'Configure a webhook secret in the output configuration. HMAC-SHA256 signature will be sent in X-Signature header.',
            'version' => '1.0.0',
            'is_active' => true,
            'is_system' => true,
            'documentation_url' => null,
            'example_response' => [
                'id' => 'string',
                'status' => 'created',
            ],
        ];
    }
}
