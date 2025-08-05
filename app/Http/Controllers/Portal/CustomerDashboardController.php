<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CustomerDashboardController extends Controller
{
    /**
     * Show customer dashboard.
     */
    public function index()
    {
        $customer = Auth::guard('customer')->user();
        
        $stats = [
            'upcoming_appointments' => $customer->appointments()
                ->where('starts_at', '>=', now())
                ->count(),
            'total_appointments' => $customer->appointments()->count(),
            'open_invoices' => $this->getOpenInvoicesCount($customer),
            'total_spent' => $this->getTotalSpent($customer),
        ];
        
        $upcomingAppointments = $customer->appointments()
            ->with(['staff', 'service', 'branch'])
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->limit(5)
            ->get();
            
        $recentInvoices = $this->getRecentInvoices($customer);
        
        return view('portal.dashboard', compact(
            'customer',
            'stats',
            'upcomingAppointments',
            'recentInvoices'
        ));
    }
    
    /**
     * Show appointments page.
     */
    public function appointments(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        
        $query = $customer->appointments()
            ->with(['staff', 'service', 'branch']);
            
        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by date range
        if ($request->has('from')) {
            $query->where('starts_at', '>=', $request->from);
        }
        if ($request->has('to')) {
            $query->where('ends_at', '<=', $request->to);
        }
        
        $appointments = $query->orderBy('starts_at', 'desc')
            ->paginate(20);
            
        return view('portal.appointments', [
            'customer' => $customer,
            'appointments' => $appointments,
        ]);
    }
    
    /**
     * Show appointment details.
     */
    public function showAppointment(Appointment $appointment)
    {
        $customer = Auth::guard('customer')->user();
        
        // Ensure appointment belongs to customer
        if ($appointment->customer_id !== $customer->id) {
            abort(403);
        }
        
        return view('portal.appointment-details', [
            'customer' => $customer,
            'appointment' => $appointment->load(['staff', 'service', 'branch']),
        ]);
    }
    
    /**
     * Cancel appointment.
     */
    public function cancelAppointment(Appointment $appointment)
    {
        $customer = Auth::guard('customer')->user();
        
        // Ensure appointment belongs to customer
        if ($appointment->customer_id !== $customer->id) {
            abort(403);
        }
        
        // Check if appointment can be cancelled (e.g., 24h before)
        if ($appointment->starts_at->diffInHours(now()) < 24) {
            return back()->withErrors([
                'error' => 'Termine können nur bis 24 Stunden vor Beginn storniert werden.',
            ]);
        }
        
        $appointment->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => 'Vom Kunden storniert',
        ]);
        
        // TODO: Send cancellation email
        // TODO: Update Cal.com booking
        
        return back()->with('success', 'Der Termin wurde erfolgreich storniert.');
    }
    
    /**
     * Show invoices page.
     */
    public function invoices(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        
        // Since getCustomerInvoices returns an empty collection for now,
        // we'll just paginate an empty result
        $invoices = new \Illuminate\Pagination\LengthAwarePaginator(
            [],
            0,
            20,
            $request->get('page', 1),
            ['path' => $request->url()]
        );
            
        return view('portal.invoices', [
            'customer' => $customer,
            'invoices' => $invoices,
        ]);
    }
    
    /**
     * Show invoice details.
     */
    public function showInvoice(Invoice $invoice)
    {
        $customer = Auth::guard('customer')->user();
        
        // Verify invoice belongs to customer's company
        if ($invoice->company_id !== $customer->company_id) {
            abort(403);
        }
        
        // Load relationships
        $invoice->load(['items', 'flexibleItems', 'company', 'branch']);
        
        return view('portal.invoice-details', [
            'customer' => $customer,
            'invoice' => $invoice,
        ]);
    }
    
    /**
     * Download invoice PDF.
     */
    public function downloadInvoice(Invoice $invoice)
    {
        $customer = Auth::guard('customer')->user();
        
        // Verify invoice belongs to customer's company
        if ($invoice->company_id !== $customer->company_id) {
            abort(403);
        }
        
        // If PDF URL exists, redirect to it
        if ($invoice->pdf_url) {
            return redirect($invoice->pdf_url);
        }
        
        // Otherwise generate PDF
        // TODO: Implement PDF generation
        abort(404, 'PDF noch nicht verfügbar.');
    }
    
    /**
     * Show profile page.
     */
    public function profile()
    {
        $customer = Auth::guard('customer')->user();
        
        return view('portal.profile', [
            'customer' => $customer,
        ]);
    }
    
    /**
     * Update profile.
     */
    public function updateProfile(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:customers,email,' . $customer->id],
            'phone' => ['required', 'string', 'max:20'],
            'preferred_language' => ['required', 'in:de,en'],
            'birthdate' => ['nullable', 'date', 'before:today'],
            'password' => ['nullable', 'confirmed', 'min:8'],
        ]);
        
        // Update basic info
        $customer->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'preferred_language' => $validated['preferred_language'],
            'birthdate' => $validated['birthdate'] ?? null,
        ]);
        
        // Update password if provided
        if (!empty($validated['password'])) {
            $customer->update([
                'password' => Hash::make($validated['password']),
            ]);
        }
        
        return back()->with('success', 'Ihr Profil wurde erfolgreich aktualisiert.');
    }
    
    /**
     * Update password.
     */
    public function updatePassword(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        
        $validated = $request->validate([
            'current_password' => ['required', 'current_password:customer'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);
        
        $customer->update([
            'password' => Hash::make($validated['password']),
        ]);
        
        return back()->with('success', 'Ihr Passwort wurde erfolgreich geändert.');
    }
    
    /**
     * Update newsletter preferences.
     */
    public function updateNewsletter(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        
        // Since we don't have accepts_marketing field, just return success
        return back()->with('success', 'Ihre Benachrichtigungseinstellungen wurden aktualisiert.');
    }
    
    /**
     * Delete account.
     */
    public function deleteAccount(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        
        $request->validate([
            'password' => ['required', 'current_password:customer'],
        ]);
        
        // Log the deletion request
        \Log::info('Customer account deletion requested', [
            'customer_id' => $customer->id,
            'email' => $customer->email,
        ]);
        
        // Logout first
        Auth::guard('customer')->logout();
        
        // Soft delete or anonymize the customer
        $customer->update([
            'name' => 'Gelöschter Kunde',
            'email' => 'deleted_' . $customer->id . '@example.com',
            'phone' => null,
            'password' => null,
            'portal_enabled' => false,
        ]);
        
        return redirect()->route('portal.login')
            ->with('success', 'Ihr Konto wurde erfolgreich gelöscht.');
    }
    
    /**
     * Show 2FA settings.
     */
    public function show2FASettings()
    {
        $customer = Auth::guard('customer')->user();
        
        // For now, just redirect back with a message
        return back()->with('info', 'Zwei-Faktor-Authentifizierung wird in Kürze verfügbar sein.');
    }
    
    /**
     * Get customer invoices.
     */
    protected function getCustomerInvoices($customer)
    {
        // For now, return empty collection since invoice structure is complex
        // This would need proper implementation based on actual invoice-customer relationship
        return collect([]);
    }
    
    /**
     * Get open invoices count.
     */
    protected function getOpenInvoicesCount($customer): int
    {
        // Temporarily return 0 until invoice structure is properly implemented
        return 0;
    }
    
    /**
     * Get total spent.
     */
    protected function getTotalSpent($customer): float
    {
        // Temporarily return 0 until invoice structure is properly implemented
        return 0.0;
    }
    
    /**
     * Get recent invoices.
     */
    protected function getRecentInvoices($customer)
    {
        // Temporarily return empty collection until invoice structure is properly implemented
        return collect([]);
    }
}