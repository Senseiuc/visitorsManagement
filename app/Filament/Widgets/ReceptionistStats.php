<?php

namespace App\Filament\Widgets;

use App\Models\Visit;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ReceptionistStats extends BaseWidget
{
    protected static ?int $sort = 0;
    protected ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        $user = auth()->user();
        // Only show to receptionists (operational stats)
        return $user && $user->isReceptionist();
    }

    protected function getStats(): array
    {
        $auth = auth()->user();
        $ids = $auth?->accessibleLocationIds(); // null = no restriction (superadmin), [] = none

        // Helper to apply location scoping via visit's location_id or staff's locations
        $applyLocationScope = function ($query) use ($ids) {
            if (is_array($ids) && ! empty($ids)) {
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
        };

        // Pending approvals: visits with status = 'pending'
        $pending = Visit::query()
            ->tap($applyLocationScope)
            ->where('status', 'pending')
            ->count();

        // Currently on-site: approved, checked-in, and not checked-out
        $checkedIn = Visit::query()
            ->tap($applyLocationScope)
            ->where('status', 'approved')
            ->whereNotNull('checkin_time')
            ->whereNull('checkout_time')
            ->count();

        // Today's visits (by checkin_time date - when visitor actually checked in)
        $today = Visit::query()
            ->tap($applyLocationScope)
            ->whereDate('checkin_time', now()->toDateString())
            ->count();

        return [
            Stat::make('Pending Approvals', number_format($pending))
                ->icon('heroicon-o-hand-raised')
                ->color($pending > 0 ? 'warning' : 'gray'),

            Stat::make('Currently Checked-In', number_format($checkedIn))
                ->icon('heroicon-o-clock')
                ->color($checkedIn > 0 ? 'primary' : 'gray'),

            Stat::make("Today's Visits", number_format($today))
                ->icon('heroicon-o-calendar-days')
                ->color('success'),
        ];
    }
}
