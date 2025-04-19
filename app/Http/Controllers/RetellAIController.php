<?php

namespace App\Http\Controllers;

use App\Services\RetellAIService;
use Illuminate\Http\Request;

class RetellAIController extends Controller
{
    protected $retellService;

    public function __construct(RetellAIService $retellService)
    {
        $this->retellService = $retellService;
    }

    public function getCalls(Request $request)
    {
        $limit = $request->input('limit', 10);
        $calls = $this->retellService->getCalls($limit);
        
        return response()->json($calls);
    }

    public function getCallDetails($callId)
    {
        $callDetails = $this->retellService->getCallDetails($callId);
        
        return response()->json($callDetails);
    }

    public function getCallTranscript($callId)
    {
        $transcript = $this->retellService->getCallTranscript($callId);
        
        return response()->json($transcript);
    }
}
