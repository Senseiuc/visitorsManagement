<?php

namespace App\Filament\Widgets;

use App\Models\Visit;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PeakVisitTimesChart extends ChartWidget
{
    protected ?string $heading = 'Peak Visit Times';
    protected static ?int $sort = 6;
    protected ?string $pollingInterval = '60s';

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && ($user->isAdmin() || $user->isSuperAdmin());
    }

    protected function getData(): array
    {
        $user = auth()->user();
        $locationIds = $user?->accessibleLocationIds();

        // Get visit counts by hour of day
        $query = Visit::query()
            ->selectRaw('HOUR(checkin_time) as hour, COUNT(*) as count')
            ->whereNotNull('checkin_time')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('hour')
            ->orderBy('hour');

        // Apply location scoping for non-superadmins
        if (is_array($locationIds) && !empty($locationIds)) {
            $query->whereIn('location_id', $locationIds);
        }

        $data = $query->get();

        // Fill in missing hours with 0
        $hourCounts = array_fill(0, 24, 0);
        foreach ($data as $row) {
            $hourCounts[$row->hour] = $row->count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Visits',
                    'data' => array_values($hourCounts),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                ],
            ],
            'labels' => array_map(fn($h) => sprintf('%02d:00', $h), range(0, 23)),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
