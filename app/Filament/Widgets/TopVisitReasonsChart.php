<?php

namespace App\Filament\Widgets;

use App\Models\Visit;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TopVisitReasonsChart extends ChartWidget
{
    protected static ?string $heading = 'Top Visit Reasons';
    protected static ?int $sort = 5;
    protected static ?string $pollingInterval = '60s';

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && ($user->isSuperAdmin() || $user->isAdmin());
    }

    protected function getData(): array
    {
        $auth = auth()->user();
        $ids = $auth?->accessibleLocationIds();

        $query = Visit::query()
            ->select('reason_for_visit_id', DB::raw('count(*) as count'))
            ->with('reason')
            ->groupBy('reason_for_visit_id')
            ->orderByDesc('count')
            ->limit(5);

        // Apply location scoping
        if (is_array($ids) && !empty($ids)) {
            $query->where(function ($q) use ($ids) {
                $q->whereHas('staff.locations', function ($qr) use ($ids) {
                    $qr->whereIn('locations.id', $ids);
                })->orWhereHas('staff', function ($qr) use ($ids) {
                    $qr->whereIn('assigned_location_id', $ids);
                });
            });
        }

        $results = $query->get();

        $labels = $results->map(fn ($item) => $item->reason?->name ?? 'Unknown')->toArray();
        $data = $results->pluck('count')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Visits',
                    'data' => $data,
                    'backgroundColor' => [
                        '#3b82f6',
                        '#10b981',
                        '#f59e0b',
                        '#ef4444',
                        '#8b5cf6',
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
