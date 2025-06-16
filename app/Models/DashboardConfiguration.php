<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'widget_settings',
        'layout_settings',
    ];

    protected $casts = [
        'widget_settings' => 'array',
        'layout_settings' => 'array',
    ];

    protected $attributes = [
        'widget_settings' => '{}',
        'layout_settings' => '{}',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getDefaultWidgetSettings(): array
    {
        return [
            'OnboardingProgressWidget' => ['enabled' => true, 'order' => 0, 'columnSpan' => 'full'],
            'DashboardStats' => ['enabled' => true, 'order' => 1, 'columnSpan' => 'full'],
            'TodayOverviewWidget' => ['enabled' => true, 'order' => 2, 'columnSpan' => 'full'],
            'RealtimeCallWidget' => ['enabled' => true, 'order' => 3, 'columnSpan' => 'full'],
            'AppointmentTimelineWidget' => ['enabled' => true, 'order' => 4, 'columnSpan' => 'full'],
            'PerformanceMetricsWidget' => ['enabled' => true, 'order' => 5, 'columnSpan' => 'full'],
            'QuickActionsWidget' => ['enabled' => true, 'order' => 6, 'columnSpan' => 'full'],
            'RecentAppointments' => ['enabled' => true, 'order' => 7, 'columnSpan' => 2],
            'RecentCalls' => ['enabled' => true, 'order' => 8, 'columnSpan' => 1],
            'SystemStatus' => ['enabled' => false, 'order' => 9, 'columnSpan' => 1],
            'SystemStatusEnhanced' => ['enabled' => true, 'order' => 10, 'columnSpan' => 1],
        ];
    }

    public static function getDefaultLayoutSettings(): array
    {
        return [
            'columns' => 3,
            'spacing' => 'normal',
            'theme' => 'default',
        ];
    }
}