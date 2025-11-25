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
        $auth = auth()->user();
        $ids = $auth?->accessibleLocationIds();

        // Helper to apply location scoping
        $applyLocationScope = function ($query) use ($ids) {
            if (is_array($ids) && !empty($ids)) {
                $query->whereHas('visits', function ($q) use ($ids) {
                    $q->where(function ($subQ) use ($ids) {
                        // Check if visit's location_id matches accessible locations
                        $subQ->whereIn('location_id', $ids)
                             ->orWhereHas('staff.locations', function ($qr) use ($ids) {
                                 $qr->whereIn('locations.id', $ids);
                             })
                             ->orWhereHas('staff', function ($qr) use ($ids) {
                                 $qr->whereIn('assigned_location_id', $ids);
                             });
                    });
                });
            }
        };

        // Helper for visits scope directly
        $applyVisitScope = function ($query) use ($ids) {
            if (is_array($ids) && !empty($ids)) {
                $query->where(function ($q) use ($ids) {
                    // Check if visit's location_id matches accessible locations
                    $q->whereIn('location_id', $ids)
                      ->orWhereHas('staff.locations', function ($qr) use ($ids) {
                          $qr->whereIn('locations.id', $ids);
                      })
                      ->orWhereHas('staff', function ($qr) use ($ids) {
                          $qr->whereIn('assigned_location_id', $ids);
                      });
                });
            }
        };

        $totalVisitors = Visitor::query()
            ->tap($applyLocationScope)
            ->count();
        
        $thisWeekVisitors = Visitor::query()
            ->tap($applyLocationScope)
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        $blacklisted = Visitor::query()
            ->tap($applyLocationScope)
            ->where('is_blacklisted', true)
            ->count();

        $activeVisits = Visit::query()
            ->tap($applyVisitScope)
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
