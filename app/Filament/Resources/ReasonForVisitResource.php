<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReasonForVisitResource\Pages;
use App\Models\ReasonForVisit;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ReasonForVisitResource extends Resource
{
    protected static ?string $model = ReasonForVisit::class;

    protected static \BackedEnum|null|string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Management';

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->since()->sortable(),
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
            'index' => Pages\ListReasonForVisits::route('/'),
            'create' => Pages\CreateReasonForVisit::route('/create'),
            'edit' => Pages\EditReasonForVisit::route('/{record}/edit'),
        ];
    }
}
