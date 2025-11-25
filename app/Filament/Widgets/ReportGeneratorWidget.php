<?php

namespace App\Filament\Widgets;

use App\Models\Visit;
use App\Models\Visitor;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class ReportGeneratorWidget extends Widget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && ($user->isAdmin() || $user->isSuperAdmin());
    }

    public $reportType = 'visitor_activity';
    public $dateFrom;
    public $dateTo;
    public $exportFormat = 'pdf';

    public function mount(): void
    {
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('filament.widgets.report-generator');
    }

    public function generateReport()
    {
        $this->validate([
            'reportType' => 'required',
            'dateFrom' => 'required|date',
            'dateTo' => 'required|date|after_or_equal:dateFrom',
            'exportFormat' => 'required|in:pdf,csv,html',
        ]);

        $data = $this->getReportData();

        if ($this->exportFormat === 'csv') {
            return $this->exportCsv($data);
        } elseif ($this->exportFormat === 'html') {
            return $this->exportHtml($data);
        } else {
            return $this->exportPdf($data);
        }
    }

    protected function getReportData(): array
    {
        $user = auth()->user();
        $locationIds = $user?->accessibleLocationIds();

        return match($this->reportType) {
            'visitor_activity' => $this->getVisitorActivityData($locationIds),
            'staff_visits' => $this->getStaffVisitsData($locationIds),
            'location_analytics' => $this->getLocationAnalyticsData($locationIds),
            'time_based' => $this->getTimeBasedData($locationIds),
            'compliance' => $this->getComplianceData($locationIds),
            default => [],
        };
    }

    protected function getVisitorActivityData($locationIds): array
    {
        $query = Visit::query()
            ->whereBetween('created_at', [$this->dateFrom, $this->dateTo]);

        if (is_array($locationIds) && !empty($locationIds)) {
            $query->whereIn('location_id', $locationIds);
        }

        return [
            'title' => 'Visitor Activity Report',
            'period' => $this->dateFrom . ' to ' . $this->dateTo,
            'total_visits' => $query->count(),
            'unique_visitors' => $query->distinct('visitor_id')->count('visitor_id'),
            'approved_visits' => $query->where('status', 'approved')->count(),
            'pending_visits' => $query->where('status', 'pending')->count(),
            'blacklisted_visitors' => Visitor::where('is_blacklisted', true)->count(),
            'visits_by_day' => $query->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->toArray(),
        ];
    }

    protected function getStaffVisitsData($locationIds): array
    {
        $query = Visit::query()
            ->whereBetween('created_at', [$this->dateFrom, $this->dateTo])
            ->whereNotNull('staff_visited_id');

        if (is_array($locationIds) && !empty($locationIds)) {
            $query->whereIn('location_id', $locationIds);
        }

        return [
            'title' => 'Staff Visit Report',
            'period' => $this->dateFrom . ' to ' . $this->dateTo,
            'staff_visits' => $query->select('staff_visited_id', DB::raw('COUNT(*) as visit_count'))
                ->with('staff:id,name,email')
                ->groupBy('staff_visited_id')
                ->orderByDesc('visit_count')
                ->get()
                ->toArray(),
        ];
    }

    protected function getLocationAnalyticsData($locationIds): array
    {
        $query = Visit::query()
            ->whereBetween('created_at', [$this->dateFrom, $this->dateTo]);

        if (is_array($locationIds) && !empty($locationIds)) {
            $query->whereIn('location_id', $locationIds);
        }

        return [
            'title' => 'Location Analytics Report',
            'period' => $this->dateFrom . ' to ' . $this->dateTo,
            'location_visits' => $query->select('location_id', DB::raw('COUNT(*) as visit_count'))
                ->with('location:id,name')
                ->groupBy('location_id')
                ->orderByDesc('visit_count')
                ->get()
                ->toArray(),
        ];
    }

    protected function getTimeBasedData($locationIds): array
    {
        $query = Visit::query()
            ->whereBetween('created_at', [$this->dateFrom, $this->dateTo])
            ->whereNotNull('checkin_time');

        if (is_array($locationIds) && !empty($locationIds)) {
            $query->whereIn('location_id', $locationIds);
        }

        return [
            'title' => 'Time-Based Report',
            'period' => $this->dateFrom . ' to ' . $this->dateTo,
            'visits_by_hour' => $query->selectRaw('HOUR(checkin_time) as hour, COUNT(*) as count')
                ->groupBy('hour')
                ->orderBy('hour')
                ->get()
                ->toArray(),
        ];
    }

    protected function getComplianceData($locationIds): array
    {
        $query = Visit::query();

        if (is_array($locationIds) && !empty($locationIds)) {
            $query->whereIn('location_id', $locationIds);
        }

        return [
            'title' => 'Compliance Report',
            'period' => $this->dateFrom . ' to ' . $this->dateTo,
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
                ->whereBetween('updated_at', [$this->dateFrom, $this->dateTo])
                ->count(),
        ];
    }

    protected function exportCsv($data)
    {
        $filename = 'report_' . $this->reportType . '_' . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            
            // Write report header
            fputcsv($file, [$data['title']]);
            fputcsv($file, ['Period: ' . $data['period']]);
            fputcsv($file, ['Generated: ' . now()->format('Y-m-d H:i:s')]);
            fputcsv($file, []); // Empty line

            // Write data based on report type
            if (isset($data['total_visits'])) {
                fputcsv($file, ['Summary Statistics']);
                fputcsv($file, ['Total Visits', $data['total_visits']]);
                fputcsv($file, ['Unique Visitors', $data['unique_visitors'] ?? 0]);
                fputcsv($file, ['Approved Visits', $data['approved_visits'] ?? 0]);
                fputcsv($file, ['Pending Visits', $data['pending_visits'] ?? 0]);
                fputcsv($file, []);
            }

            if (isset($data['staff_visits'])) {
                fputcsv($file, ['Staff Name', 'Email', 'Visit Count']);
                foreach ($data['staff_visits'] as $visit) {
                    fputcsv($file, [
                        $visit['staff']['name'] ?? 'N/A',
                        $visit['staff']['email'] ?? 'N/A',
                        $visit['visit_count']
                    ]);
                }
            }

            if (isset($data['location_visits'])) {
                fputcsv($file, ['Location', 'Visit Count']);
                foreach ($data['location_visits'] as $visit) {
                    fputcsv($file, [
                        $visit['location']['name'] ?? 'N/A',
                        $visit['visit_count']
                    ]);
                }
            }

            if (isset($data['visits_by_hour'])) {
                fputcsv($file, ['Hour', 'Visit Count']);
                foreach ($data['visits_by_hour'] as $hourData) {
                    fputcsv($file, [
                        str_pad($hourData['hour'], 2, '0', STR_PAD_LEFT) . ':00',
                        $hourData['count']
                    ]);
                }
            }

            if (isset($data['pending_approvals'])) {
                fputcsv($file, ['Compliance Metrics']);
                fputcsv($file, ['Pending Approvals', $data['pending_approvals']]);
                fputcsv($file, ['Old Pending (>24h)', $data['old_pending'] ?? 0]);
                fputcsv($file, ['Missing Checkouts', $data['missing_checkouts'] ?? 0]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function exportHtml($data)
    {
        $html = view('reports.template', ['data' => $data])->render();
        
        return response($html)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', 'inline; filename="report_' . $this->reportType . '_' . now()->format('Y-m-d_His') . '.html"');
    }

    protected function exportPdf($data)
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.template', ['data' => $data]);
        
        return response()->streamDownload(function() use ($pdf) {
            echo $pdf->output();
        }, 'report_' . $this->reportType . '_' . now()->format('Y-m-d_His') . '.pdf');
    }
}
