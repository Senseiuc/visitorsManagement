<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocationResource\Pages;
use App\Filament\Resources\LocationResource\RelationManagers\FloorsRelationManager;
use App\Models\Location;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static BackedEnum|null|string $navigationIcon = 'heroicon-o-map-pin';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isSuperAdmin()) {
            return $query;
        }

        if ($user->isAdmin() || $user->isReceptionist()) {
            $ids = $user->accessibleLocationIds();
            if ($ids === null) {
                return $query; // superadmin case already handled, but keep safe
            }
            if (empty($ids)) {
                return $query->whereRaw('1 = 0');
            }
            return $query->whereIn('id', $ids);
        }

        return $query->whereRaw('1 = 0');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('address')
                    ->rows(3)
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->copyable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('checkin_link')
                    ->label('Check-in Link')
                    ->getStateUsing(fn (Location $record) => route('visitor.location.start', $record->uuid))
                    ->copyable()
                    ->copyableState(fn (Location $record) => route('visitor.location.start', $record->uuid))
                    ->copyMessage('Check-in link copied')
                    ->formatStateUsing(fn () => 'Copy Link')
                    ->color('primary')
                    ->url(fn (Location $record) => route('visitor.location.start', $record->uuid), true), // Open in new tab
                Tables\Columns\TextColumn::make('address')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('floors_count')
                    ->counts('floors')
                    ->label('Floors'),
                Tables\Columns\TextColumn::make('creator.name')->label('Created By')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updater.name')->label('Updated By')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->since()->sortable(),
            ])
            ->defaultSort('name')
            ->filters([
                //
            ])
            ->actions([
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            FloorsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLocations::route('/'),
            'create' => Pages\CreateLocation::route('/create'),
            'edit' => Pages\EditLocation::route('/{record}/edit'),
        ];
    }
}
