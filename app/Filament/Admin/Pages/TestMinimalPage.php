<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class TestMinimalPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationLabel = 'Test Minimal';
    protected static ?string $navigationGroup = 'System & Monitoring';
    protected static string $view = 'filament.admin.pages.test-minimal';
    protected static ?int $navigationSort = 99;
}