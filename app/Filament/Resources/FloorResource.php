<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FloorResource\Pages;
use App\Filament\Resources\FloorResource\RelationManagers\DepartmentsRelationManager;
use App\Models\Floor;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FloorResource extends Resource
{
    protected static ?string $model = Floor::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

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
                return $query;
            }
            if (empty($ids)) {
                return $query->whereRaw('1 = 0');
            }
            return $query->whereIn('location_id', $ids);
        }
        return $query->whereRaw('1 = 0');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('location_id')
                ->relationship('location', 'name', modifyQueryUsing: function ($query) {
                    $user = auth()->user();
                    if ($user && ($user->isAdmin() || $user->isReceptionist())) {
                        $ids = $user->accessibleLocationIds();
                        if ($ids === null) {
                            return; // superadmin case
                        }
                        $query->whereIn('id', $ids ?: [-1]);
                    }
                })
                ->required()
                ->searchable()
                ->preload(),
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('location.name')->label('Location')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('departments_count')->counts('departments')->label('Departments'),
                Tables\Columns\TextColumn::make('creator.name')->label('Created By')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updater.name')->label('Updated By')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->since()->sortable(),
            ])
            ->defaultSort('name')
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
            DepartmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFloors::route('/'),
            'create' => Pages\CreateFloor::route('/create'),
            'edit' => Pages\EditFloor::route('/{record}/edit'),
        ];
    }
}
