<?php

// Simplified BranchResource for Sept 21 backup
// Only uses: id, company_id, name, slug, is_active, created_at, updated_at, deleted_at

public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\Section::make('Filial-Informationen')
                ->schema([
                    Forms\Components\Select::make('company_id')
                        ->label('Unternehmen')
                        ->relationship('company', 'name')
                        ->required()
                        ->searchable()
                        ->preload(),

                    Forms\Components\TextInput::make('name')
                        ->label('Filialname')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktiv')
                        ->default(true),
                ]),
        ]);
}

public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('id')
                ->label('ID')
                ->sortable(),

            Tables\Columns\TextColumn::make('company.name')
                ->label('Unternehmen')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('name')
                ->label('Filialname')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('slug')
                ->label('Slug')
                ->searchable(),

            Tables\Columns\IconColumn::make('is_active')
                ->label('Aktiv')
                ->boolean()
                ->sortable(),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Erstellt')
                ->dateTime('d.m.Y H:i')
                ->sortable()
                ->toggleable(),

            Tables\Columns\TextColumn::make('updated_at')
                ->label('Aktualisiert')
                ->dateTime('d.m.Y H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('company_id')
                ->label('Unternehmen')
                ->relationship('company', 'name')
                ->searchable()
                ->preload(),

            Tables\Filters\Filter::make('is_active')
                ->label('Nur Aktive')
                ->query(fn (Builder $query): Builder => $query->where('is_active', true))
                ->default(),
        ])
        ->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ])
        ->defaultSort('name');
}
