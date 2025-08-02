<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SimpleCustomerController extends Controller
{
    /**
     * Display customers list
     */
    public function index()
    {
        $customers = [
            [
                'id' => 1,
                'name' => 'Max Mustermann',
                'email' => 'max@example.com',
                'phone' => '+49 123 456789',
                'appointments_count' => 5,
                'last_appointment' => now()->subDays(7),
                'created_at' => now()->subMonths(3)
            ],
            [
                'id' => 2,
                'name' => 'Erika Musterfrau',
                'email' => 'erika@example.com',
                'phone' => '+49 987 654321',
                'appointments_count' => 3,
                'last_appointment' => now()->subDays(14),
                'created_at' => now()->subMonths(2)
            ],
            [
                'id' => 3,
                'name' => 'Thomas Schmidt',
                'email' => 'thomas@example.com',
                'phone' => '+49 555 123456',
                'appointments_count' => 8,
                'last_appointment' => now()->subDays(2),
                'created_at' => now()->subMonths(6)
            ]
        ];
        
        return view('portal.customers.simple-index', compact('customers'));
    }
    
    /**
     * Show customer details
     */
    public function show($id)
    {
        $customer = [
            'id' => $id,
            'name' => 'Max Mustermann',
            'email' => 'max@example.com',
            'phone' => '+49 123 456789',
            'address' => 'Musterstraße 123, 12345 Musterstadt',
            'notes' => 'Bevorzugt Termine am Nachmittag',
            'created_at' => now()->subMonths(3),
            'appointments' => [
                [
                    'id' => 1,
                    'service' => 'Beratung',
                    'scheduled_at' => now()->subDays(7),
                    'status' => 'completed'
                ],
                [
                    'id' => 2,
                    'service' => 'Nachuntersuchung',
                    'scheduled_at' => now()->addDays(3),
                    'status' => 'confirmed'
                ]
            ]
        ];
        
        return view('portal.customers.simple-show', compact('customer'));
    }
}