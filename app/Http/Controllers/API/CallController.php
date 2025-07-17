<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Call;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CallController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Alle Anrufe anzeigen.
     */
    public function index(): JsonResponse
    {
        $calls = Call::all();
        return response()->json(['data' => $calls]);
    }

    /**
     * Neuen Anruf anlegen.
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'call_id' => 'required|string',
            'customer_id' => 'nullable|exists:customers,id',
            'business_id' => 'required|exists:businesses,id',
            'zeitpunkt' => 'required|date',
            'dauer' => 'required|integer|min:0',
            'telefonnummer_anrufer' => 'required|string',
            'sentiment' => 'nullable|string',
            'status' => 'required|string',
            'zusammenfassung' => 'nullable|string',
            'transkript' => 'nullable|string',
        ]);

        $call = Call::create($validatedData);
        return response()->json(['message' => 'Anruf erfolgreich erstellt', 'data' => $call], 201);
    }

    /**
     * Bestimmten Anruf anzeigen.
     */
    public function show(Call $call): JsonResponse
    {
        return response()->json(['data' => $call]);
    }

    /**
     * Anrufdaten aktualisieren.
     */
    public function update(Request $request, Call $call): JsonResponse
    {
        $validatedData = $request->validate([
            'call_id' => 'sometimes|string',
            'customer_id' => 'nullable|exists:customers,id',
            'business_id' => 'sometimes|exists:businesses,id',
            'zeitpunkt' => 'sometimes|date',
            'dauer' => 'sometimes|integer|min:0',
            'telefonnummer_anrufer' => 'sometimes|string',
            'sentiment' => 'nullable|string',
            'status' => 'sometimes|string',
            'zusammenfassung' => 'nullable|string',
            'transkript' => 'nullable|string',
        ]);

        $call->update($validatedData);
        return response()->json(['message' => 'Anruf erfolgreich aktualisiert', 'data' => $call]);
    }

    /**
     * Anruf löschen.
     */
    public function destroy(Call $call): JsonResponse
    {
        $call->delete();
        return response()->json(['message' => 'Anruf erfolgreich gelöscht'], 200);
    }
    
    /**
     * Übersetze die Zusammenfassung eines Anrufs.
     */
    public function translateSummary(Request $request, Call $call): JsonResponse
    {
        $request->validate([
            'target_language' => 'required|string|size:2'
        ]);
        
        // Get the summary
        $summary = $call->webhook_data['call_analysis']['call_summary'] ?? 
                  $call->call_summary ?? 
                  $call->summary ?? 
                  null;
                  
        if (!$summary) {
            return response()->json([
                'error' => 'No summary available for translation'
            ], 404);
        }
        
        try {
            $translator = app(\App\Services\TranslationService::class);
            
            // Detect source language
            $sourceLanguage = $call->detected_language ?? $translator->detectLanguage($summary);
            $targetLanguage = $request->input('target_language');
            
            // If source and target are the same, return original
            if ($sourceLanguage === $targetLanguage) {
                return response()->json([
                    'original' => $summary,
                    'translated' => $summary,
                    'source_language' => $sourceLanguage,
                    'target_language' => $targetLanguage,
                    'needs_translation' => false
                ]);
            }
            
            // Perform translation
            $translated = $translator->translate($summary, $targetLanguage, $sourceLanguage);
            
            // Optionally save the detected language
            if (!$call->detected_language) {
                $call->detected_language = $sourceLanguage;
                $call->save();
            }
            
            return response()->json([
                'original' => $summary,
                'translated' => $translated,
                'source_language' => $sourceLanguage,
                'target_language' => $targetLanguage,
                'needs_translation' => true
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Translation failed for call ' . $call->id, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Translation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Exportiere Anrufdetails als PDF.
     */
    public function exportPdf(Call $call)
    {
        // Load relationships needed for the PDF
        $call->load(['customer', 'company', 'branch', 'appointment']);
        
        // Prepare data for the PDF
        $data = [
            'call' => $call,
            'customerName' => $call->customer?->name ?? $call->extracted_name ?? 'Unbekannter Anrufer',
            'companyName' => $call->company?->name ?? 'Unbekannte Firma',
            'branchName' => $call->branch?->name ?? '',
            'callDate' => $call->created_at->format('d.m.Y H:i'),
            'duration' => gmdate('i:s', $call->duration_sec ?? 0),
            'phoneNumber' => $call->phone_number ?? '',
            'summary' => $call->webhook_data['call_analysis']['call_summary'] ?? 
                        $call->call_summary ?? 
                        $call->summary ?? 
                        'Keine Zusammenfassung verfügbar',
            'sentiment' => $call->webhook_data['call_analysis']['user_sentiment'] ?? 
                          $call->mlPrediction?->sentiment_label ?? 
                          'neutral',
            'status' => $call->status ?? 'completed',
            'transcript' => $call->webhook_data['transcript'] ?? 
                           $call->transcript ?? 
                           'Kein Transkript verfügbar',
        ];
        
        // Calculate financial metrics if available
        if (class_exists(\App\Services\CallFinancialService::class)) {
            try {
                $financialService = app(\App\Services\CallFinancialService::class);
                $data['financials'] = $financialService->calculateMetrics($call);
            } catch (\Exception $e) {
                // If financial calculation fails, continue without it
                $data['financials'] = null;
            }
        }
        
        // Generate PDF using a view (we'll use a simple HTML structure)
        $html = view('pdf.call-details', $data)->render();
        
        // For now, return a simple PDF response using browser's print-to-PDF functionality
        // In production, you would use a proper PDF library like DomPDF or TCPDF
        
        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Disposition', 'inline; filename="anruf-' . $call->id . '.pdf"');
    }
}
