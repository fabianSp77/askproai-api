<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BranchResource\Pages;
use App\Models\{Branch, Service};
use Filament\Forms;
use Filament\Forms\Components\{Repeater, Select, TextInput, Toggle};
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class BranchResource extends Resource
{
    protected static ?string $model           = Branch::class;
    protected static ?string $navigationIcon  = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Stammdaten';

    /* ─────────────  Formular  ───────────── */
    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            TextInput::make('name')->required(),
            TextInput::make('city')->label('Ort'),
            TextInput::make('phone_number')->label('Telefon'),
            Toggle::make('active')->label('Aktiv')->default(true),
        ]);
    }

    /* ─────────────  Tabelle  ───────────── */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('city'),
                Tables\Columns\IconColumn::make('active')->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                /* -------- Service-Import -------- */
                Action::make('importServices')
                    ->label('Services importieren')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->modalHeading('Services aus anderer Filiale kopieren')
                    ->form([
                        /* Quelle-Filiale */
                        Select::make('source_branch_id')
                            ->label('Quelle-Filiale')
                            ->options(fn () => Branch::pluck('name', 'id'))
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                // direkt nach Filial-Auswahl Services in den Repeater einfüllen
                                $services = Branch::find($state)?->services()->get() ?? collect();
                                $set(
                                    'items',
                                    $services
                                        ->map(fn ($s) => [
                                            'service_id' => $s->id,
                                            'name'       => $s->name,
                                            'price'      => $s->price,
                                        ])
                                        ->toArray(),
                                );
                            })
                            ->required(),

                        /* Services + Edit */
                        Repeater::make('items')
                            ->label('Services auswählen & anpassen')
                            ->schema([
                                Select::make('service_id')
                                    ->label('Service')
                                    ->options(fn (Forms\Get $get) => $get('source_branch_id')
                                        ? Branch::find($get('source_branch_id'))
                                            ?->services()
                                            ->pluck('name', 'id')
                                            ->toArray()
                                        : []
                                    )
                                    ->required(),

                                TextInput::make('name')
                                    ->label('Name')
                                    ->placeholder('wie Original   (optional)'),

                                TextInput::make('price')
                                    ->label('Preis (€)')
                                    ->numeric()
                                    ->step(0.01),
                            ])
                            ->columns(3),
                    ])
                    ->action(function (array $data, Branch $record) {

                        $source = Branch::find($data['source_branch_id']);
                        if (! $source) {
                            Notification::make()
                                ->title('Quelle-Filiale nicht gefunden')
                                ->danger()
                                ->send();
                            return;
                        }

                        $copied = 0;

                        foreach ($data['items'] as $item) {
                            /* Original-Service */
                            $orig = $source->services()->find($item['service_id']);
                            if (! $orig) {
                                continue;
                            }

                            /* Duplikat anlegen */
                            $new = Service::create([
                                'name'        => $item['name'] ?: $orig->name,
                                'description' => $orig->description,
                                'price'       => $item['price'] ?? $orig->price,
                                'active'      => $orig->active,
                            ]);

                            /* In Ziel-Filiale verknüpfen */
                            $record->services()->attach($new->id);
                            $copied++;
                        }

                        Notification::make()
                            ->title("$copied Service(s) kopiert")
                            ->success()
                            ->send();
                    })
                    ->modalSubmitActionLabel('Kopieren'),
            ]);
    }

    /* ─────────────  Seiten  ───────────── */
    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBranches::route('/'),
            'create' => Pages\CreateBranch::route('/create'),
            'edit'   => Pages\EditBranch::route('/{record}/edit'),
        ];
    }
}
