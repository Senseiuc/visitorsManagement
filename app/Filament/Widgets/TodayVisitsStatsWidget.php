<?php

namespace App\Filament\Widgets;

use App\Models\Visit;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TodayVisitsStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        return auth()->check();
    }

    protected function getStats(): array
    {
        $auth = auth()->user();
        $ids = $auth?->accessibleLocationIds();

        // Helper to apply location scoping
        $applyLocationScope = function ($query) use ($ids) {
            if (is_array($ids) && !empty($ids)) {
                $query->where(function ($q) use ($ids) {
                    $q->whereHas('staff.locations', function ($qr) use ($ids) {
                        $qr->whereIn('locations.id', $ids);
                    })->orWhereHas('staff', function ($qr) use ($ids) {
                        $qr->whereIn('assigned_location_id', $ids);
                    });
                });
            }
        };

        // Today's total visits
        $todayTotal = Visit::query()
            ->tap($applyLocationScope)
            ->whereDate('created_at', today())
            ->count();

        // Today's approved visits
        $todayApproved = Visit::query()
            ->tap($applyLocationScope)
            ->whereDate('created_at', today())
            ->where('status', 'approved')
            ->count();

        // Today's pending visits
        $todayPending = Visit::query()
            ->tap($applyLocationScope)
            ->whereDate('created_at', today())
            ->where('status', 'pending')
            ->count();

        // Currently on-site
        $onSite = Visit::query()
            ->tap($applyLocationScope)
            ->where('status', 'approved')
            ->whereNull('checkout_time')
            ->whereNotNull('checkin_time')
            ->count();

        return [
            Stat::make("Today's Visits", number_format($todayTotal))
                ->description($todayApproved . ' approved, ' . $todayPending . ' pending')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary'),

            Stat::make('Pending Approval', number_format($todayPending))
                ->description('Awaiting approval')
                ->descriptionIcon('heroicon-m-clock')
                ->color($todayPending > 0 ? 'warning' : 'success'),

            Stat::make('Currently On-Site', number_format($onSite))
                ->description('Checked in, not checked out')
                ->descriptionIcon('heroicon-m-user-group')
                ->color($onSite > 0 ? 'success' : 'gray'),
        ];
    }
}
