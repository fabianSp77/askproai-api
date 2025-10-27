<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Dummy model for Filament Resource
 * Data comes from JSON files, not database
 */
class ConversationFlow extends Model
{
    // No table needed - data comes from JSON
    protected $table = null;

    // Disable timestamps
    public $timestamps = false;

    // Disable database operations
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'total_nodes',
        'total_transitions',
        'model',
        'status',
    ];

    /**
     * Get all conversation flows from JSON
     */
    public static function all($columns = ['*'])
    {
        $nodeGraphPath = 'conversation_flow/graphs/node_graph.json';

        if (!Storage::disk('local')->exists($nodeGraphPath)) {
            return new \Illuminate\Database\Eloquent\Collection([]);
        }

        $nodeGraph = json_decode(Storage::disk('local')->get($nodeGraphPath), true);

        $flow = new static([
            'id' => 1,
            'name' => 'AskPro AI Appointment Booking Flow',
            'total_nodes' => $nodeGraph['total_nodes'] ?? 0,
            'total_transitions' => $nodeGraph['total_transitions'] ?? 0,
            'model' => 'gpt-4o-mini',
            'status' => 'generated',
        ]);

        $flow->exists = true;

        return new \Illuminate\Database\Eloquent\Collection([$flow]);
    }
}
