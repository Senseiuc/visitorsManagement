<?php

namespace App\Filament\Pages;

use App\Models\Visit;
use App\Models\Visitor;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class Reports extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.reports';

    public ?array $data = [];

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-document-chart-bar';
    }

    public static function getNavigationLabel(): string
    {
        return 'Reports';
    }

    public static function getNavigationSort(): ?int
    {
        return 50;
    }

    public function mount(): void
    {
        $this->form->fill([
            'report_type' => 'visitor_activity',
            'export_format' => 'pdf',
            'date_range' => 'last_30_days',
            'date_from' => now()->subDays(30)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('report_type')
                ->label('Report Type')
                ->options([
                    'visitor_activity' => 'Visitor Activity Report',
                    'staff_visits' => 'Staff Visit Report',
                    'location_analytics' => 'Location Analytics Report',
                    'time_based' => 'Time-Based Report',
                    'compliance' => 'Compliance Report',
                ])
                ->helperText('Select the type of report you want to generate')
                ->required()
                ->native(false)
                ->columnSpanFull(),

            Select::make('date_range')
                ->label('Quick Date Range')
                ->options([
                    'today' => 'Today',
                    'yesterday' => 'Yesterday',
                    'last_7_days' => 'Last 7 Days',
                    'last_30_days' => 'Last 30 Days',
                    'this_month' => 'This Month',
                    'last_month' => 'Last Month',
                    'this_year' => 'This Year',
                    'custom' => 'Custom Range',
                ])
                ->default('last_30_days')
                ->helperText('Choose a preset date range or select "Custom Range" to specify your own dates')
                ->live()
                ->afterStateUpdated(function ($state, callable $set) {
                    $dates = match($state) {
                        'today' => [now()->startOfDay(), now()->endOfDay()],
                        'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
                        'last_7_days' => [now()->subDays(7), now()],
                        'last_30_days' => [now()->subDays(30), now()],
                        'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
                        'last_month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
                        'this_year' => [now()->startOfYear(), now()->endOfYear()],
                        default => [now()->subDays(30), now()],
                    };
                    
                    if ($state !== 'custom') {
                        $set('date_from', $dates[0]->format('Y-m-d'));
                        $set('date_to', $dates[1]->format('Y-m-d'));
                    }
                })
                ->native(false),

            Select::make('export_format')
                ->label('Export Format')
                ->options([
                    'pdf' => 'PDF Document',
                    'csv' => 'CSV / Excel Spreadsheet',
                    'html' => 'HTML (Print-Friendly)',
                ])
                ->helperText('Choose the format for your report export')
                ->required()
                ->native(false),

            DatePicker::make('date_from')
                ->label('From Date')
                ->required()
                ->native(false)
                ->maxDate(fn (callable $get) => $get('date_to') ?: now()),

            DatePicker::make('date_to')
                ->label('To Date')
                ->required()
                ->native(false)
                ->after('date_from')
                ->maxDate(now()),
        ];
    }

    protected function getFormStatePath(): string
    {
        return 'data';
    }

    protected function getFormColumns(): int
    {
        return 2;
    }

    public function generateReport()
    {
        $data = $this->form->getState();
        $reportData = $this->getReportData($data['report_type'], $data['date_from'], $data['date_to']);

        if ($data['export_format'] === 'csv') {
            return $this->exportCsv($reportData);
        } elseif ($data['export_format'] === 'html') {
            return $this->exportHtml($reportData);
        } else {
            return $this->exportPdf($reportData);
        }
    }

    protected function getReportData(string $reportType, string $dateFrom, string $dateTo): array
    {
        $user = auth()->user();
        $locationIds = $user?->accessibleLocationIds();

        return match($reportType) {
            'visitor_activity' => $this->getVisitorActivityData($locationIds, $dateFrom, $dateTo),
            'staff_visits' => $this->getStaffVisitsData($locationIds, $dateFrom, $dateTo),
            'location_analytics' => $this->getLocationAnalyticsData($locationIds, $dateFrom, $dateTo),
            'time_based' => $this->getTimeBasedData($locationIds, $dateFrom, $dateTo),
            'compliance' => $this->getComplianceData($locationIds, $dateFrom, $dateTo),
            default => [],
        };
    }

    protected function getVisitorActivityData($locationIds, $dateFrom, $dateTo): array
    {
        $query = Visit::query()->whereBetween('created_at', [$dateFrom, $dateTo]);

        if (is_array($locationIds) && !empty($locationIds)) {
            $query->whereIn('location_id', $locationIds);
        }

        return [
            'title' => 'Visitor Activity Report',
            'period' => $dateFrom . ' to ' . $dateTo,
            'total_visits' => $query->count(),
            'unique_visitors' => $query->distinct('visitor_id')->count('visitor_id'),
            'approved_visits' => $query->where('status', 'approved')->count(),
            'pending_visits' => $query->where('status', 'pending')->count(),
            'blacklisted_visitors' => Visitor::where('is_blacklisted', true)->count(),
        ];
    }

    protected function getStaffVisitsData($locationIds, $dateFrom, $dateTo): array
    {
        $query = Visit::query()
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereNotNull('staff_visited_id');

        if (is_array($locationIds) && !empty($locationIds)) {
            $query->whereIn('location_id', $locationIds);
        }

        return [
            'title' => 'Staff Visit Report',
            'period' => $dateFrom . ' to ' . $dateTo,
            'staff_visits' => $query->select('staff_visited_id', DB::raw('COUNT(*) as visit_count'))
                ->with('staff:id,name,email')
                ->groupBy('staff_visited_id')
                ->orderByDesc('visit_count')
                ->get()
                ->toArray(),
        ];
    }

    protected function getLocationAnalyticsData($locationIds, $dateFrom, $dateTo): array
    {
        $query = Visit::query()->whereBetween('created_at', [$dateFrom, $dateTo]);

        if (is_array($locationIds) && !empty($locationIds)) {
            $query->whereIn('location_id', $locationIds);
        }

        return [
            'title' => 'Location Analytics Report',
            'period' => $dateFrom . ' to ' . $dateTo,
            'location_visits' => $query->select('location_id', DB::raw('COUNT(*) as visit_count'))
                ->with('location:id,name')
                ->groupBy('location_id')
                ->orderByDesc('visit_count')
                ->get()
                ->toArray(),
        ];
    }

    protected function getTimeBasedData($locationIds, $dateFrom, $dateTo): array
    {
        $query = Visit::query()
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereNotNull('checkin_time');

        if (is_array($locationIds) && !empty($locationIds)) {
            $query->whereIn('location_id', $locationIds);
        }

        return [
            'title' => 'Time-Based Report',
            'period' => $dateFrom . ' to ' . $dateTo,
            'visits_by_hour' => $query->selectRaw('HOUR(checkin_time) as hour, COUNT(*) as count')
                ->groupBy('hour')
                ->orderBy('hour')
                ->get()
                ->toArray(),
        ];
    }

    protected function getComplianceData($locationIds, $dateFrom, $dateTo): array
    {
        $query = Visit::query();

        if (is_array($locationIds) && !empty($locationIds)) {
            $query->whereIn('location_id', $locationIds);
        }

        return [
            'title' => 'Compliance Report',
            'period' => $dateFrom . ' to ' . $dateTo,
            'pending_approvals' => $query->where('status', 'pending')->count(),
            'old_pending' => $query->where('status', 'pending')
                ->where('created_at', '<', now()->subHours(24))
                ->count(),
            'missing_checkouts' => $query->where('status', 'approved')
                ->whereNotNull('checkin_time')
                ->whereNull('checkout_time')
                ->where('checkin_time', '<', now()->subHours(12))
                ->count(),
            'blacklist_activity' => Visitor::where('is_blacklisted', true)
                ->whereBetween('updated_at', [$dateFrom, $dateTo])
                ->count(),
        ];
    }

    protected function exportCsv($data)
    {
        $filename = 'report_' . now()->format('Y-m-d_His') . '.csv';
        
        $csv = fopen('php://temp', 'r+');
        
        fputcsv($csv, [$data['title']]);
        fputcsv($csv, ['Period: ' . $data['period']]);
        fputcsv($csv, ['Generated: ' . now()->format('Y-m-d H:i:s')]);
        fputcsv($csv, []);

        if (isset($data['total_visits'])) {
            fputcsv($csv, ['Summary Statistics']);
            fputcsv($csv, ['Total Visits', $data['total_visits']]);
            fputcsv($csv, ['Unique Visitors', $data['unique_visitors'] ?? 0]);
            fputcsv($csv, ['Approved Visits', $data['approved_visits'] ?? 0]);
            fputcsv($csv, ['Pending Visits', $data['pending_visits'] ?? 0]);
            fputcsv($csv, []);
        }

        if (isset($data['staff_visits'])) {
            fputcsv($csv, ['Staff Visits']);
            fputcsv($csv, ['Staff Name', 'Email', 'Visit Count']);
            foreach ($data['staff_visits'] as $visit) {
                fputcsv($csv, [
                    $visit['staff']['name'] ?? 'N/A',
                    $visit['staff']['email'] ?? 'N/A',
                    $visit['visit_count']
                ]);
            }
            fputcsv($csv, []);
        }

        if (isset($data['location_visits'])) {
            fputcsv($csv, ['Location Analytics']);
            fputcsv($csv, ['Location', 'Visit Count']);
            foreach ($data['location_visits'] as $visit) {
                fputcsv($csv, [
                    $visit['location']['name'] ?? 'N/A',
                    $visit['visit_count']
                ]);
            }
            fputcsv($csv, []);
        }

        if (isset($data['visits_by_hour'])) {
            fputcsv($csv, ['Visits by Hour']);
            fputcsv($csv, ['Hour', 'Visit Count']);
            foreach ($data['visits_by_hour'] as $hourData) {
                fputcsv($csv, [
                    str_pad($hourData['hour'], 2, '0', STR_PAD_LEFT) . ':00',
                    $hourData['count']
                ]);
            }
            fputcsv($csv, []);
        }

        if (isset($data['pending_approvals'])) {
            fputcsv($csv, ['Compliance Metrics']);
            fputcsv($csv, ['Metric', 'Count']);
            fputcsv($csv, ['Pending Approvals', $data['pending_approvals']]);
            fputcsv($csv, ['Old Pending (>24h)', $data['old_pending'] ?? 0]);
            fputcsv($csv, ['Missing Checkouts', $data['missing_checkouts'] ?? 0]);
            fputcsv($csv, ['Blacklist Activity', $data['blacklist_activity'] ?? 0]);
        }

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return response()->streamDownload(function() use ($content) {
            echo $content;
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    protected function exportHtml($data)
    {
        $html = view('reports.template', ['data' => $data])->render();
        $filename = 'report_' . now()->format('Y-m-d_His') . '.html';
        
        return response()->streamDownload(function() use ($html) {
            echo $html;
        }, $filename, [
            'Content-Type' => 'text/html',
        ]);
    }

    protected function exportPdf($data)
    {
        try {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.template', ['data' => $data]);
            $filename = 'report_' . now()->format('Y-m-d_His') . '.pdf';
            
            return response()->streamDownload(function() use ($pdf) {
                echo $pdf->output();
            }, $filename, [
                'Content-Type' => 'application/pdf',
            ]);
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error Generating PDF')
                ->body('There was an error generating the PDF report: ' . $e->getMessage())
                ->danger()
                ->send();
                
            return null;
        }
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->isAdmin() || $user->isSuperAdmin());
    }
}
