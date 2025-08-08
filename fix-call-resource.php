<?php

// Backup and fix CallResource

$original = file_get_contents('/var/www/api-gateway/app/Filament/Admin/Resources/CallResource.php');
file_put_contents('/var/www/api-gateway/app/Filament/Admin/Resources/CallResource.php.backup', $original);

// Create simplified version
$simplified = '<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CallResource\Pages;
use App\Models\Call;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Forms\Form;

class CallResource extends Resource
{
    protected static ?string $model = Call::class;
    
    protected static ?string $navigationIcon = \'heroicon-o-phone-arrow-down-left\';
    
    protected static ?string $navigationLabel = \'Anrufe\';
    
    protected static ?string $navigationGroup = \'Täglicher Betrieb\';
    
    protected static ?int $navigationSort = 110;
    
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make(\'id\')->disabled(),
            Forms\Components\TextInput::make(\'call_id\')->disabled(),
            Forms\Components\TextInput::make(\'status\')->disabled(),
        ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make(\'id\')
                    ->label(\'ID\')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make(\'created_at\')
                    ->label(\'Datum\')
                    ->dateTime(\'d.m.Y H:i\')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make(\'duration_sec\')
                    ->label(\'Dauer\')
                    ->formatStateUsing(fn ($state) => $state ? gmdate(\'i:s\', $state) : \'—\')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make(\'status\')
                    ->label(\'Status\')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        \'completed\' => \'success\',
                        \'failed\' => \'danger\',
                        \'ended\' => \'info\',
                        default => \'gray\',
                    }),
                    
                Tables\Columns\TextColumn::make(\'from_phone\')
                    ->label(\'Von\')
                    ->default(\'—\'),
                    
                Tables\Columns\TextColumn::make(\'to_phone\')
                    ->label(\'Nach\')
                    ->default(\'—\'),
                    
                Tables\Columns\TextColumn::make(\'customer.name\')
                    ->label(\'Kunde\')
                    ->default(\'—\'),
            ])
            ->defaultSort(\'created_at\', \'desc\')
            ->paginated([25, 50, 100]);
    }
    
    public static function getPages(): array
    {
        return [
            \'index\' => Pages\ListCalls::route(\'/\'),
            \'create\' => Pages\CreateCall::route(\'/create\'),
            \'edit\' => Pages\EditCall::route(\'/{record}/edit\'),
            \'view\' => Pages\ViewCall::route(\'/{record}\'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}';

file_put_contents('/var/www/api-gateway/app/Filament/Admin/Resources/CallResource.php.simplified', $simplified);

echo "Backup created: CallResource.php.backup\n";
echo "Simplified version created: CallResource.php.simplified\n";
echo "\nTo apply the fix, run:\n";
echo "cp /var/www/api-gateway/app/Filament/Admin/Resources/CallResource.php.simplified /var/www/api-gateway/app/Filament/Admin/Resources/CallResource.php\n";
echo "\nTo restore original, run:\n";
echo "cp /var/www/api-gateway/app/Filament/Admin/Resources/CallResource.php.backup /var/www/api-gateway/app/Filament/Admin/Resources/CallResource.php\n";