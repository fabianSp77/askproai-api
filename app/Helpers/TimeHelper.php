<?php

namespace App\Helpers;

use Carbon\Carbon;

class TimeHelper
{
    /**
     * Format time elapsed since a given date
     * Shows hours:minutes for < 24h, otherwise days + hours
     */
    public static function formatTimeSince($dateTime, $includeTooltip = true)
    {
        if (!$dateTime) {
            return '-';
        }

        $date = $dateTime instanceof Carbon ? $dateTime : Carbon::parse($dateTime);
        $now = Carbon::now();
        $diff = $date->diff($now);
        
        // Calculate total hours
        $totalHours = ($diff->days * 24) + $diff->h;
        
        // Format display text
        if ($totalHours < 24) {
            // Show hours and minutes
            $displayText = sprintf('%dh %dm', $diff->h, $diff->i);
        } else {
            // Show days and hours
            $displayText = sprintf('%dd %dh', $diff->days, $diff->h);
        }
        
        // Add "vor" (ago) suffix
        $displayText = $displayText . ' her';
        
        if ($includeTooltip) {
            $tooltipText = $date->format('d.m.Y H:i:s');
            return sprintf(
                '<span class="cursor-help" title="%s">%s</span>',
                htmlspecialchars($tooltipText),
                htmlspecialchars($displayText)
            );
        }
        
        return $displayText;
    }
    
    /**
     * Get CSS class based on age
     */
    public static function getAgeClass($dateTime)
    {
        if (!$dateTime) {
            return '';
        }
        
        $date = $dateTime instanceof Carbon ? $dateTime : Carbon::parse($dateTime);
        $hoursAgo = $date->diffInHours();
        
        if ($hoursAgo < 1) {
            return 'text-green-600'; // Fresh
        } elseif ($hoursAgo < 4) {
            return 'text-blue-600'; // Recent
        } elseif ($hoursAgo < 24) {
            return 'text-gray-600'; // Today
        } elseif ($hoursAgo < 48) {
            return 'text-yellow-600'; // Yesterday
        } else {
            return 'text-red-600'; // Old
        }
    }
}