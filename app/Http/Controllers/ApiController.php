<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ApiController extends Controller
{
    public function createCall(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'call_id' => 'required|string|unique:calls',
            'call_time' => 'required|date',
            'call_duration' => 'required|string',
            'type' => 'required|string',
            'user_sentiment' => 'nullable|string',
            'successful' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $call = DB::table('calls')->insert([
            'call_id' => $request->call_id,
            'call_time' => $request->call_time,
            'call_duration' => $request->call_duration,
            'type' => $request->type,
            'cost' => $request->cost ?? 0,
            'call_status' => $request->call_status ?? 'ended',
            'user_sentiment' => $request->user_sentiment ?? 'Neutral',
            'successful' => $request->successful,
            'call_summary' => $request->call_summary,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Call created successfully'], 201);
    }

    public function getStats()
    {
        $stats = [
            'total_calls' => DB::table('calls')->count(),
            'successful_calls' => DB::table('calls')->where('successful', true)->count(),
            'avg_cost' => round(DB::table('calls')->avg('cost') ?? 0, 4),
            'sentiments' => DB::table('calls')
                ->select('user_sentiment', DB::raw('count(*) as count'))
                ->groupBy('user_sentiment')
                ->get(),
        ];

        return response()->json($stats);
    }
}
