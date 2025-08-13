<?php

namespace App\Helpers;

/**
 * Portal display helper functions
 */
class PortalDisplayHelper
{
    /**
     * Format currency for display
     *
     * @param float $amount
     * @param string $currency
     * @return string
     */
    public static function formatCurrency(float $amount, string $currency = 'EUR'): string
    {
        return number_format($amount, 2, ',', '.') . ' €';
    }

    /**
     * Format date for display
     *
     * @param mixed $date
     * @param string $format
     * @return string
     */
    public static function formatDate($date, string $format = 'd.m.Y H:i'): string
    {
        if (!$date) {
            return '-';
        }

        if (is_string($date)) {
            $date = new \DateTime($date);
        }

        return $date->format($format);
    }

    /**
     * Get status badge HTML
     *
     * @param string $status
     * @return string
     */
    public static function getStatusBadge(string $status): string
    {
        $classes = match($status) {
            'active', 'confirmed' => 'bg-green-100 text-green-800',
            'pending' => 'bg-yellow-100 text-yellow-800',
            'cancelled', 'failed' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };

        return sprintf(
            '<span class="px-2 py-1 text-xs font-medium rounded-full %s">%s</span>',
            $classes,
            ucfirst($status)
        );
    }
}