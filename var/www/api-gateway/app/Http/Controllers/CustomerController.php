<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class CustomerController extends Controller
{
    public function index()
    {
        $customers = DB::table('customers')->get();
        return view('customers.index', compact('customers'));
    }
    
    public function create()
    {
        return view('customers.create');
    }
    
    public function store(Request $request)
    {
        $id = DB::table('customers')->insertGetId([
            'name' => $request->name,
            'phone_number' => $request->phone_number,
            'email' => $request->email,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        return redirect()->route('customers.show', $id)
            ->with('success', 'Kunde erfolgreich erstellt');
    }
    
    public function show($id)
    {
        $customer = DB::table('customers')->find($id);
        
        // Verwende phone_number statt customer_id für die Verknüpfung
        $customerPhone = DB::table('customers')->where('id', $id)->value('phone_number');
        
        $calls = DB::table('calls')
            ->where('phone_number', $customerPhone)
            ->orderBy('call_time', 'desc')
            ->get();
            
        return view('customers.show', compact('customer', 'calls'));
    }
}
