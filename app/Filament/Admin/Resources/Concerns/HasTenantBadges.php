<?php

namespace App\Filament\Admin\Resources\Concerns;

use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\HtmlString;

trait HasTenantBadges
{
    /**
     * Get a combined tenant badge column
     */
    public static function getTenantBadgeColumn(): Column
    {
        return Stack::make([
            TextColumn::make('tenant_info')
                ->label('Zuordnung')
                ->getStateUsing(function ($record) {
                    $user = auth()->user();
                    $badges = [];
                    
                    // Company badge (only for super admins)
                    if ($user && ($user->hasRole('super_admin') || $user->hasRole('reseller'))) {
                        if ($record->company) {
                            $badges[] = sprintf(
                                '<span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-md bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                    %s
                                </span>',
                                e($record->company->name)
                            );
                        }
                    }
                    
                    // Branch badge
                    if ($record->branch) {
                        $badges[] = sprintf(
                            '<span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-md bg-blue-100 text-blue-700 dark:bg-blue-800 dark:text-blue-300">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M12 7v5m0 0v5m0-5h5m-5 0H7"></path>
                                </svg>
                                %s
                            </span>',
                            e($record->branch->name)
                        );
                    } else {
                        $badges[] = '<span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-md bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Nicht zugeordnet
                        </span>';
                    }
                    
                    return new HtmlString(implode(' ', $badges));
                })
                ->searchable(false)
                ->sortable(false),
        ])
        ->space(1);
    }
    
    /**
     * Get a compact tenant indicator
     */
    public static function getTenantIndicator(): Column
    {
        return TextColumn::make('tenant_indicator')
            ->label('')
            ->getStateUsing(function ($record) {
                $parts = [];
                
                if ($record->company) {
                    $parts[] = substr($record->company->name, 0, 3);
                }
                
                if ($record->branch) {
                    $parts[] = substr($record->branch->name, 0, 3);
                }
                
                return strtoupper(implode('/', $parts));
            })
            ->badge()
            ->color('gray')
            ->size('xs')
            ->extraAttributes(['class' => 'font-mono']);
    }
}