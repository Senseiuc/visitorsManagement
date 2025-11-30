<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisitorResource\Pages;
use App\Models\Visitor;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema; // Essential for v4 compatibility
use Filament\Tables;
use Filament\Tables\Table;

class VisitorResource extends Resource
{
    use \App\Traits\HasImageUpload;

    protected static ?string $model = Visitor::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-user';

    public static function canAccess(): bool
    {
        return auth()->check();
    }



    /**
     * Filament v4 form schema (uses Schema object)
     */
    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('first_name')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('last_name')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('email')
                ->email()
                ->maxLength(255),

            Forms\Components\TextInput::make('mobile')
                ->tel()
                ->maxLength(50),

            Forms\Components\TextInput::make('organization')
                ->label('Organization')
                ->maxLength(255),

            Forms\Components\FileUpload::make('image_url')
                ->label('Visitor Image')
                ->image()
                ->directory('visitors')
                ->visibility('public')
                ->disk(static::cloudinaryEnabled() ? 'cloudinary' : 'public')
                ->imageEditor()
                ->imageEditorAspectRatios(['1:1', '4:3', '16:9'])
                ->downloadable()
                ->openable()
                ->extraInputAttributes(['capture' => 'environment']) // Enable camera on mobile
                ->helperText('Upload or replace visitor photo')
                ->saveUploadedFileUsing(function ($component, $file) {
                    return static::handleImageUpload($file);
                }),
        ])->columns(2);
    }

    /**
     * Filament v4 infolist schema
     */
    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                \Filament\Schemas\Components\Section::make('Visitor Details')
                    ->schema([
                        \Filament\Infolists\Components\ImageEntry::make('image_url')
                            ->label('Photo')
                            ->size(200) // Large size
                            ->circular(false) // Keep it square or rectangular for better visibility
                            ->extraImgAttributes(['class' => 'w-full h-auto max-w-md rounded-lg shadow-md']), // Tailwind classes for big image

                        \Filament\Infolists\Components\TextEntry::make('first_name'),
                        \Filament\Infolists\Components\TextEntry::make('last_name'),
                        \Filament\Infolists\Components\TextEntry::make('email'),
                        \Filament\Infolists\Components\TextEntry::make('mobile'),
                        \Filament\Infolists\Components\TextEntry::make('organization')
                            ->label('Organization'),
                        \Filament\Infolists\Components\TextEntry::make('created_at')
                            ->dateTime(),
                    ])->columns(2),
            ]);
    }

    /**
     * Filament v4 table schema (includes ImageColumn)
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Image Column added here
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Photo')
                    ->size(50)
                    ->circular(),

                Tables\Columns\TextColumn::make('first_name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('last_name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('mobile')
                    ->label('Mobile')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('organization')
                    ->label('Organization')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('visits_count')
                    ->counts('visits')
                    ->label('Visits'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_blacklisted')
                    ->label('Blacklist Status')
                    ->placeholder('All visitors')
                    ->trueLabel('Blacklisted')
                    ->falseLabel('Not blacklisted')
                    ->queries(
                        true: fn ($query) => $query->where('is_blacklisted', true),
                        false: fn ($query) => $query->where('is_blacklisted', false),
                    ),

                Tables\Filters\SelectFilter::make('blacklist_request_status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->label('Blacklist Request'),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Registered From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Registered Until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),

                Tables\Filters\Filter::make('has_visits')
                    ->label('Has Visits')
                    ->query(fn ($query) => $query->has('visits'))
                    ->toggle(),

                Tables\Filters\Filter::make('this_month')
                    ->label('Registered This Month')
                    ->query(fn ($query) => $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]))
                    ->toggle(),
            ])
            ->defaultSort('last_name')
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),

                // Admin: Direct Blacklist
                Actions\Action::make('blacklist')
                    ->label('Blacklist')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Visitor $record) => auth()->user()->hasPermission('blacklist.update') && ! $record->is_blacklisted)
                    ->form([
                        Forms\Components\Textarea::make('reasons_for_blacklisting')
                            ->label('Reason')
                            ->required(),
                    ])
                    ->action(function (Visitor $record, array $data) {
                        $record->update([
                            'is_blacklisted' => true,
                            'date_blacklisted' => now(),
                            'reasons_for_blacklisting' => $data['reasons_for_blacklisting'],
                            'blacklist_request_status' => 'approved', // Auto-approve if admin does it
                        ]);
                    }),

                // Receptionist: Request Blacklist
                Actions\Action::make('request_blacklist')
                    ->label('Request Blacklist')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Visitor $record) =>
                        ! auth()->user()->hasPermission('blacklist.update') &&
                        ! $record->is_blacklisted &&
                        $record->blacklist_request_status !== 'pending'
                    )
                    ->form([
                        Forms\Components\Textarea::make('blacklist_request_reason')
                            ->label('Reason for Request')
                            ->required(),
                    ])
                    ->action(function (Visitor $record, array $data) {
                        $record->update([
                            'blacklist_request_status' => 'pending',
                            'blacklist_request_reason' => $data['blacklist_request_reason'],
                            'blacklist_requested_by' => auth()->id(),
                            'blacklist_requested_at' => now(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Blacklist Request Submitted')
                            ->success()
                            ->send();
                    }),

                // Admin: Approve Blacklist Request
                Actions\Action::make('approve_blacklist')
                    ->label('Approve Blacklist')
                    ->icon('heroicon-o-check-badge')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Visitor $record) =>
                        auth()->user()->hasPermission('blacklist.update') &&
                        $record->blacklist_request_status === 'pending' &&
                        ! $record->is_blacklisted
                    )
                    ->form([
                        Forms\Components\Textarea::make('reasons_for_blacklisting')
                            ->label('Final Reason')
                            ->default(fn (Visitor $record) => $record->blacklist_request_reason)
                            ->required(),
                    ])
                    ->action(function (Visitor $record, array $data) {
                        $record->update([
                            'is_blacklisted' => true,
                            'date_blacklisted' => now(),
                            'reasons_for_blacklisting' => $data['reasons_for_blacklisting'],
                            'blacklist_request_status' => 'approved',
                        ]);
                    }),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Filament pages
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVisitors::route('/'),
            'create' => Pages\CreateVisitor::route('/create'),
            'view' => Pages\ViewVisitor::route('/{record}'),
            'edit' => Pages\EditVisitor::route('/{record}/edit'),
        ];
    }
}
