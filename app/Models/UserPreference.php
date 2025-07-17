<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_type',
        'preference_key',
        'preference_value',
    ];

    protected $casts = [
        'preference_value' => 'array',
    ];

    /**
     * Get preference for a user
     */
    public static function getPreference($userId, $userType, $key, $default = null)
    {
        $preference = self::where('user_id', $userId)
            ->where('user_type', $userType)
            ->where('preference_key', $key)
            ->first();

        return $preference ? $preference->preference_value : $default;
    }

    /**
     * Set preference for a user
     */
    public static function setPreference($userId, $userType, $key, $value)
    {
        return self::updateOrCreate(
            [
                'user_id' => $userId,
                'user_type' => $userType,
                'preference_key' => $key,
            ],
            [
                'preference_value' => $value,
            ]
        );
    }

    /**
     * Default columns for calls table
     */
    public static function getDefaultCallsColumns()
    {
        return [
            'time_since' => ['label' => 'Zeit seit Anruf', 'visible' => true, 'order' => 1, 'width' => 'w-32'],
            'caller_info' => ['label' => 'Anrufer/Kunde', 'visible' => true, 'order' => 2, 'width' => 'w-48'],
            'reason' => ['label' => 'Anliegen', 'visible' => true, 'order' => 3, 'width' => 'w-64'],
            'urgency' => ['label' => 'Dringlichkeit', 'visible' => true, 'order' => 4, 'width' => 'w-32'],
            'status' => ['label' => 'Status', 'visible' => true, 'order' => 5, 'width' => 'w-36'],
            'assigned_to' => ['label' => 'Zugewiesen', 'visible' => true, 'order' => 6, 'width' => 'w-40'],
            'duration' => ['label' => 'Dauer', 'visible' => false, 'order' => 7, 'width' => 'w-24'],
            'costs' => ['label' => 'Kosten', 'visible' => false, 'order' => 8, 'width' => 'w-24'],
            'phone_number' => ['label' => 'Telefonnummer', 'visible' => false, 'order' => 9, 'width' => 'w-36'],
            'created_at' => ['label' => 'Datum/Zeit', 'visible' => false, 'order' => 10, 'width' => 'w-40'],
        ];
    }

    /**
     * Predefined view templates
     */
    public static function getViewTemplates()
    {
        return [
            'compact' => [
                'name' => 'Kompakt',
                'description' => 'Nur die wichtigsten Informationen',
                'columns' => ['time_since', 'caller_info', 'reason', 'status'],
            ],
            'standard' => [
                'name' => 'Standard',
                'description' => 'Ausgewogene Ansicht',
                'columns' => ['time_since', 'caller_info', 'reason', 'urgency', 'status', 'assigned_to'],
            ],
            'detailed' => [
                'name' => 'Detailliert',
                'description' => 'Alle verfügbaren Informationen',
                'columns' => ['time_since', 'caller_info', 'phone_number', 'reason', 'urgency', 'status', 'duration', 'costs', 'assigned_to', 'created_at'],
            ],
            'management' => [
                'name' => 'Management',
                'description' => 'Fokus auf Kosten und Performance',
                'columns' => ['time_since', 'caller_info', 'reason', 'status', 'duration', 'costs', 'assigned_to'],
            ],
        ];
    }
}