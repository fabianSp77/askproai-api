<?php

namespace App\Filament\Admin\Resources\CustomerResource\Pages;

use App\Filament\Admin\Resources\CustomerResource;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Customer;
use Filament\Notifications\Notification;
use Filament\Forms;

class FindDuplicates extends Page implements HasTable
{
    use InteractsWithTable;
    
    protected static string $resource = CustomerResource::class;
    
    protected static string $view = 'filament.admin.resources.customer-resource.pages.find-duplicates';
    
    protected static ?string $title = 'Duplikate finden';
    
    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Customer::query()
                    ->select('customers.*')
                    ->selectRaw('COUNT(*) OVER (PARTITION BY LOWER(email)) as email_count')
                    ->selectRaw('COUNT(*) OVER (PARTITION BY phone) as phone_count')
                    ->selectRaw('COUNT(*) OVER (PARTITION BY LOWER(name)) as name_count')
                    ->whereRaw('(
                        EXISTS (SELECT 1 FROM customers c2 WHERE c2.id != customers.id AND LOWER(c2.email) = LOWER(customers.email) AND customers.email IS NOT NULL)
                        OR EXISTS (SELECT 1 FROM customers c2 WHERE c2.id != customers.id AND c2.phone = customers.phone AND customers.phone IS NOT NULL)
                        OR EXISTS (SELECT 1 FROM customers c2 WHERE c2.id != customers.id AND LOWER(c2.name) = LOWER(customers.name))
                    )')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable()
                    ->badge()
                    ->color(fn ($record) => $record->email_count > 1 ? 'danger' : 'gray'),
                    
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefon')
                    ->searchable()
                    ->badge()
                    ->color(fn ($record) => $record->phone_count > 1 ? 'danger' : 'gray'),
                    
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Unternehmen')
                    ->badge(),
                    
                Tables\Columns\TextColumn::make('duplicate_type')
                    ->label('Duplikat-Typ')
                    ->state(function ($record) {
                        $types = [];
                        if ($record->email_count > 1) $types[] = 'E-Mail';
                        if ($record->phone_count > 1) $types[] = 'Telefon';
                        if ($record->name_count > 1) $types[] = 'Name';
                        return implode(', ', $types);
                    })
                    ->badge()
                    ->color('warning'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('duplicate_type')
                    ->label('Duplikat-Typ')
                    ->options([
                        'email' => 'E-Mail-Duplikate',
                        'phone' => 'Telefon-Duplikate',
                        'name' => 'Namens-Duplikate',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['value'] === 'email') {
                            return $query->whereRaw('EXISTS (SELECT 1 FROM customers c2 WHERE c2.id != customers.id AND LOWER(c2.email) = LOWER(customers.email) AND customers.email IS NOT NULL)');
                        } elseif ($data['value'] === 'phone') {
                            return $query->whereRaw('EXISTS (SELECT 1 FROM customers c2 WHERE c2.id != customers.id AND c2.phone = customers.phone AND customers.phone IS NOT NULL)');
                        } elseif ($data['value'] === 'name') {
                            return $query->whereRaw('EXISTS (SELECT 1 FROM customers c2 WHERE c2.id != customers.id AND LOWER(c2.name) = LOWER(customers.name))');
                        }
                        return $query;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view_duplicates')
                    ->label('Duplikate anzeigen')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Mögliche Duplikate')
                    ->modalWidth('6xl')
                    ->modalContent(function ($record) {
                        $duplicates = Customer::where('id', '!=', $record->id)
                            ->where(function ($query) use ($record) {
                                $query->where(function ($q) use ($record) {
                                    if ($record->email) {
                                        $q->whereRaw('LOWER(email) = ?', [strtolower($record->email)]);
                                    }
                                })
                                ->orWhere(function ($q) use ($record) {
                                    if ($record->phone) {
                                        $q->where('phone', $record->phone);
                                    }
                                })
                                ->orWhereRaw('LOWER(name) = ?', [strtolower($record->name)]);
                            })
                            ->with('company')
                            ->get();
                            
                        return view('filament.customer.duplicates-modal', [
                            'original' => $record,
                            'duplicates' => $duplicates,
                        ]);
                    }),
                    
                Tables\Actions\Action::make('merge')
                    ->label('Zusammenführen')
                    ->icon('heroicon-o-arrows-pointing-in')
                    ->color('warning')
                    ->action(function ($record) {
                        return redirect()->to(CustomerResource::getUrl('edit', ['record' => $record]))
                            ->with('highlight_merge', true);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('merge_selected')
                    ->label('Ausgewählte zusammenführen')
                    ->icon('heroicon-o-arrows-pointing-in')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Kunden zusammenführen')
                    ->modalDescription('Die ausgewählten Kunden werden zusammengeführt. Wählen Sie den Hauptkunden aus.')
                    ->form([
                        Forms\Components\Select::make('primary_customer_id')
                            ->label('Hauptkunde (alle Daten werden hierhin übertragen)')
                            ->options(fn ($records) => $records->pluck('name', 'id'))
                            ->required(),
                    ])
                    ->action(function ($records, array $data) {
                        $primaryCustomer = Customer::find($data['primary_customer_id']);
                        $otherCustomers = $records->where('id', '!=', $primaryCustomer->id);
                        
                        foreach ($otherCustomers as $customer) {
                            // Transfer appointments
                            $customer->appointments()->update(['customer_id' => $primaryCustomer->id]);
                            
                            // Merge contact info if primary is missing
                            if (empty($primaryCustomer->email) && !empty($customer->email)) {
                                $primaryCustomer->email = $customer->email;
                            }
                            if (empty($primaryCustomer->phone) && !empty($customer->phone)) {
                                $primaryCustomer->phone = $customer->phone;
                            }
                            
                            // Delete the duplicate
                            $customer->delete();
                        }
                        
                        $primaryCustomer->save();
                        
                        Notification::make()
                            ->title('Kunden zusammengeführt')
                            ->body($otherCustomers->count() . ' Kunden wurden mit ' . $primaryCustomer->name . ' zusammengeführt.')
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('created_at', 'desc')
            ->heading('Mögliche Duplikate')
            ->description('Hier finden Sie Kunden mit identischen E-Mail-Adressen, Telefonnummern oder Namen.');
    }
}