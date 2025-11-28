<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\VisitorResource;
use App\Models\Visitor;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action as TableAction;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RecentVisitorsWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Visitors';
    protected static ?int $sort = 8;
    protected int|string|array $columnSpan = 'full';
    protected ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        $user = auth()->user();
        // Show to admins and superadmins (monitoring widget)
        return $user && ($user->isAdmin() || $user->isSuperAdmin());
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Photo')
                    ->circular()
                    ->defaultImageUrl(url('/images/placeholder-user.png')),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('mobile')
                    ->label('Mobile')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_blacklisted')
                    ->label('Blacklisted')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                TableAction::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->visible(fn () => Auth::user()?->hasPermission('visitors.view') ?? false)
                    ->url(fn (Visitor $record) => VisitorResource::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(),

                TableAction::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn () => Auth::user()?->hasPermission('visitors.update') ?? false)
                    ->url(fn (Visitor $record) => VisitorResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),
            ])
            ->paginated([10, 25])
            ->defaultPaginationPageOption(10);
    }

    protected function getTableQuery(): Builder
    {
        $query = Visitor::query()->latest('created_at');

        // Apply location scoping
        $user = auth()->user();
        $ids = $user?->accessibleLocationIds();

        if (is_array($ids) && !empty($ids)) {
            $query->whereHas('visits', function ($q) use ($ids) {
                $q->where(function ($subQ) use ($ids) {
                    // Check if visit's location_id matches accessible locations
                    $subQ->whereIn('location_id', $ids)
                         // OR if staff belongs to accessible locations (for backward compatibility)
                         ->orWhereHas('staff.locations', function ($qr) use ($ids) {
                             $qr->whereIn('locations.id', $ids);
                         })
                         ->orWhereHas('staff', function ($qr) use ($ids) {
                             $qr->whereIn('assigned_location_id', $ids);
                         });
                });
            });
        }

        return $query;
    }
}
