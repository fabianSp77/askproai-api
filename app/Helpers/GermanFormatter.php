<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * German Formatter Helper
 * 
 * Formatiert Daten nach deutschen Standards (DIN 5008)
 * für professionelle Geschäftsanwendungen
 */
class GermanFormatter
{
    /**
     * Formatiert Währungsbeträge nach deutschem Standard
     * 
     * @param float $amount Betrag in Euro
     * @param bool $showCurrency Währungssymbol anzeigen
     * @return string Formatierter Betrag (z.B. "1.234,56 €")
     */
    public static function formatCurrency($amount, $showCurrency = true)
    {
        $formatted = number_format($amount, 2, ',', '.');
        return $showCurrency ? $formatted . ' €' : $formatted;
    }

    /**
     * Formatiert Währungsbeträge von Cents
     * 
     * @param int $cents Betrag in Cents
     * @param bool $showCurrency Währungssymbol anzeigen
     * @return string Formatierter Betrag
     */
    public static function formatCentsToEuro($cents, $showCurrency = true)
    {
        return self::formatCurrency($cents / 100, $showCurrency);
    }

    /**
     * Formatiert Zahlen nach deutschem Standard
     * 
     * @param float $number Die zu formatierende Zahl
     * @param int $decimals Anzahl der Dezimalstellen
     * @return string Formatierte Zahl (z.B. "1.234,56")
     */
    public static function formatNumber($number, $decimals = 2)
    {
        return number_format($number, $decimals, ',', '.');
    }

    /**
     * Formatiert Prozentangaben
     * 
     * @param float $value Prozentwert
     * @param int $decimals Anzahl der Dezimalstellen
     * @return string Formatierter Prozentwert (z.B. "75,5 %")
     */
    public static function formatPercentage($value, $decimals = 1)
    {
        return self::formatNumber($value, $decimals) . ' %';
    }

    /**
     * Formatiert Datum und Zeit nach deutschem Standard
     * 
     * @param mixed $date Datum (Carbon, DateTime oder String)
     * @param bool $withTime Mit Zeitangabe
     * @param bool $withSeconds Mit Sekunden
     * @return string Formatiertes Datum (z.B. "08.09.2025 14:30:45")
     */
    public static function formatDateTime($date, $withTime = true, $withSeconds = false)
    {
        if (!$date) {
            return '-';
        }

        if (!($date instanceof Carbon)) {
            $date = Carbon::parse($date);
        }

        if ($withTime) {
            $format = $withSeconds ? 'd.m.Y H:i:s' : 'd.m.Y H:i';
        } else {
            $format = 'd.m.Y';
        }

        return $date->format($format) . ($withTime ? ' Uhr' : '');
    }

    /**
     * Formatiert nur das Datum
     * 
     * @param mixed $date Datum
     * @return string Formatiertes Datum (z.B. "08.09.2025")
     */
    public static function formatDate($date)
    {
        return self::formatDateTime($date, false);
    }

    /**
     * Formatiert nur die Zeit
     * 
     * @param mixed $time Zeit
     * @param bool $withSeconds Mit Sekunden
     * @return string Formatierte Zeit (z.B. "14:30 Uhr")
     */
    public static function formatTime($time, $withSeconds = false)
    {
        if (!$time) {
            return '-';
        }

        if (!($time instanceof Carbon)) {
            $time = Carbon::parse($time);
        }

        $format = $withSeconds ? 'H:i:s' : 'H:i';
        return $time->format($format) . ' Uhr';
    }

    /**
     * Formatiert relative Zeitangaben auf Deutsch
     * 
     * @param mixed $date Datum
     * @return string Relative Zeitangabe (z.B. "vor 5 Minuten")
     */
    public static function formatRelativeTime($date)
    {
        if (!$date) {
            return '-';
        }

        if (!($date instanceof Carbon)) {
            $date = Carbon::parse($date);
        }

        // Carbon auf Deutsch setzen
        Carbon::setLocale('de');
        
        return $date->diffForHumans();
    }

    /**
     * Formatiert deutsche Telefonnummern
     * 
     * @param string $phone Telefonnummer
     * @return string Formatierte Nummer (z.B. "+49 (0)30 12345678")
     */
    public static function formatPhoneNumber($phone)
    {
        if (!$phone) {
            return '-';
        }

        // Entferne alle Nicht-Ziffern
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Internationale Vorwahl
        if (Str::startsWith($phone, '+49')) {
            $phone = substr($phone, 3);
            $prefix = '+49 ';
        } elseif (Str::startsWith($phone, '0049')) {
            $phone = substr($phone, 4);
            $prefix = '+49 ';
        } elseif (Str::startsWith($phone, '0')) {
            $phone = substr($phone, 1);
            $prefix = '+49 (0)';
        } else {
            return $phone; // Unbekanntes Format
        }

        // Formatiere basierend auf Länge
        $length = strlen($phone);
        
        if ($length === 10) { // Normale deutsche Nummer
            // Format: XXX XXXXXXX
            return $prefix . substr($phone, 0, 3) . ' ' . substr($phone, 3);
        } elseif ($length === 11) { // Mit führender 0
            // Format: XXX XXXXXXXX
            return $prefix . substr($phone, 0, 3) . ' ' . substr($phone, 3);
        } else {
            // Fallback für andere Formate
            return $prefix . $phone;
        }
    }

