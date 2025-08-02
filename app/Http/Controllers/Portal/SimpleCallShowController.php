<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Call;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SimpleCallShowController extends Controller
{
    /**
     * Display a specific call - simplified version
     */
    public function show($id)
    {
        // For testing, just return a simple view with mock data
        $call = [
            'id' => $id,
            'phone_number' => '+49 123 456789',
            'status' => 'completed',
            'duration_sec' => 180,
            'created_at' => now()->subHours(2),
            'transcript' => "Agent: Guten Tag, AskProAI, wie kann ich Ihnen helfen?\n\nKunde: Hallo, ich möchte gerne einen Termin vereinbaren.\n\nAgent: Gerne! Für welchen Service möchten Sie einen Termin?\n\nKunde: Ich brauche einen Termin für eine Beratung.\n\nAgent: Perfekt. Wann würde es Ihnen denn passen?",
            'summary' => 'Kunde möchte einen Beratungstermin vereinbaren.',
            'customer' => [
                'id' => 1,
                'name' => 'Max Mustermann',
                'email' => 'max@example.com',
                'phone' => '+49 123 456789'
            ],
            'appointment' => [
                'id' => 1,
                'scheduled_at' => now()->addDays(3),
                'service' => 'Beratung',
                'duration' => 60
            ]
        ];
        
        return view('portal.calls.simple-show', compact('call'));
    }
}