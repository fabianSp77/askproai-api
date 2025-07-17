<?php

namespace App\Helpers;

use Carbon\Carbon;

class PortalDisplayHelper
{
    /**
     * Format phone number for display
     */
    public static function formatPhoneNumber(?string $phone): string
    {
        if (!$phone) {
            return 'Unbekannt';
        }
        
        // Remove all non-digits
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Format German phone numbers
        if (preg_match('/^49(\d{3,4})(\d+)$/', $phone, $matches)) {
            $area = $matches[1];
            $number = $matches[2];
            
            // Split number into chunks
            $chunks = str_split($number, 3);
            $formatted = implode(' ', $chunks);
            
            return "+49 {$area} {$formatted}";
        }
        
        // Format other international numbers
        if (strlen($phone) > 10) {
            return '+' . substr($phone, 0, 2) . ' ' . substr($phone, 2);
        }
        
        // Default formatting
        return $phone;
    }
    
    /**
     * Format duration from seconds
     */
    public static function formatDuration(?int $seconds): string
    {
        if (!$seconds || $seconds < 0) {
            return '0:00';
        }
        
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        
        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
        }
        
        return sprintf('%d:%02d', $minutes, $secs);
    }
    
    /**
     * Format date/time for display
     */
    public static function formatDateTime($datetime, string $format = 'd.m.Y H:i'): string
    {
        if (!$datetime) {
            return '-';
        }
        
        try {
            return Carbon::parse($datetime)->format($format);
        } catch (\Exception $e) {
            return '-';
        }
    }
    
    /**
     * Format currency
     */
    public static function formatCurrency(?float $amount): string
    {
        if ($amount === null) {
            return '0,00 €';
        }
        
        return number_format($amount, 2, ',', '.') . ' €';
    }
    
    /**
     * Get status badge HTML
     */
    public static function getStatusBadge(string $status): string
    {
        $classes = match($status) {
            'completed' => 'bg-green-100 text-green-800',
            'in_progress' => 'bg-yellow-100 text-yellow-800',
            'failed' => 'bg-red-100 text-red-800',
            'scheduled' => 'bg-blue-100 text-blue-800',
            'confirmed' => 'bg-indigo-100 text-indigo-800',
            'cancelled' => 'bg-gray-100 text-gray-800',
            'new' => 'bg-purple-100 text-purple-800',
            'requires_action' => 'bg-orange-100 text-orange-800',
            'callback_scheduled' => 'bg-pink-100 text-pink-800',
            default => 'bg-gray-100 text-gray-700'
        };
        
        $label = match($status) {
            'completed' => 'Abgeschlossen',
            'in_progress' => 'In Bearbeitung',
            'failed' => 'Fehlgeschlagen',
            'scheduled' => 'Geplant',
            'confirmed' => 'Bestätigt',
            'cancelled' => 'Abgesagt',
            'new' => 'Neu',
            'requires_action' => 'Aktion erforderlich',
            'callback_scheduled' => 'Rückruf geplant',
            default => ucfirst($status)
        };
        
        return "<span class=\"inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {$classes}\">{$label}</span>";
    }
    
    /**
     * Format relative time
     */
    public static function formatRelativeTime($datetime): string
    {
        if (!$datetime) {
            return '-';
        }
        
        try {
            $carbon = Carbon::parse($datetime);
            
            // If more than 7 days ago, show date
            if ($carbon->diffInDays() > 7) {
                return $carbon->format('d.m.Y');
            }
            
            // Otherwise show relative time
            return $carbon->diffForHumans();
        } catch (\Exception $e) {
            return '-';
        }
    }
    
    /**
     * Get urgency level badge
     */
    public static function getUrgencyBadge(?string $urgency): string
    {
        if (!$urgency) {
            return '';
        }
        
        $classes = match(strtolower($urgency)) {
            'high', 'dringend' => 'bg-red-100 text-red-800',
            'medium', 'mittel' => 'bg-yellow-100 text-yellow-800',
            'low', 'niedrig' => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-700'
        };
        
        $label = match(strtolower($urgency)) {
            'high', 'dringend' => 'Dringend',
            'medium', 'mittel' => 'Mittel',
            'low', 'niedrig' => 'Niedrig',
            default => ucfirst($urgency)
        };
        
        return "<span class=\"inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {$classes}\">{$label}</span>";
    }
    
    /**
     * Format file size
     */
    public static function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Truncate text with ellipsis
     */
    public static function truncate(string $text, int $length = 100, string $suffix = '...'): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length - strlen($suffix)) . $suffix;
    }
    
    /**
     * Format percentage
     */
    public static function formatPercentage(?float $value, int $decimals = 1): string
    {
        if ($value === null) {
            return '-';
        }
        
        return number_format($value, $decimals, ',', '.') . '%';
    }
}