    /**
     * Formatiert Anrufdauer in lesbarem Format
     * 
     * @param int $seconds Dauer in Sekunden
     * @return string Formatierte Dauer (z.B. "5 Min. 30 Sek.")
     */
    public static function formatDuration($seconds)
    {
        if ($seconds < 60) {
            return $seconds . ' Sek.';
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return $minutes . ' Min.' . ($remainingSeconds > 0 ? ' ' . $remainingSeconds . ' Sek.' : '');
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return $hours . ' Std.' . 
               ($remainingMinutes > 0 ? ' ' . $remainingMinutes . ' Min.' : '') .
               ($remainingSeconds > 0 ? ' ' . $remainingSeconds . ' Sek.' : '');
    }

    /**
     * Formatiert Dateigröße in deutschem Format
     * 
     * @param int $bytes Größe in Bytes
     * @return string Formatierte Größe (z.B. "1,5 MB")
     */
    public static function formatFileSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return self::formatNumber($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Formatiert Boolean-Werte auf Deutsch
     * 
     * @param bool $value Boolean-Wert
     * @param string $trueText Text für true
     * @param string $falseText Text für false
     * @return string "Ja" oder "Nein"
     */
    public static function formatBoolean($value, $trueText = 'Ja', $falseText = 'Nein')
    {
        return $value ? $trueText : $falseText;
    }

    /**
     * Formatiert Status-Badges mit deutschen Texten
     * 
     * @param string $status Status-String
     * @return array ['text' => 'Deutscher Text', 'color' => 'Tailwind-Farbe']
     */
    public static function formatStatus($status)
    {
        $statusMap = [
            'success' => ['text' => 'Erfolgreich', 'color' => 'green'],
            'successful' => ['text' => 'Erfolgreich', 'color' => 'green'],
            'failed' => ['text' => 'Fehlgeschlagen', 'color' => 'red'],
            'pending' => ['text' => 'Ausstehend', 'color' => 'yellow'],
            'in_progress' => ['text' => 'In Bearbeitung', 'color' => 'blue'],
            'completed' => ['text' => 'Abgeschlossen', 'color' => 'green'],
            'cancelled' => ['text' => 'Abgebrochen', 'color' => 'gray'],
            'ended' => ['text' => 'Beendet', 'color' => 'gray'],
            'busy' => ['text' => 'Besetzt', 'color' => 'orange'],
            'no-answer' => ['text' => 'Keine Antwort', 'color' => 'yellow'],
            'unknown' => ['text' => 'Unbekannt', 'color' => 'gray'],
        ];

        $key = strtolower(str_replace(' ', '-', $status));
        return $statusMap[$key] ?? ['text' => ucfirst($status), 'color' => 'gray'];
    }

    /**
     * Formatiert Sentiment auf Deutsch
     * 
     * @param string $sentiment Sentiment-String
     * @return array ['text' => 'Deutscher Text', 'icon' => 'Emoji']
     */
    public static function formatSentiment($sentiment)
    {
        $sentimentMap = [
            'positive' => ['text' => 'Positiv', 'icon' => '😊', 'color' => 'green'],
            'negative' => ['text' => 'Negativ', 'icon' => '😔', 'color' => 'red'],
            'neutral' => ['text' => 'Neutral', 'icon' => '😐', 'color' => 'gray'],
            'mixed' => ['text' => 'Gemischt', 'icon' => '🤔', 'color' => 'yellow'],
        ];

        $key = strtolower($sentiment);
        return $sentimentMap[$key] ?? ['text' => 'Unbekannt', 'icon' => '❓', 'color' => 'gray'];
    }

    /**
     * Formatiert Priorität/Dringlichkeit auf Deutsch
     * 
     * @param string $urgency Dringlichkeitsstufe
     * @return array ['text' => 'Deutscher Text', 'color' => 'Tailwind-Farbe']
     */
    public static function formatUrgency($urgency)
    {
        $urgencyMap = [
            'urgent' => ['text' => 'Dringend', 'color' => 'red', 'icon' => '🔴'],
            'dringend' => ['text' => 'Dringend', 'color' => 'red', 'icon' => '🔴'],
            'high' => ['text' => 'Hoch', 'color' => 'orange', 'icon' => '🟠'],
            'hoch' => ['text' => 'Hoch', 'color' => 'orange', 'icon' => '🟠'],
            'fast' => ['text' => 'Schnell', 'color' => 'orange', 'icon' => '🟡'],
            'schnell' => ['text' => 'Schnell', 'color' => 'orange', 'icon' => '🟡'],
            'normal' => ['text' => 'Normal', 'color' => 'blue', 'icon' => '🔵'],
            'low' => ['text' => 'Niedrig', 'color' => 'gray', 'icon' => '⚪'],
            'niedrig' => ['text' => 'Niedrig', 'color' => 'gray', 'icon' => '⚪'],
        ];

        $key = strtolower($urgency);
        return $urgencyMap[$key] ?? ['text' => 'Normal', 'color' => 'blue', 'icon' => '🔵'];
    }
}