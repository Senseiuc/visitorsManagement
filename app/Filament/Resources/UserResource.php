<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Role;
use App\Models\User;
use App\Support\Permissions as Perms;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static \BackedEnum|null|string $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Management';

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

        if ($user->isAdmin()) {
            // Admins can only see receptionists they created or who share any of their accessible locations
            $ids = $user->accessibleLocationIds() ?? [];
            return $query
                ->where(function ($q) {
                    $q->whereHas('roles', function ($qr) {
                        $qr->where('slug', 'receptionist');
                    })->orWhereHas('roleRelation', function ($qr) {
                        $qr->where('slug', 'receptionist');
                    });
                })
                ->where(function ($q) use ($user, $ids) {
                    $q->where('created_by_user_id', $user->id)
                      ->orWhereHas('locations', function ($q2) use ($ids) {
                          $q2->whereIn('locations.id', $ids ?: [-1]);
                      });
                });
        }

        // Receptionists cannot access users
        return $query->whereRaw('1 = 0');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('email')
                ->email()
                ->unique(ignoreRecord: true)
                ->required(),

            Forms\Components\TextInput::make('phone_number')
                ->label('Phone Number')
                ->tel()
                ->maxLength(50)
                ->nullable(),

            Forms\Components\TextInput::make('intercom')
                ->label('Intercom')
                ->maxLength(50)
                ->nullable(),

            Forms\Components\TextInput::make('password')
                ->password()
                ->revealable()
                ->required(fn ($livewire) => $livewire instanceof Pages\CreateUser)
                ->dehydrated(fn ($state) => filled($state))
                ->maxLength(255),

            // Multi-role assignment for Super Admins
            Forms\Components\Select::make('roles')
                ->label('Roles')
                ->relationship('roles', 'name')
                ->multiple()
                ->searchable()
                ->preload()
                ->helperText('Assign one or more roles to this user.')
                ->visible(fn () => auth()->user()?->isSuperAdmin()),

            Forms\Components\Select::make('locations')
                ->label('Locations')
                ->relationship('locations', 'name', modifyQueryUsing: function ($query) {
                    $auth = auth()->user();
                    if ($auth && !$auth->isSuperAdmin()) {
                        $ids = $auth->accessibleLocationIds();
                        if ($ids !== null) {
                            $query->whereIn('id', $ids ?: [-1]);
                        }
                    }
                })
                ->multiple()
                ->searchable()
                ->preload()
                ->required(fn () => ! auth()->user()?->isSuperAdmin())
                ->helperText('Assign one or more locations. Non-superadmin users are restricted to their accessible locations.'),

            Forms\Components\CheckboxList::make('permissions')
                ->label('Direct Permissions (optional)')
                ->options(function () {
                    $all = Perms::all();
                    $auth = auth()->user();
                    if ($auth?->isSuperAdmin()) {
                        return $all;
                    }
                    if ($auth?->isAdmin()) {
                        $perms = $auth->effectivePermissions();
                        // Admin can only assign permissions they possess
                        return array_intersect_key($all, array_flip($perms));
                    }
                    return [];
                })
                ->columns(2)
                ->helperText('Admins can only grant permissions they already have. Role permissions will be applied in addition to these.')
                ->visible(fn () => auth()->user()?->isSuperAdmin() || auth()->user()?->isAdmin()),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Phone')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('intercom')
                    ->label('Intercom')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TagsColumn::make('roles.name')
                    ->label('Roles')
                    ->separator(', ')
                    ->limitList(3)
                    ->separator(', ')
                    ->searchable(),
                Tables\Columns\TagsColumn::make('locations.name')
                    ->label('Locations')
                    ->separator(', ')
                    ->limitList(3)
                    ->searchable(),
                Tables\Columns\TextColumn::make('creator.name')->label('Created By')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updater.name')->label('Updated By')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->label('Role')
                    ->relationship('roles', 'name'),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
