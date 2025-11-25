<?php

namespace App\Filament\Widgets;

use App\Models\Visit;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action as TableAction;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class NotCheckedOutWidget extends BaseWidget
{
    protected static ?string $heading = "Visitors Still On-site";
    protected static ?int $sort = 7;
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();
        // Only show to receptionists (operational widget)
        return $user && $user->isReceptionist();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('visitor.full_name')->label('Visitor')->searchable(),
                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Staff')
                    ->formatStateUsing(fn ($state) => $state ?: '—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('tag_number')->label('Tag #')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('checkin_time')->label('Checked In')->dateTime()->sortable(),
            ])
            ->actions([
                TableAction::make('checkout')
                    ->label('Check out')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('warning')
                    ->visible(fn () => Auth::user()?->hasPermission('visits.update') ?? false)
                    ->requiresConfirmation()
                    ->action(function (Visit $record) {
                        $record->forceFill([
                            'checkout_time' => now(),
                        ])->save();
                    }),
            ])
            ->paginated([10, 25])
            ->defaultPaginationPageOption(10);
    }

    protected function getTableQuery(): Builder
    {
        $query = Visit::query()
            ->with(['visitor', 'staff'])
            ->where('status', 'approved')
            ->whereNotNull('checkin_time')
            ->whereNull('checkout_time')
            ->latest('checkin_time');

        // Apply location scoping
        $user = auth()->user();
        $ids = $user?->accessibleLocationIds();

        if (is_array($ids) && !empty($ids)) {
            $query->where(function ($q) use ($ids) {
                // Check if visit's location_id matches accessible locations
                $q->whereIn('location_id', $ids)
                  // OR if staff belongs to accessible locations (for backward compatibility)
                  ->orWhereHas('staff.locations', function ($qr) use ($ids) {
                      $qr->whereIn('locations.id', $ids);
                  })
                  ->orWhereHas('staff', function ($qr) use ($ids) {
                      $qr->whereIn('assigned_location_id', $ids);
                  });
            });
        }

        return $query;
    }
}
