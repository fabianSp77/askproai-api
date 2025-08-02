<?php

namespace App\Services\MCP;

use App\Models\User;
use App\Models\PortalUser;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PragmaRX\Google2FA\Google2FA;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class SettingsMCPServer
{
    protected string $name = 'Settings Management MCP Server';
    protected string $version = '1.0.0';

    /**
     * Get available tools for this MCP server.
     */
    public function getTools(): array
    {
        return [
            [
                'name' => 'getUserProfile',
                'description' => 'Get user profile information',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'user_id' => ['type' => 'integer', 'required' => true]
                    ],
                    'required' => ['user_id']
                ]
            ],
            [
                'name' => 'updateUserProfile',
                'description' => 'Update user profile information',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'user_id' => ['type' => 'integer', 'required' => true],
                        'name' => ['type' => 'string', 'required' => true],
                        'email' => ['type' => 'string', 'format' => 'email', 'required' => true],
                        'phone' => ['type' => 'string']
                    ],
                    'required' => ['user_id', 'name', 'email']
                ]
            ],
            [
                'name' => 'changePassword',
                'description' => 'Change user password',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'user_id' => ['type' => 'integer', 'required' => true],
                        'current_password' => ['type' => 'string', 'required' => true],
                        'new_password' => ['type' => 'string', 'required' => true]
                    ],
                    'required' => ['user_id', 'current_password', 'new_password']
                ]
            ],
            [
                'name' => 'getCompanySettings',
                'description' => 'Get company settings and information',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'company_id' => ['type' => 'integer', 'required' => true]
                    ],
                    'required' => ['company_id']
                ]
            ],
            [
                'name' => 'updateCompanySettings',
                'description' => 'Update company settings',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'company_id' => ['type' => 'integer', 'required' => true],
                        'name' => ['type' => 'string'],
                        'email' => ['type' => 'string', 'format' => 'email'],
                        'phone' => ['type' => 'string'],
                        'address' => ['type' => 'string'],
                        'timezone' => ['type' => 'string'],
                        'language' => ['type' => 'string', 'enum' => ['de', 'en', 'fr']],
                        'currency' => ['type' => 'string', 'enum' => ['EUR', 'USD', 'GBP']]
                    ],
                    'required' => ['company_id']
                ]
            ],
            [
                'name' => 'getNotificationPreferences',
                'description' => 'Get user notification preferences',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'user_id' => ['type' => 'integer', 'required' => true]
                    ],
                    'required' => ['user_id']
                ]
            ],
            [
                'name' => 'updateNotificationPreferences',
                'description' => 'Update user notification preferences',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'user_id' => ['type' => 'integer', 'required' => true],
                        'email_notifications' => ['type' => 'boolean'],
                        'sms_notifications' => ['type' => 'boolean'],
                        'appointment_reminders' => ['type' => 'boolean'],
                        'daily_summary' => ['type' => 'boolean'],
                        'marketing_emails' => ['type' => 'boolean'],
                        'call_assigned' => ['type' => 'boolean'],
                        'callback_reminder' => ['type' => 'boolean'],
                        'low_balance_alert' => ['type' => 'boolean']
                    ],
                    'required' => ['user_id']
                ]
            ],
            [
                'name' => 'enable2FA',
                'description' => 'Enable two-factor authentication',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'user_id' => ['type' => 'integer', 'required' => true]
                    ],
                    'required' => ['user_id']
                ]
            ],
            [
                'name' => 'confirm2FA',
                'description' => 'Confirm two-factor authentication with code',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'user_id' => ['type' => 'integer', 'required' => true],
                        'code' => ['type' => 'string', 'required' => true]
                    ],
                    'required' => ['user_id', 'code']
                ]
            ],
            [
                'name' => 'disable2FA',
                'description' => 'Disable two-factor authentication',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'user_id' => ['type' => 'integer', 'required' => true]
                    ],
                    'required' => ['user_id']
                ]
            ],
            [
                'name' => 'getCallNotificationSettings',
                'description' => 'Get call notification settings for company',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'company_id' => ['type' => 'integer', 'required' => true],
                        'user_id' => ['type' => 'integer']
                    ],
                    'required' => ['company_id']
                ]
            ],
            [
                'name' => 'updateCallNotificationSettings',
                'description' => 'Update call notification settings',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'company_id' => ['type' => 'integer', 'required' => true],
                        'send_call_summaries' => ['type' => 'boolean'],
                        'call_summary_recipients' => ['type' => 'array', 'items' => ['type' => 'string', 'format' => 'email']],
                        'include_transcript_in_summary' => ['type' => 'boolean'],
                        'include_csv_export' => ['type' => 'boolean'],
                        'summary_email_frequency' => ['type' => 'string', 'enum' => ['immediate', 'hourly', 'daily']]
                    ],
                    'required' => ['company_id']
                ]
            ],
            [
                'name' => 'getAPIKeys',
                'description' => 'Get API keys for company integrations',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'company_id' => ['type' => 'integer', 'required' => true]
                    ],
                    'required' => ['company_id']
                ]
            ],
            [
                'name' => 'regenerateAPIKey',
                'description' => 'Regenerate API key for a specific integration',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'company_id' => ['type' => 'integer', 'required' => true],
                        'integration' => ['type' => 'string', 'required' => true, 'enum' => ['retell', 'calcom', 'webhook']]
                    ],
                    'required' => ['company_id', 'integration']
                ]
            ]
        ];
    }

    /**
     * Handle tool execution.
     */
    public function executeTool(string $name, array $arguments): array
    {
        try {
            switch ($name) {
                case 'getUserProfile':
                    return $this->getUserProfile($arguments);
                case 'updateUserProfile':
                    return $this->updateUserProfile($arguments);
                case 'changePassword':
                    return $this->changePassword($arguments);
                case 'getCompanySettings':
                    return $this->getCompanySettings($arguments);
                case 'updateCompanySettings':
                    return $this->updateCompanySettings($arguments);
                case 'getNotificationPreferences':
                    return $this->getNotificationPreferences($arguments);
                case 'updateNotificationPreferences':
                    return $this->updateNotificationPreferences($arguments);
                case 'enable2FA':
                    return $this->enable2FA($arguments);
                case 'confirm2FA':
                    return $this->confirm2FA($arguments);
                case 'disable2FA':
                    return $this->disable2FA($arguments);
                case 'getCallNotificationSettings':
                    return $this->getCallNotificationSettings($arguments);
                case 'updateCallNotificationSettings':
                    return $this->updateCallNotificationSettings($arguments);
                case 'getAPIKeys':
                    return $this->getAPIKeys($arguments);
                case 'regenerateAPIKey':
                    return $this->regenerateAPIKey($arguments);
                default:
                    throw new \Exception("Unknown tool: {$name}");
            }
        } catch (\Exception $e) {
            Log::error("SettingsMCPServer error in {$name}", [
                'error' => $e->getMessage(),
                'arguments' => $arguments
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get user profile information.
     */
    protected function getUserProfile(array $params): array
    {
        $userId = $params['user_id'];
        
        // Try portal user first, then regular user
        $user = PortalUser::find($userId) ?: User::find($userId);
        
        if (!$user) {
            throw new \Exception('User not found');
        }

        return [
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? null,
                'role' => $user->role ?? 'user',
                'avatar_url' => $user->avatar_url ?? null,
                'two_factor_enabled' => !empty($user->two_factor_confirmed_at),
                'created_at' => $user->created_at->toIso8601String(),
                'last_login_at' => $user->last_login_at ? $user->last_login_at->toIso8601String() : null,
                'is_portal_user' => $user instanceof PortalUser,
                'company_id' => $user->company_id ?? null
            ]
        ];
    }

    /**
     * Update user profile.
     */
    protected function updateUserProfile(array $params): array
    {
        $userId = $params['user_id'];
        
        // Try portal user first, then regular user
        $user = PortalUser::find($userId) ?: User::find($userId);
        
        if (!$user) {
            throw new \Exception('User not found');
        }

        // Check email uniqueness
        $emailExistsPortal = PortalUser::where('email', $params['email'])
            ->where('id', '!=', $userId)
            ->exists();
            
        $emailExistsUser = User::where('email', $params['email'])
            ->where('id', '!=', $userId)
            ->exists();
            
        if ($emailExistsPortal || $emailExistsUser) {
            throw new \Exception('Email already in use');
        }

        // Update user
        $user->update([
            'name' => $params['name'],
            'email' => $params['email'],
            'phone' => $params['phone'] ?? $user->phone
        ]);

        return [
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone
            ]
        ];
    }

    /**
     * Change user password.
     */
    protected function changePassword(array $params): array
    {
        $userId = $params['user_id'];
        
        // Try portal user first, then regular user
        $user = PortalUser::find($userId) ?: User::find($userId);
        
        if (!$user) {
            throw new \Exception('User not found');
        }

        // Verify current password
        if (!Hash::check($params['current_password'], $user->password)) {
            throw new \Exception('Current password is incorrect');
        }

        // Update password
        $user->update([
            'password' => Hash::make($params['new_password'])
        ]);

        return [
            'success' => true,
            'message' => 'Password changed successfully'
        ];
    }

    /**
     * Get company settings.
     */
    protected function getCompanySettings(array $params): array
    {
        $company = Company::find($params['company_id']);
        
        if (!$company) {
            throw new \Exception('Company not found');
        }

        return [
            'success' => true,
            'data' => [
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->email ?? null,
                'phone' => $company->phone ?? null,
                'address' => $company->address ?? null,
                'timezone' => $company->timezone ?? 'Europe/Berlin',
                'language' => $company->language ?? 'de',
                'currency' => $company->currency ?? 'EUR',
                'subscription' => [
                    'plan_name' => $company->subscription_plan ?? 'Basic',
                    'next_billing_date' => $company->next_billing_date ?? null,
                    'used_minutes' => $company->used_minutes ?? 0,
                    'included_minutes' => $company->included_minutes ?? 1000,
                    'prepaid_balance' => $company->prepaid_balance ?? 0,
                    'billing_rate_per_minute' => $company->billing_rate_per_minute ?? 0.50
                ],
                'features' => [
                    'appointments_enabled' => $company->needsAppointmentBooking(),
                    'sms_enabled' => $company->sms_enabled ?? false,
                    'whatsapp_enabled' => $company->whatsapp_enabled ?? false,
                    'multi_language_enabled' => $company->multi_language_enabled ?? false
                ],
                'branding' => [
                    'logo_url' => $company->logo_url ?? null,
                    'primary_color' => $company->primary_color ?? '#3B82F6',
                    'secondary_color' => $company->secondary_color ?? '#6B7280'
                ]
            ]
        ];
    }

    /**
     * Update company settings.
     */
    protected function updateCompanySettings(array $params): array
    {
        $company = Company::find($params['company_id']);
        
        if (!$company) {
            throw new \Exception('Company not found');
        }

        // Update only provided fields
        $updateData = [];
        foreach (['name', 'email', 'phone', 'address', 'timezone', 'language', 'currency'] as $field) {
            if (isset($params[$field])) {
                $updateData[$field] = $params[$field];
            }
        }

        if (!empty($updateData)) {
            $company->update($updateData);
        }

        return [
            'success' => true,
            'data' => [
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->email,
                'phone' => $company->phone,
                'address' => $company->address,
                'timezone' => $company->timezone,
                'language' => $company->language,
                'currency' => $company->currency
            ]
        ];
    }

    /**
     * Get notification preferences.
     */
    protected function getNotificationPreferences(array $params): array
    {
        $userId = $params['user_id'];
        
        // Try portal user first, then regular user
        $user = PortalUser::find($userId) ?: User::find($userId);
        
        if (!$user) {
            throw new \Exception('User not found');
        }

        // Get preferences from user or defaults
        $preferences = $user->notification_preferences ?? [];
        
        return [
            'success' => true,
            'data' => [
                'email_notifications' => $preferences['email_notifications'] ?? true,
                'sms_notifications' => $preferences['sms_notifications'] ?? false,
                'appointment_reminders' => $preferences['appointment_reminders'] ?? true,
                'daily_summary' => $preferences['daily_summary'] ?? true,
                'marketing_emails' => $preferences['marketing_emails'] ?? false,
                'call_assigned' => $preferences['call_assigned'] ?? true,
                'callback_reminder' => $preferences['callback_reminder'] ?? true,
                'low_balance_alert' => $preferences['low_balance_alert'] ?? true
            ]
        ];
    }

    /**
     * Update notification preferences.
     */
    protected function updateNotificationPreferences(array $params): array
    {
        $userId = $params['user_id'];
        
        // Try portal user first, then regular user
        $user = PortalUser::find($userId) ?: User::find($userId);
        
        if (!$user) {
            throw new \Exception('User not found');
        }

        // Get existing preferences
        $preferences = $user->notification_preferences ?? [];
        
        // Update with provided values
        $fields = [
            'email_notifications', 'sms_notifications', 'appointment_reminders',
            'daily_summary', 'marketing_emails', 'call_assigned',
            'callback_reminder', 'low_balance_alert'
        ];
        
        foreach ($fields as $field) {
            if (isset($params[$field])) {
                $preferences[$field] = $params[$field];
            }
        }

        // Save preferences
        $user->notification_preferences = $preferences;
        $user->save();

        return [
            'success' => true,
            'data' => $preferences
        ];
    }

    /**
     * Enable two-factor authentication.
     */
    protected function enable2FA(array $params): array
    {
        $userId = $params['user_id'];
        
        // Try portal user first, then regular user
        $user = PortalUser::find($userId) ?: User::find($userId);
        
        if (!$user) {
            throw new \Exception('User not found');
        }

        if ($user->two_factor_confirmed_at) {
            throw new \Exception('2FA is already enabled');
        }

        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();
        
        // Store secret (encrypted)
        $user->two_factor_secret = encrypt($secret);
        $user->save();

        // Generate QR code URL
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        // Generate QR code image
        $qrCode = base64_encode(QrCode::format('png')->size(200)->generate($qrCodeUrl));

        return [
            'success' => true,
            'data' => [
                'secret' => $secret,
                'qr_code' => 'data:image/png;base64,' . $qrCode,
                'qr_code_url' => $qrCodeUrl
            ]
        ];
    }

    /**
     * Confirm 2FA with code.
     */
    protected function confirm2FA(array $params): array
    {
        $userId = $params['user_id'];
        $code = $params['code'];
        
        // Try portal user first, then regular user
        $user = PortalUser::find($userId) ?: User::find($userId);
        
        if (!$user) {
            throw new \Exception('User not found');
        }

        if (!$user->two_factor_secret) {
            throw new \Exception('2FA setup not started');
        }

        $google2fa = new Google2FA();
        $secret = decrypt($user->two_factor_secret);
        
        if (!$google2fa->verifyKey($secret, $code)) {
            throw new \Exception('Invalid verification code');
        }

        // Confirm 2FA
        $user->two_factor_confirmed_at = now();
        $user->save();

        // Generate recovery codes
        $recoveryCodes = $this->generateRecoveryCodes();
        $user->two_factor_recovery_codes = encrypt(json_encode($recoveryCodes));
        $user->save();

        return [
            'success' => true,
            'data' => [
                'recovery_codes' => $recoveryCodes,
                'message' => '2FA successfully enabled'
            ]
        ];
    }

    /**
     * Disable 2FA.
     */
    protected function disable2FA(array $params): array
    {
        $userId = $params['user_id'];
        
        // Try portal user first, then regular user
        $user = PortalUser::find($userId) ?: User::find($userId);
        
        if (!$user) {
            throw new \Exception('User not found');
        }

        // Clear 2FA data
        $user->two_factor_secret = null;
        $user->two_factor_confirmed_at = null;
        $user->two_factor_recovery_codes = null;
        $user->save();

        return [
            'success' => true,
            'message' => '2FA successfully disabled'
        ];
    }

    /**
     * Get call notification settings.
     */
    protected function getCallNotificationSettings(array $params): array
    {
        $company = Company::find($params['company_id']);
        
        if (!$company) {
            throw new \Exception('Company not found');
        }

        $settings = [
            'send_call_summaries' => $company->send_call_summaries ?? false,
            'call_summary_recipients' => $company->call_summary_recipients ?? [],
            'include_transcript_in_summary' => $company->include_transcript_in_summary ?? true,
            'include_csv_export' => $company->include_csv_export ?? false,
            'summary_email_frequency' => $company->summary_email_frequency ?? 'immediate'
        ];

        // If user_id provided, also get user preferences
        $userPreferences = null;
        if (isset($params['user_id'])) {
            $user = PortalUser::find($params['user_id']) ?: User::find($params['user_id']);
            if ($user) {
                $userPreferences = $user->call_notification_preferences ?? [
                    'receive_summaries' => false
                ];
            }
        }

        return [
            'success' => true,
            'data' => [
                'company_settings' => $settings,
                'user_preferences' => $userPreferences
            ]
        ];
    }

    /**
     * Update call notification settings.
     */
    protected function updateCallNotificationSettings(array $params): array
    {
        $company = Company::find($params['company_id']);
        
        if (!$company) {
            throw new \Exception('Company not found');
        }

        // Update only provided fields
        $fields = [
            'send_call_summaries',
            'call_summary_recipients',
            'include_transcript_in_summary',
            'include_csv_export',
            'summary_email_frequency'
        ];

        foreach ($fields as $field) {
            if (isset($params[$field])) {
                $company->$field = $params[$field];
            }
        }

        $company->save();

        return [
            'success' => true,
            'data' => [
                'send_call_summaries' => $company->send_call_summaries,
                'call_summary_recipients' => $company->call_summary_recipients,
                'include_transcript_in_summary' => $company->include_transcript_in_summary,
                'include_csv_export' => $company->include_csv_export,
                'summary_email_frequency' => $company->summary_email_frequency
            ]
        ];
    }

    /**
     * Get API keys.
     */
    protected function getAPIKeys(array $params): array
    {
        $company = Company::find($params['company_id']);
        
        if (!$company) {
            throw new \Exception('Company not found');
        }

        // Mask API keys for security
        $maskKey = function($key) {
            if (!$key) return null;
            if (strlen($key) < 8) return str_repeat('*', strlen($key));
            return substr($key, 0, 4) . str_repeat('*', strlen($key) - 8) . substr($key, -4);
        };

        return [
            'success' => true,
            'data' => [
                'retell' => [
                    'api_key' => $maskKey($company->retell_api_key),
                    'agent_id' => $company->retell_agent_id,
                    'last_updated' => $company->retell_api_key_updated_at ?? null
                ],
                'calcom' => [
                    'api_key' => $maskKey($company->calcom_api_key),
                    'team_slug' => $company->calcom_team_slug,
                    'last_updated' => $company->calcom_api_key_updated_at ?? null
                ],
                'webhook' => [
                    'secret' => $maskKey($company->webhook_secret),
                    'url' => $company->webhook_url,
                    'last_updated' => $company->webhook_secret_updated_at ?? null
                ]
            ]
        ];
    }

    /**
     * Regenerate API key.
     */
    protected function regenerateAPIKey(array $params): array
    {
        $company = Company::find($params['company_id']);
        
        if (!$company) {
            throw new \Exception('Company not found');
        }

        $integration = $params['integration'];
        $newKey = null;

        switch ($integration) {
            case 'retell':
                // Note: Retell API keys must be obtained from Retell.ai dashboard
                throw new \Exception('Retell API keys must be regenerated in the Retell.ai dashboard');
                
            case 'calcom':
                // Note: Cal.com API keys must be obtained from Cal.com dashboard
                throw new \Exception('Cal.com API keys must be regenerated in the Cal.com dashboard');
                
            case 'webhook':
                // Generate new webhook secret
                $newKey = bin2hex(random_bytes(32));
                $company->webhook_secret = $newKey;
                $company->webhook_secret_updated_at = now();
                $company->save();
                break;
                
            default:
                throw new \Exception('Unknown integration');
        }

        return [
            'success' => true,
            'data' => [
                'integration' => $integration,
                'new_key' => $newKey,
                'message' => $integration === 'webhook' 
                    ? 'Webhook secret regenerated successfully' 
                    : 'Please regenerate the API key in the respective dashboard'
            ]
        ];
    }

    /**
     * Generate recovery codes for 2FA.
     */
    protected function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4))) . '-' . strtoupper(bin2hex(random_bytes(4)));
        }
        return $codes;
    }
}