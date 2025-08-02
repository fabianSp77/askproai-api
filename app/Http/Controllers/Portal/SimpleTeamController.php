<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SimpleTeamController extends Controller
{
    /**
     * Display team members
     */
    public function index()
    {
        $teamMembers = [
            [
                'id' => 1,
                'name' => 'Dr. Schmidt',
                'email' => 'schmidt@example.com',
                'role' => 'Arzt',
                'branch' => 'Hauptfiliale',
                'status' => 'active',
                'created_at' => now()->subMonths(6)
            ],
            [
                'id' => 2,
                'name' => 'Fr. Müller',
                'email' => 'mueller@example.com',
                'role' => 'Empfang',
                'branch' => 'Hauptfiliale',
                'status' => 'active',
                'created_at' => now()->subMonths(4)
            ],
            [
                'id' => 3,
                'name' => 'Hr. Weber',
                'email' => 'weber@example.com',
                'role' => 'Therapeut',
                'branch' => 'Filiale Nord',
                'status' => 'active',
                'created_at' => now()->subMonths(2)
            ]
        ];
        
        return view('portal.team.simple-index', compact('teamMembers'));
    }
    
    /**
     * Show create form
     */
    public function create()
    {
        return view('portal.team.simple-create');
    }
    
    /**
     * Store new team member
     */
    public function store(Request $request)
    {
        return redirect()->route('business.team.index')
            ->with('success', 'Teammitglied wurde erfolgreich hinzugefügt!');
    }
    
    /**
     * Show edit form
     */
    public function edit($id)
    {
        $member = [
            'id' => $id,
            'name' => 'Dr. Schmidt',
            'email' => 'schmidt@example.com',
            'role' => 'Arzt',
            'branch_id' => 1
        ];
        
        return view('portal.team.simple-edit', compact('member'));
    }
    
    /**
     * Update team member
     */
    public function update(Request $request, $id)
    {
        return redirect()->route('business.team.index')
            ->with('success', 'Teammitglied wurde erfolgreich aktualisiert!');
    }
    
    /**
     * Delete team member
     */
    public function destroy($id)
    {
        return redirect()->route('business.team.index')
            ->with('success', 'Teammitglied wurde erfolgreich entfernt!');
    }
}