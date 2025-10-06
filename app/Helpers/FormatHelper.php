<?php

namespace App\Helpers;

/**
 * Helper-Klasse für konsistente Formatierung in der gesamten Anwendung
 */
class FormatHelper
{
    /**
     * Formatiert Cent-Beträge als Euro mit deutschem Format
     *
     * @param int|float|null $cents Betrag in Cents
     * @param bool $withSymbol Ob das €-Symbol angezeigt werden soll
     * @return string Formatierter Betrag
     */
    public static function currency($cents, bool $withSymbol = true): string
    {
        if ($cents === null || $cents === 0) {
            return $withSymbol ? '0,00 €' : '0,00';
        }

        $euros = $cents / 100;
        $formatted = number_format($euros, 2, ',', '.');

        return $withSymbol ? $formatted . ' €' : $formatted;
    }

    /**
     * Formatiert Dauer in Sekunden als lesbare Zeit
     *
     * @param int|null $seconds Dauer in Sekunden
     * @param string $format Format: 'short' (mm:ss), 'long' (Xh Xm Xs), 'human' (X Min.)
     * @return string Formatierte Dauer
     */
    public static function duration(?int $seconds, string $format = 'short'): string
    {
        if (!$seconds || $seconds === 0) {
            return match ($format) {
                'short' => '00:00',
                'long' => '0s',
                'human' => '0 Min.',
                default => '-'
            };
        }

        return match ($format) {
            'short' => gmdate("i:s", $seconds),
            'long' => self::formatLongDuration($seconds),
            'human' => self::formatHumanDuration($seconds),
            default => gmdate("i:s", $seconds)
        };
    }

