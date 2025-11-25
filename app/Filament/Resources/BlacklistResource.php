<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlacklistResource\Pages;
use App\Models\Visitor;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class BlacklistResource extends Resource
{
    protected static ?string $model = Visitor::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-shield-exclamation';
    
    protected static ?string $navigationLabel = 'Blacklist Management';
    
    protected static ?string $slug = 'blacklist-management';

    protected static ?int $navigationSort = 90;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->isSuperAdmin() || $user->isAdmin());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('is_blacklisted', true)
                ->orWhereNotNull('blacklist_request_status'))
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Photo')
                    ->circular(),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Visitor')
                    ->searchable(['first_name', 'last_name']),

                Tables\Columns\TextColumn::make('status_label')
                    ->label('Status')
                    ->badge()
                    ->color(fn (Visitor $record) => $record->is_blacklisted ? 'danger' : 'warning')
                    ->getStateUsing(fn (Visitor $record) => $record->is_blacklisted ? 'Blacklisted' : 'Request Pending'),

                Tables\Columns\TextColumn::make('reason_display')
                    ->label('Reason')
                    ->limit(50)
                    ->tooltip(fn (Visitor $record) => $record->is_blacklisted ? $record->reasons_for_blacklisting : $record->blacklist_request_reason)
                    ->getStateUsing(fn (Visitor $record) => $record->is_blacklisted ? $record->reasons_for_blacklisting : $record->blacklist_request_reason),

                Tables\Columns\TextColumn::make('blacklistRequester.name')
                    ->label('Requested By')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at') // Using created_at as a proxy for "Date" generally, or we can use specific dates
                    ->label('Date')
                    ->getStateUsing(fn (Visitor $record) => $record->is_blacklisted ? $record->date_blacklisted : $record->blacklist_requested_at)
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                // Approve Request
                Action::make('approve_request')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('danger')
                    ->visible(fn (Visitor $record) => !$record->is_blacklisted && $record->blacklist_request_status === 'pending')
                    ->requiresConfirmation()
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
                        Notification::make()->title('Visitor Blacklisted')->success()->send();
                    }),

                // Reject Request
                Action::make('reject_request')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->visible(fn (Visitor $record) => !$record->is_blacklisted && $record->blacklist_request_status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (Visitor $record) {
                        $record->update([
                            'blacklist_request_status' => 'rejected',
                            // We keep the request history but they don't show up in the main list unless we want them to
                            // The query filters for NOT NULL status, so rejected will still show.
                            // Maybe we want to clear it or keep it? Let's keep it but maybe filter out rejected by default?
                            // For now, let's just set status to rejected.
                        ]);
                        Notification::make()->title('Request Rejected')->success()->send();
                    }),

                // Remove from Blacklist
                Action::make('remove_blacklist')
                    ->label('Remove')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->visible(fn (Visitor $record) => $record->is_blacklisted)
                    ->requiresConfirmation()
                    ->action(function (Visitor $record) {
                        $record->update([
                            'is_blacklisted' => false,
                            'date_blacklisted' => null,
                            'reasons_for_blacklisting' => null,
                            'blacklist_request_status' => null, // Reset request status
                        ]);
                        Notification::make()->title('Visitor Removed from Blacklist')->success()->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlacklists::route('/'),
        ];
    }
}
