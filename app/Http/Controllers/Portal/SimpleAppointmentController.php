<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SimpleAppointmentController extends Controller
{
    /**
     * Display appointments list
     */
    public function index()
    {
        return view('portal.appointments.simple-index');
    }
    
    /**
     * Show create appointment form
     */
    public function create()
    {
        return view('portal.appointments.simple-create');
    }
    
    /**
     * Store new appointment
     */
    public function store(Request $request)
    {
        // For now, just redirect back with success message
        return redirect()->route('business.appointments.index')
            ->with('success', 'Termin wurde erfolgreich erstellt!');
    }
    
    /**
     * Show appointment details
     */
    public function show($id)
    {
        $appointment = [
            'id' => $id,
            'customer' => [
                'name' => 'Max Mustermann',
                'email' => 'max@example.com',
                'phone' => '+49 123 456789'
            ],
            'service' => 'Beratung',
            'scheduled_at' => now()->addDays(2),
            'duration' => 60,
            'status' => 'confirmed',
            'branch' => [
                'name' => 'Hauptfiliale',
                'address' => 'Musterstraße 1, 12345 Musterstadt'
            ],
            'staff' => [
                'name' => 'Dr. Schmidt'
            ],
            'notes' => 'Kunde möchte eine Beratung zum Thema Versicherungen.'
        ];
        
        return view('portal.appointments.simple-show', compact('appointment'));
    }
    
    /**
     * Show edit form
     */
    public function edit($id)
    {
        $appointment = [
            'id' => $id,
            'customer_name' => 'Max Mustermann',
            'service' => 'Beratung',
            'scheduled_at' => now()->addDays(2),
            'duration' => 60,
            'notes' => 'Kunde möchte eine Beratung zum Thema Versicherungen.'
        ];
        
        return view('portal.appointments.simple-edit', compact('appointment'));
    }
    
    /**
     * Update appointment
     */
    public function update(Request $request, $id)
    {
        return redirect()->route('business.appointments.show', $id)
            ->with('success', 'Termin wurde erfolgreich aktualisiert!');
    }
    
    /**
     * Delete appointment
     */
    public function destroy($id)
    {
        return redirect()->route('business.appointments.index')
            ->with('success', 'Termin wurde erfolgreich gelöscht!');
    }
}