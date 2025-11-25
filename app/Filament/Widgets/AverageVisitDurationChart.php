<?php

namespace App\Filament\Widgets;

use App\Models\Visit;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class AverageVisitDurationChart extends ChartWidget
{
    protected ?string $heading = 'Average Visit Duration';
    protected static ?int $sort = 7;
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

        // Get average duration by day (in minutes)
        $query = Visit::query()
            ->selectRaw('DATE(checkin_time) as date, AVG(TIMESTAMPDIFF(MINUTE, checkin_time, checkout_time)) as avg_duration')
            ->whereNotNull('checkin_time')
            ->whereNotNull('checkout_time')
            ->where('checkin_time', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date');

        // Apply location scoping for non-superadmins
        if (is_array($locationIds) && !empty($locationIds)) {
            $query->whereIn('location_id', $locationIds);
        }

        $data = $query->get();

        $labels = [];
        $durations = [];

        foreach ($data as $row) {
            $labels[] = \Carbon\Carbon::parse($row->date)->format('M d');
            $durations[] = round($row->avg_duration ?? 0, 2);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Avg Duration (minutes)',
                    'data' => $durations,
                    'fill' => false,
                    'borderColor' => 'rgb(16, 185, 129)',
                    'tension' => 0.1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
