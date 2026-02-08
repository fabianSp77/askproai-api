<?php

/**
 * Billing Configuration
 *
 * Central configuration for AskProAI company data and billing settings.
 * Used in: Partner invoices (Stripe), Email templates, Legal compliance (§14 UStG)
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Pricing
    |--------------------------------------------------------------------------
    */

    'price_per_second_cents' => 3,  // 3 Cent pro Sekunde

    /*
    |--------------------------------------------------------------------------
    | Company Information (§14 UStG)
    |--------------------------------------------------------------------------
    |
    | Legal company details required for invoices.
    | Displayed in invoice footers and email templates.
    |
    */

    'company' => [
        'name' => 'AskProAI UG (haftungsbeschränkt)',
        'owner' => 'Fabian Spitzer',

        'address' => [
            'street' => 'George-Stephenson-Straße 12',
            'postal_code' => '10557',
            'city' => 'Berlin',
            'country' => 'DE',
        ],

        'contact' => [
            'phone' => '+49 160 4366218',
            'email' => 'fabian@askproai.de',
            'website' => 'www.askproai.de',
        ],

        /*
        |--------------------------------------------------------------------------
        | Tax Information
        |--------------------------------------------------------------------------
        |
        | Per §14 UStG: Steuernummer OR USt-IdNr required on invoices.
        | USt-IdNr needed for EU B2B transactions and Stripe Tax ID.
        |
        */

        'tax_number' => '34/540/01295',
        'vat_id' => env('BILLING_VAT_ID', ''),  // USt-IdNr (DE...) - beim Finanzamt beantragen

        /*
        |--------------------------------------------------------------------------
        | Trade Register (Handelsregister)
        |--------------------------------------------------------------------------
        |
        | Required for UG/GmbH/AG per §14 UStG.
        |
        */

        'trade_register' => env('BILLING_TRADE_REGISTER', ''),
        'trade_register_court' => env('BILLING_TRADE_REGISTER_COURT', ''),

        /*
        |--------------------------------------------------------------------------
        | Bank Information
        |--------------------------------------------------------------------------
        */

        'bank' => [
            'name' => env('BILLING_BANK_NAME', ''),
            'iban' => env('BILLING_IBAN', ''),
            'bic' => env('BILLING_BIC', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Invoice Defaults
    |--------------------------------------------------------------------------
    */

    'invoice' => [
        'default_tax_rate' => 19.00,
        'currency' => 'EUR',
        'payment_terms_days' => 14,
    ],

];
