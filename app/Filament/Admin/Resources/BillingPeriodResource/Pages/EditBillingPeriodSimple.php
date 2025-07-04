<?php

namespace App\Filament\Admin\Resources\BillingPeriodResource\Pages;

use App\Filament\Admin\Resources\BillingPeriodResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms;
use Filament\Forms\Form;

class EditBillingPeriodSimple extends EditRecord
{
    protected static string $resource = BillingPeriodResource::class;
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('id')
                            ->disabled(),
                        
                        Forms\Components\Select::make('company_id')
                            ->relationship('company', 'name')
                            ->disabled(),
                        
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'active' => 'Active',
                                'processed' => 'Processed',
                                'invoiced' => 'Invoiced',
                                'closed' => 'Closed',
                            ])
                            ->required(),
                        
                        Forms\Components\DatePicker::make('start_date')
                            ->required(),
                        
                        Forms\Components\DatePicker::make('end_date')
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }
}