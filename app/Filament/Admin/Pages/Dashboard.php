<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class Dashboard extends Page
{
    /** @var string|null  Route-Slug – leer = "/admin"  */
    protected static ?string $slug = '';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.admin.pages.dashboard';
}
