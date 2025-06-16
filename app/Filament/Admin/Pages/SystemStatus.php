<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class SystemStatus extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationLabel = 'Systemstatus';
    protected static ?string $navigationGroup = 'System & Monitoring';
    protected static string $view = 'filament.admin.pages.system-status';
    protected static ?int $navigationSort = 30;

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\AnimatedStatusWidget::class,
            \App\Filament\Widgets\StripeStatusWidget::class,
            \App\Filament\Widgets\MailStatusWidget::class,
            \App\Filament\Widgets\LogStatusWidget::class,
            \App\Filament\Widgets\QueueStatusWidget::class,
            \App\Filament\Widgets\BackupStatusWidget::class,
            \App\Filament\Widgets\MiddlewareStatusWidget::class,
        ];
    }
}
