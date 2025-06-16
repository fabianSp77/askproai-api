<?php

namespace App\Filament\Components;

use Filament\Tables\Columns\TextColumn;

class StatusBadge
{
    /**
     * Create a status badge column with icons and colors
     */
    public static function make(string $name = 'status'): TextColumn
    {
        return TextColumn::make($name)
            ->badge()
            ->label('Status')
            ->colors(static::getStatusColors())
            ->icons(static::getStatusIcons())
            ->formatStateUsing(fn ($state) => static::getStatusLabel($state));
    }

    /**
     * Create appointment status badge
     */
    public static function appointmentStatus(string $name = 'status'): TextColumn
    {
        return TextColumn::make($name)
            ->badge()
            ->label('Status')
            ->colors([
                'warning' => ['pending', 'scheduled'],
                'success' => ['confirmed', 'completed', 'booked'],
                'danger' => ['cancelled', 'no_show'],
                'info' => ['rescheduled'],
            ])
            ->icons([
                'heroicon-o-clock' => ['pending', 'scheduled'],
                'heroicon-o-check-circle' => ['confirmed', 'completed', 'booked'],
                'heroicon-o-x-circle' => ['cancelled'],
                'heroicon-o-exclamation-circle' => ['no_show'],
                'heroicon-o-arrow-path' => ['rescheduled'],
            ])
            ->formatStateUsing(fn ($state) => match($state) {
                'pending' => 'Ausstehend',
                'scheduled' => 'Geplant',
                'confirmed' => 'Bestätigt',
                'completed' => 'Abgeschlossen',
                'booked' => 'Gebucht',
                'cancelled' => 'Abgesagt',
                'no_show' => 'Nicht erschienen',
                'rescheduled' => 'Verschoben',
                default => ucfirst($state)
            });
    }

    /**
     * Create call status badge
     */
    public static function callStatus(string $name = 'status'): TextColumn
    {
        return TextColumn::make($name)
            ->badge()
            ->label('Status')
            ->colors([
                'success' => ['completed', 'answered'],
                'danger' => ['failed', 'busy', 'no_answer'],
                'warning' => ['in_progress', 'ringing'],
                'gray' => ['ended', 'voicemail'],
            ])
            ->icons([
                'heroicon-o-phone-arrow-up-right' => ['in_progress', 'ringing'],
                'heroicon-o-check-circle' => ['completed', 'answered'],
                'heroicon-o-x-circle' => ['failed'],
                'heroicon-o-phone-x-mark' => ['busy', 'no_answer'],
                'heroicon-o-microphone' => ['voicemail'],
                'heroicon-o-phone' => ['ended'],
            ])
            ->formatStateUsing(fn ($state) => match($state) {
                'completed' => 'Abgeschlossen',
                'answered' => 'Beantwortet',
                'failed' => 'Fehlgeschlagen',
                'busy' => 'Besetzt',
                'no_answer' => 'Keine Antwort',
                'in_progress' => 'Läuft',
                'ringing' => 'Klingelt',
                'ended' => 'Beendet',
                'voicemail' => 'Mailbox',
                default => ucfirst($state)
            });
    }

    /**
     * Create activity status badge
     */
    public static function activityStatus(string $name = 'active'): TextColumn
    {
        return TextColumn::make($name)
            ->badge()
            ->label('Status')
            ->colors([
                'success' => fn ($state) => $state === true || $state === 1,
                'danger' => fn ($state) => $state === false || $state === 0,
            ])
            ->icons([
                'heroicon-o-check-circle' => fn ($state) => $state === true || $state === 1,
                'heroicon-o-x-circle' => fn ($state) => $state === false || $state === 0,
            ])
            ->formatStateUsing(fn ($state) => $state ? 'Aktiv' : 'Inaktiv');
    }

    /**
     * Create priority badge
     */
    public static function priority(string $name = 'priority'): TextColumn
    {
        return TextColumn::make($name)
            ->badge()
            ->label('Priorität')
            ->colors([
                'danger' => 'high',
                'warning' => 'medium',
                'success' => 'low',
                'gray' => 'none',
            ])
            ->icons([
                'heroicon-o-arrow-up' => 'high',
                'heroicon-o-minus' => 'medium',
                'heroicon-o-arrow-down' => 'low',
                'heroicon-o-minus-circle' => 'none',
            ])
            ->formatStateUsing(fn ($state) => match($state) {
                'high' => 'Hoch',
                'medium' => 'Mittel',
                'low' => 'Niedrig',
                'none' => 'Keine',
                default => ucfirst($state)
            });
    }

    /**
     * Default status colors mapping
     */
    protected static function getStatusColors(): array
    {
        return [
            'success' => ['active', 'completed', 'confirmed', 'approved', 'published'],
            'warning' => ['pending', 'processing', 'scheduled', 'draft'],
            'danger' => ['inactive', 'cancelled', 'rejected', 'failed', 'error'],
            'info' => ['new', 'updated', 'modified'],
            'gray' => ['archived', 'deleted', 'unknown'],
        ];
    }

    /**
     * Default status icons mapping
     */
    protected static function getStatusIcons(): array
    {
        return [
            'heroicon-o-check-circle' => ['active', 'completed', 'confirmed', 'approved'],
            'heroicon-o-clock' => ['pending', 'processing', 'scheduled'],
            'heroicon-o-x-circle' => ['inactive', 'cancelled', 'rejected', 'failed'],
            'heroicon-o-exclamation-circle' => ['error', 'warning'],
            'heroicon-o-archive-box' => ['archived'],
            'heroicon-o-trash' => ['deleted'],
            'heroicon-o-question-mark-circle' => ['unknown'],
        ];
    }

    /**
     * Get status label
     */
    protected static function getStatusLabel(string $state): string
    {
        return match($state) {
            'active' => 'Aktiv',
            'inactive' => 'Inaktiv',
            'completed' => 'Abgeschlossen',
            'pending' => 'Ausstehend',
            'confirmed' => 'Bestätigt',
            'cancelled' => 'Abgesagt',
            'approved' => 'Genehmigt',
            'rejected' => 'Abgelehnt',
            'published' => 'Veröffentlicht',
            'draft' => 'Entwurf',
            'archived' => 'Archiviert',
            'deleted' => 'Gelöscht',
            default => ucfirst(str_replace('_', ' ', $state))
        };
    }
}