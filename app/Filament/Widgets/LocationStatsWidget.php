<?php

namespace App\Filament\Widgets;

use App\Models\Location;
use App\Models\User;
use App\Models\Visit;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LocationStatsWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected ?string $pollingInterval = '60s';

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && $user->isSuperAdmin();
    }

    protected function getStats(): array
    {
        $totalLocations = Location::query()->count();
        
        $activeUsers = User::query()
            ->whereNotNull('assigned_location_id')
            ->orWhereHas('locations')
            ->count();

        $visitsThisMonth = Visit::query()
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        return [
            Stat::make('Total Locations', number_format($totalLocations))
                ->description('Active locations')
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('primary'),

            Stat::make('Location Users', number_format($activeUsers))
                ->description('Users assigned to locations')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('This Month Visits', number_format($visitsThisMonth))
                ->description('Across all locations')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
        ];
    }
}
