<?php

namespace App\Filament\Resources\NotificationQueueResource\Pages;

use App\Filament\Resources\NotificationQueueResource;
use Filament\Resources\Pages\ListRecords;

class ListNotificationQueues extends ListRecords
{
    protected static string $resource = NotificationQueueResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            NotificationQueueResource\Widgets\NotificationStats::class,
        ];
    }
}