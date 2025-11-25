<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepartmentResource\Pages;
use App\Models\Department;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-group';

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
            return $query->whereHas('floor', function ($q) use ($ids) {
                $q->whereIn('location_id', $ids);
            });
        }
        return $query->whereRaw('1 = 0');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('location_id')
                ->label('Location')
                ->options(\App\Models\Location::all()->pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(fn ($set) => $set('floor_id', null))
                ->dehydrated(false)
                ->visible(fn () => auth()->user()->isSuperAdmin())
                ->default(function ($record) {
                    if ($record && $record->floor) {
                        return $record->floor->location_id;
                    }
                    return null;
                }),
            Forms\Components\Select::make('floor_id')
                ->relationship('floor', 'name', modifyQueryUsing: function ($query, $get) {
                    $user = auth()->user();
                    
                    // If superadmin and location is selected, filter by location
                    if ($user->isSuperAdmin()) {
                        $locationId = $get('location_id');
                        if ($locationId) {
                            $query->where('location_id', $locationId);
                        }
                        return;
                    }

                    // For other roles, use accessible locations
                    if ($user && ($user->isAdmin() || $user->isReceptionist())) {
                        $ids = $user->accessibleLocationIds();
                        if ($ids === null) {
                            return; // superadmin case (fallback)
                        }
                        $query->whereIn('location_id', $ids ?: [-1]);
                    }
                })
                ->required()
                ->searchable()
                ->preload()
                ->label('Floor'),
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
                Tables\Columns\TextColumn::make('floor.name')->label('Floor')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('floor.location.name')->label('Location')->sortable()->searchable(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
            'edit' => Pages\EditDepartment::route('/{record}/edit'),
        ];
    }
}