    /**
     * Formatiert Dauer als "Xh Xm Xs"
     */
    private static function formatLongDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}m {$secs}s";
        } elseif ($minutes > 0) {
            return "{$minutes}m {$secs}s";
        } else {
            return "{$secs}s";
        }
    }

    /**
     * Formatiert Dauer als "X Min." oder "X Std. X Min."
     */
    private static function formatHumanDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($hours > 0) {
            return "{$hours} Std. {$minutes} Min.";
        } else {
            $minutes = ceil($seconds / 60);
            return "{$minutes} Min.";
        }
    }

    /**
     * Formatiert Datum mit deutschem Format
     *
     * @param \DateTime|string|null $date Datum
     * @param string $format Format-String
     * @return string Formatiertes Datum
     */
    public static function date($date, string $format = 'd.m.Y'): string
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
     * Formatiert Datum und Zeit mit deutschem Format
     *
     * @param \DateTime|string|null $datetime Datum/Zeit
     * @param bool $withSeconds Mit Sekunden anzeigen
     * @return string Formatierte Datum/Zeit
     */
    public static function datetime($datetime, bool $withSeconds = false): string
    {
        if (!$datetime) {
            return '-';
        }

        if (is_string($datetime)) {
            $datetime = new \DateTime($datetime);
        }

        $format = $withSeconds ? 'd.m.Y H:i:s' : 'd.m.Y H:i';
        return $datetime->format($format);
    }

    /**
     * Formatiert Prozentangaben
     *
     * @param float|null $value Wert zwischen 0 und 100
     * @param int $decimals Anzahl Nachkommastellen
     * @return string Formatierte Prozentangabe
     */
    public static function percentage(?float $value, int $decimals = 1): string
    {
        if ($value === null) {
            return '0,0 %';
        }

        return number_format($value, $decimals, ',', '.') . ' %';
    }

    /**
     * Formatiert Telefonnummern für bessere Lesbarkeit
     *
     * @param string|null $number Telefonnummer
     * @return string Formatierte Nummer
     */
    public static function phoneNumber(?string $number): string
    {
        if (!$number) {
            return '-';
        }

        // Entferne alle Nicht-Ziffern außer +
        $cleaned = preg_replace('/[^0-9+]/', '', $number);

        // Deutsche Mobilnummer
        if (preg_match('/^(\+49|0049|0)(1[567]\d)(\d{7})$/', $cleaned, $matches)) {
            $prefix = $matches[1] === '0' ? '0' : '+49 ';
            $number = $matches[3];
            // Format: +49 171 1234567
            return $prefix . $matches[2] . ' ' . $number;
        }

        // Deutsche Festnetznummer
        if (preg_match('/^(\+49|0049|0)(\d{2})(\d+)$/', $cleaned, $matches)) {
            $prefix = $matches[1] === '0' ? '0' : '+49 ';
            $areaCode = $matches[2];
            $number = $matches[3];
            // Format with space between area code and number
            return $prefix . $areaCode . ' ' . $number;
        }

        // Alternative for longer area codes (3-5 digits)
        if (preg_match('/^(\+49|0049|0)(\d{3,5})(\d{4,})$/', $cleaned, $matches)) {
            $prefix = $matches[1] === '0' ? '0' : '+49 ';
            return $prefix . $matches[2] . ' ' . $matches[3];
        }

        // Fallback: Original zurückgeben
        return $number;
    }

    /**
     * Übersetzt Status-Werte
     */
    public static function translateStatus(?string $status): string
    {
        return match ($status) {
            'completed' => 'Abgeschlossen',
            'missed' => 'Verpasst',
            'failed' => 'Fehlgeschlagen',
            'busy' => 'Besetzt',
            'no_answer' => 'Keine Antwort',
            'ongoing' => 'Laufend',
            'in-progress' => 'In Bearbeitung',
            'active' => 'Aktiv',
            'ended' => 'Beendet',
            'analyzed' => 'Analysiert',
            default => ucfirst($status ?? 'Unbekannt'),
        };
    }

    /**
     * Übersetzt Sentiment-Werte
     */
    public static function translateSentiment(?string $sentiment): string
    {
        return match ($sentiment) {
            'positive' => 'Positiv',
            'neutral' => 'Neutral',
            'negative' => 'Negativ',
            default => 'Unbekannt',
        };
    }

    /**
     * Übersetzt Session-Outcome-Werte
     */
    public static function translateOutcome(?string $outcome): string
    {
        return match ($outcome) {
            'appointment_scheduled' => 'Termin vereinbart',
            'information_provided' => 'Information gegeben',
            'callback_requested' => 'Rückruf erwünscht',
            'complaint_registered' => 'Beschwerde registriert',
            'no_interest' => 'Kein Interesse',
            'transferred' => 'Weitergeleitet',
            'voicemail' => 'Voicemail',
            default => $outcome ?? '-',
        };
    }

    /**
     * Übersetzt Richtung
     */
    public static function translateDirection(?string $direction): string
    {
        return match ($direction) {
            'inbound' => 'Eingehend',
            'outbound' => 'Ausgehend',
            default => ucfirst($direction ?? 'Unbekannt'),
        };
    }

    /**
     * Gibt die entsprechende Farbe für einen Status zurück
     */
    public static function getStatusColor(?string $status): string
    {
        return match ($status) {
            'completed', 'analyzed' => 'success',
            'missed', 'busy' => 'warning',
            'failed', 'no_answer' => 'danger',
            'ongoing', 'in-progress', 'active' => 'info',
            default => 'gray',
        };
    }

    /**
     * Gibt die entsprechende Farbe für ein Sentiment zurück
     */
    public static function getSentimentColor(?string $sentiment): string
    {
        return match ($sentiment) {
            'positive' => 'success',
            'neutral' => 'gray',
            'negative' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Gibt die entsprechende Farbe für ein Outcome zurück
     */
    public static function getOutcomeColor(?string $outcome): string
    {
        return match ($outcome) {
            'appointment_scheduled' => 'success',
            'information_provided' => 'info',
            'callback_requested' => 'warning',
            'complaint_registered' => 'danger',
            'no_interest' => 'gray',
            'transferred' => 'primary',
            'voicemail' => 'secondary',
            default => 'gray',
        };
    }
}