<?php

namespace App\Http\Controllers\Portal\Api;

use Illuminate\Http\Request;
use App\Traits\UsesMCPServers;

class CustomersApiController extends BaseApiController
{
    use UsesMCPServers;

    public function __construct()
    {
        // Note: BaseApiController doesn't have a constructor, so no parent::__construct()
        $this->setMCPPreferences([
            'customer' => true,
            'company' => true,
            'database' => true
        ]);
    }
    public function index(Request $request)
    {
        $company = $this->getCompany();
        
        if (!$company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // List customers via MCP
        $result = $this->executeMCPTask('searchCustomers', [
            'company_id' => $company->id,
            'filters' => [
                'search' => $request->input('search'),
                'tag' => $request->input('tag'),
                'has_appointments' => $request->input('has_appointments')
            ],
            'sort_by' => $request->get('sort_by', 'created_at'),
            'sort_order' => $request->get('sort_order', 'desc'),
            'page' => $request->get('page', 1),
            'per_page' => $request->get('per_page', 25)
        ]);

        if (!($result['result']['success'] ?? false)) {
            return response()->json([
                'error' => $result['result']['message'] ?? 'Failed to fetch customers'
            ], 500);
        }

        return response()->json($result['result']['data']);
    }

    public function show(Request $request, $id)
    {
        $company = $this->getCompany();
        
        if (!$company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Get customer details via MCP
        $result = $this->executeMCPTask('getCustomerDetails', [
            'company_id' => $company->id,
            'customer_id' => $id,
            'include_activities' => true,
            'recent_activities_limit' => 5
        ]);

        if (!($result['result']['success'] ?? false)) {
            return response()->json([
                'error' => $result['result']['message'] ?? 'Customer not found'
            ], 404);
        }

        return response()->json($result['result']['data']);
    }

    public function store(Request $request)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check permission
        if ($user instanceof \App\Models\PortalUser && !$user->hasPermission('customers.create')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'company_name' => 'nullable|string|max:255',
            'address' => 'nullable|array',
            'tags' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        $company = $this->getCompany();
        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        // Create customer via MCP
        $result = $this->executeMCPTask('createCustomer', [
            'company_id' => $company->id,
            'customer_data' => $request->only([
                'name', 'phone', 'email', 'company_name',
                'address', 'tags', 'notes'
            ]),
            'source' => 'manual'
        ]);

        if (!($result['result']['success'] ?? false)) {
            return response()->json([
                'error' => $result['result']['message'] ?? 'Failed to create customer'
            ], 422);
        }

        return response()->json($result['result']['data'], 201);
    }

    public function update(Request $request, $id)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check permission
        if ($user instanceof \App\Models\PortalUser && !$user->hasPermission('customers.edit')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'company_name' => 'nullable|string|max:255',
            'address' => 'nullable|array',
            'tags' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        $company = $this->getCompany();
        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        // Update customer via MCP
        $result = $this->executeMCPTask('updateCustomer', [
            'company_id' => $company->id,
            'customer_id' => $id,
            'customer_data' => $request->only([
                'name', 'phone', 'email', 'company_name',
                'address', 'tags', 'notes'
            ])
        ]);

        if (!($result['result']['success'] ?? false)) {
            return response()->json([
                'error' => $result['result']['message'] ?? 'Failed to update customer'
            ], 422);
        }

        return response()->json($result['result']['data']);
    }

    public function destroy(Request $request, $id)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Only admins can delete customers
        if (!canDeleteBusinessData()) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Nur Administratoren können Kunden löschen'
            ], 403);
        }

        $company = $this->getCompany();
        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        // Delete customer via MCP
        $result = $this->executeMCPTask('deleteCustomer', [
            'company_id' => $company->id,
            'customer_id' => $id,
            'force' => false // Don't delete if has appointments/calls
        ]);

        if (!($result['result']['success'] ?? false)) {
            return response()->json([
                'error' => $result['result']['message'] ?? 'Failed to delete customer'
            ], 422);
        }

        return response()->json(['success' => true]);
    }

    public function exportCsv(Request $request)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check permission
        if ($user instanceof \App\Models\PortalUser && !$user->hasPermission('customers.export')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $company = $this->getCompany();
        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        // Export via MCP
        $result = $this->executeMCPTask('exportCustomers', [
            'company_id' => $company->id,
            'format' => 'csv',
            'filters' => [
                'search' => $request->input('search'),
                'tag' => $request->input('tag')
            ]
        ]);

        if (!($result['result']['success'] ?? false)) {
            return response()->json([
                'error' => $result['result']['message'] ?? 'Export failed'
            ], 500);
        }

        $export = $result['result']['export'];
        $filename = 'kunden_export_' . now()->format('Y-m-d_His') . '.csv';
        
        return response()->streamDownload(function () use ($export) {
            echo $export['content'];
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function tags(Request $request)
    {
        $company = $this->getCompany();
        
        if (!$company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Get tags via MCP
        $result = $this->executeMCPTask('getCustomerTags', [
            'company_id' => $company->id
        ]);

        if (!($result['result']['success'] ?? false)) {
            return response()->json(['tags' => []]);
        }

        return response()->json($result['result']['data']);
    }

    public function appointments(Request $request, $id)
    {
        $company = $this->getCompany();
        
        if (!$company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Get customer appointments via MCP
        $result = $this->executeMCPTask('getCustomerAppointments', [
            'company_id' => $company->id,
            'customer_id' => $id,
            'status' => $request->input('status', 'all'),
            'from_date' => $request->input('from'),
            'to_date' => $request->input('to'),
            'page' => $request->get('page', 1),
            'per_page' => $request->get('per_page', 25)
        ]);

        if (!($result['result']['success'] ?? false)) {
            return response()->json([
                'error' => $result['result']['message'] ?? 'Failed to fetch appointments'
            ], 500);
        }

        return response()->json($result['result']['data']);
    }

    public function invoices(Request $request, $id)
    {
        $company = $this->getCompany();
        
        if (!$company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Get customer invoices via MCP
        $result = $this->executeMCPTask('getCustomerInvoices', [
            'company_id' => $company->id,
            'customer_id' => $id,
            'status' => $request->input('status', 'all'),
            'from_date' => $request->input('from'),
            'to_date' => $request->input('to'),
            'page' => $request->get('page', 1),
            'per_page' => $request->get('per_page', 25)
        ]);

        if (!($result['result']['success'] ?? false)) {
            return response()->json([
                'error' => $result['result']['message'] ?? 'Failed to fetch invoices'
            ], 500);
        }

        return response()->json($result['result']['data']);
    }
}