<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $data['title'] }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; border-bottom: 2px solid #f59e0b; padding-bottom: 10px; }
        .meta { color: #666; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f59e0b; color: white; }
        .stat-box { display: inline-block; padding: 15px; margin: 10px; background: #f3f4f6; border-radius: 8px; }
        .stat-label { font-size: 12px; color: #666; }
        .stat-value { font-size: 24px; font-weight: bold; color: #333; }
    </style>
</head>
<body>
    <h1>{{ $data['title'] }}</h1>
    <div class="meta">
        <strong>Period:</strong> {{ $data['period'] }}<br>
        <strong>Generated:</strong> {{ now()->format('Y-m-d H:i:s') }}
    </div>

    @if(isset($data['total_visits']))
        <h2>Summary Statistics</h2>
        <div class="stat-box">
            <div class="stat-label">Total Visits</div>
            <div class="stat-value">{{ number_format($data['total_visits']) }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Unique Visitors</div>
            <div class="stat-value">{{ number_format($data['unique_visitors'] ?? 0) }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Approved</div>
            <div class="stat-value">{{ number_format($data['approved_visits'] ?? 0) }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Pending</div>
            <div class="stat-value">{{ number_format($data['pending_visits'] ?? 0) }}</div>
        </div>
    @endif

    @if(isset($data['staff_visits']))
        <h2>Staff Visits</h2>
        <table>
            <thead>
                <tr>
                    <th>Staff Name</th>
                    <th>Email</th>
                    <th>Visit Count</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['staff_visits'] as $visit)
                    <tr>
                        <td>{{ $visit['staff']['name'] ?? 'N/A' }}</td>
                        <td>{{ $visit['staff']['email'] ?? 'N/A' }}</td>
                        <td>{{ $visit['visit_count'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if(isset($data['location_visits']))
        <h2>Location Analytics</h2>
        <table>
            <thead>
                <tr>
                    <th>Location</th>
                    <th>Visit Count</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['location_visits'] as $visit)
                    <tr>
                        <td>{{ $visit['location']['name'] ?? 'N/A' }}</td>
                        <td>{{ $visit['visit_count'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if(isset($data['visits_by_hour']))
        <h2>Visits by Hour</h2>
        <table>
            <thead>
                <tr>
                    <th>Hour</th>
                    <th>Visit Count</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['visits_by_hour'] as $hourData)
                    <tr>
                        <td>{{ str_pad($hourData['hour'], 2, '0', STR_PAD_LEFT) }}:00</td>
                        <td>{{ $hourData['count'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if(isset($data['pending_approvals']))
        <h2>Compliance Metrics</h2>
        <div class="stat-box">
            <div class="stat-label">Pending Approvals</div>
            <div class="stat-value">{{ number_format($data['pending_approvals']) }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Old Pending (>24h)</div>
            <div class="stat-value">{{ number_format($data['old_pending'] ?? 0) }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Missing Checkouts</div>
            <div class="stat-value">{{ number_format($data['missing_checkouts'] ?? 0) }}</div>
        </div>
    @endif
</body>
</html>
