<?php

/**
 * Filament Admin Panel - English Translations
 *
 * This file contains all English translations for the Filament admin interface.
 * Usage: __('filament.labels.created_by')
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Common Labels
    |--------------------------------------------------------------------------
    */
    'labels' => [
        'created_by' => 'Created By',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
        'deleted_at' => 'Deleted At',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'status' => 'Status',
        'status_code' => 'Status Code',
        'details' => 'Details',
        'settings' => 'Settings',
        'actions' => 'Actions',
        'name' => 'Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'address' => 'Address',
    ],

    /*
    |--------------------------------------------------------------------------
    | Action Labels
    |--------------------------------------------------------------------------
    */
    'actions' => [
        'create' => 'Create',
        'edit' => 'Edit',
        'view' => 'View',
        'delete' => 'Delete',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'confirm' => 'Confirm',
        'back' => 'Back',
        'next' => 'Next',
        'finish' => 'Finish',
        'export' => 'Export',
        'import' => 'Import',
    ],

    /*
    |--------------------------------------------------------------------------
    | Status Messages
    |--------------------------------------------------------------------------
    */
    'messages' => [
        'success' => 'Success',
        'error' => 'Error',
        'warning' => 'Warning',
        'info' => 'Info',
        'created' => 'Successfully created',
        'updated' => 'Successfully updated',
        'deleted' => 'Successfully deleted',
        'saved' => 'Successfully saved',
    ],

    /*
    |--------------------------------------------------------------------------
    | Filter Labels
    |--------------------------------------------------------------------------
    */
    'filters' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'all' => 'All',
        'active_only' => 'Active Only',
        'inactive_only' => 'Inactive Only',
        'active_label' => 'Active',
        'inactive_label' => 'Inactive',
    ],

    /*
    |--------------------------------------------------------------------------
    | Policy System
    |--------------------------------------------------------------------------
    */
    'policy' => [
        'onboarding' => [
            'title' => 'Policy Onboarding Wizard',
            'welcome' => 'Welcome to the Policy Setup Wizard!',
            'step_welcome' => 'Welcome',
            'step_entity' => 'Select Entity',
            'step_rules' => 'Configure Rules',
            'step_complete' => 'Complete',
            'entity_type' => 'Entity Type',
            'policy_type' => 'Policy Type',
            'hours_before' => 'Hours Before',
            'fee_type' => 'Fee Type',
            'fee_amount' => 'Fee Amount',
            'enable_quota' => 'Enable Monthly Limit',
            'max_per_month' => 'Max Per Month',
        ],
        'types' => [
            'cancellation' => 'Cancellation',
            'reschedule' => 'Reschedule',
        ],
        'entities' => [
            'company' => 'Company',
            'branch' => 'Branch',
            'service' => 'Service',
            'staff' => 'Staff',
        ],
        'fee_types' => [
            'none' => 'No Fee',
            'percentage' => 'Percentage',
            'fixed' => 'Fixed Amount',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'event_types' => [
            'appointment_created' => 'Appointment Created',
            'appointment_updated' => 'Appointment Updated',
            'appointment_cancelled' => 'Appointment Cancelled',
            'appointment_reminder' => 'Appointment Reminder',
        ],
        'channels' => [
            'email' => 'Email',
            'sms' => 'SMS',
            'whatsapp' => 'WhatsApp',
            'push' => 'Push Notification',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Callbacks
    |--------------------------------------------------------------------------
    */
    'callbacks' => [
        'status' => [
            'pending' => 'Pending',
            'assigned' => 'Assigned',
            'contacted' => 'Contacted',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ],
        'priority' => [
            'low' => 'Low',
            'normal' => 'Normal',
            'high' => 'High',
            'urgent' => 'Urgent',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Appointments
    |--------------------------------------------------------------------------
    */
    'appointments' => [
        'status' => [
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'no_show' => 'No Show',
        ],
    ],
];
