<?php

namespace App\Filament\Widgets;

use App\Models\Visit;
use App\Models\Visitor;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VisitorStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && ($user->isSuperAdmin() || $user->isAdmin());
    }

    protected function getStats(): array
    {
        $totalVisitors = Visitor::query()->count();
        
        $thisWeekVisitors = Visitor::query()
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        $blacklisted = Visitor::query()
            ->where('is_blacklisted', true)
            ->count();

        $activeVisits = Visit::query()
            ->where('status', 'approved')
            ->whereNull('checkout_time')
            ->whereNotNull('checkin_time')
            ->count();

        return [
            Stat::make('Total Visitors', number_format($totalVisitors))
                ->description($thisWeekVisitors . ' new this week')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary'),

            Stat::make('Blacklisted', number_format($blacklisted))
                ->description('Restricted visitors')
                ->descriptionIcon('heroicon-m-shield-exclamation')
                ->color($blacklisted > 0 ? 'danger' : 'success'),

            Stat::make('Active Visits', number_format($activeVisits))
                ->description('Currently on premises')
                ->descriptionIcon('heroicon-m-user-group')
                ->color($activeVisits > 0 ? 'success' : 'gray'),
        ];
    }
}
