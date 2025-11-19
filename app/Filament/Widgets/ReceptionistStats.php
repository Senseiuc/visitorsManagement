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
        return $user && ($user->isReceptionist() || $user->isAdmin() || $user->isSuperAdmin());
    }

    protected function getStats(): array
    {
        $auth = auth()->user();
        $ids = $auth?->accessibleLocationIds(); // null = no restriction (superadmin), [] = none

        // Helper to apply location scoping via the staff's locations or assigned location
        $applyLocationScope = function ($query) use ($ids) {
            if (is_array($ids) && ! empty($ids)) {
                $query->where(function ($q) use ($ids) {
                    $q->whereHas('staff.locations', function ($qr) use ($ids) {
                        $qr->whereIn('locations.id', $ids);
                    })->orWhereHas('staff', function ($qr) use ($ids) {
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

        // Currently on-site: not checked-out
        $checkedIn = Visit::query()
            ->tap($applyLocationScope)
            ->whereNull('checkout_time')
            ->count();

        // Today's visits (by checkin_time date)
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
