<?php

namespace App\Filament\Widgets;

use App\Models\Visit;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class CheckedOutVisitsWidget extends BaseWidget
{
    protected static ?string $heading = 'Checked-Out Visits';
    protected static ?int $sort = 9;
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();
        // Show to admins and superadmins (historical data)
        return $user && ($user->isAdmin() || $user->isSuperAdmin());
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('visitor.full_name')
                    ->label('Visitor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Staff Visited')
                    ->searchable()
                    ->formatStateUsing(fn ($state) => $state ?: '—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('location.name')
                    ->label('Location')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('tag_number')
                    ->label('Tag #')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('checkin_time')
                    ->label('Check-in')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('checkout_time')
                    ->label('Check-out')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration')
                    ->getStateUsing(function (Visit $record) {
                        if (!$record->checkin_time || !$record->checkout_time) {
                            return '—';
                        }
                        $diff = $record->checkin_time->diff($record->checkout_time);
                        $hours = $diff->h;
                        $minutes = $diff->i;
                        
                        if ($hours > 0) {
                            return "{$hours}h {$minutes}m";
                        }
                        return "{$minutes}m";
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('reason.name')
                    ->label('Reason')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('checkout_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('checked_out_from')
                            ->label('Checked Out From'),
                        \Filament\Forms\Components\DatePicker::make('checked_out_until')
                            ->label('Checked Out Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['checked_out_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('checkout_time', '>=', $date),
                            )
                            ->when(
                                $data['checked_out_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('checkout_time', '<=', $date),
                            );
                    }),
                
                Tables\Filters\SelectFilter::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('checkout_time', 'desc')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25);
    }

    protected function getTableQuery(): Builder
    {
        $query = Visit::query()
            ->with(['visitor', 'staff', 'location', 'reason'])
            ->where('status', 'approved')
            ->whereNotNull('checkin_time')
            ->whereNotNull('checkout_time')
            ->latest('checkout_time');

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
