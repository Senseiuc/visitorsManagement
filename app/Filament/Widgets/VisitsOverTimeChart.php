<?php

namespace App\Filament\Widgets;

use App\Models\Visit;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class VisitsOverTimeChart extends ChartWidget
{
    protected ?string $heading = 'Visits Over Last 30 Days';
    protected static ?int $sort = 4;
    protected ?string $pollingInterval = '60s';

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && ($user->isSuperAdmin() || $user->isAdmin());
    }

    protected function getData(): array
    {
        $auth = auth()->user();
        $ids = $auth?->accessibleLocationIds();

        $query = Visit::query();

        // Apply location scoping
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

        $data = Trend::query($query)
            ->between(
                start: now()->subDays(30),
                end: now(),
            )
            ->perDay()
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Visits',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => $value->date),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
