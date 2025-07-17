<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\StripeTopupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class StripePaymentLinkController extends Controller
{
    protected StripeTopupService $stripeService;

    public function __construct(StripeTopupService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Generate a payment link for a company
     */
    public function generatePaymentLink(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'amount' => 'nullable|numeric|min:10|max:5000',
            'metadata' => 'nullable|array',
        ]);
        
        // Get company
        $company = Company::findOrFail($request->company_id);
        
        // Check permission
        if ($user->company_id !== $company->id && !$user->isAdmin()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        
        try {
            // Generate payment link
            $paymentLinkUrl = $this->stripeService->createPaymentLink(
                $company,
                $request->amount,
                $request->metadata ?? []
            );
            
            if (!$paymentLinkUrl) {
                throw new \Exception('Failed to create payment link');
            }
            
            return response()->json([
                'success' => true,
                'payment_link' => $paymentLinkUrl,
                'company' => [
                    'id' => $company->id,
                    'name' => $company->name,
                ],
                'amount' => $request->amount,
                'message' => 'Payment link erfolgreich erstellt',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Payment link generation failed', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'error' => 'Fehler beim Erstellen des Payment Links: ' . $e->getMessage(),
            ], 400);
        }
    }
    
    /**
     * Get existing payment link for a company
     */
    public function getPaymentLink(Request $request, $companyId)
    {
        $user = Auth::guard('portal')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Get company
        $company = Company::findOrFail($companyId);
        
        // Check permission
        if ($user->company_id !== $company->id && !$user->isAdmin()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        
        // Get payment link from metadata
        $metadata = $company->metadata ?? [];
        $paymentLinkUrl = $metadata['stripe_payment_link_url'] ?? null;
        $paymentLinkId = $metadata['stripe_payment_link_id'] ?? null;
        $createdAt = $metadata['stripe_payment_link_created_at'] ?? null;
        
        if (!$paymentLinkUrl) {
            return response()->json([
                'exists' => false,
                'message' => 'Kein Payment Link vorhanden',
            ]);
        }
        
        return response()->json([
            'exists' => true,
            'payment_link' => $paymentLinkUrl,
            'payment_link_id' => $paymentLinkId,
            'created_at' => $createdAt,
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
            ],
        ]);
    }
    
    /**
     * Generate QR code for payment link
     */
    public function generateQRCode(Request $request, $companyId)
    {
        $user = Auth::guard('portal')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Get company
        $company = Company::findOrFail($companyId);
        
        // Check permission
        if ($user->company_id !== $company->id && !$user->isAdmin()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        
        // Get payment link from metadata
        $metadata = $company->metadata ?? [];
        $paymentLinkUrl = $metadata['stripe_payment_link_url'] ?? null;
        
        if (!$paymentLinkUrl) {
            return response()->json([
                'error' => 'Kein Payment Link vorhanden. Bitte erst einen Payment Link erstellen.',
            ], 404);
        }
        
        try {
            // Generate QR code using a QR code library or API
            // For now, we'll use a simple web service
            $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?' . http_build_query([
                'size' => '300x300',
                'data' => $paymentLinkUrl,
                'format' => 'png',
            ]);
            
            return response()->json([
                'success' => true,
                'qr_code_url' => $qrCodeUrl,
                'payment_link' => $paymentLinkUrl,
                'company' => [
                    'id' => $company->id,
                    'name' => $company->name,
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error('QR code generation failed', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'error' => 'Fehler beim Erstellen des QR-Codes',
            ], 500);
        }
    }
}