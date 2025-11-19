<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\NotCheckedOutWidget;
use App\Filament\Widgets\RecentVisitorsWidget;
use App\Filament\Widgets\ReceptionistStats;
use App\Filament\Widgets\VisitsAwaitingApprovalWidget;
use BackedEnum;
use Filament\Pages\Dashboard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Throwable;

class ReceptionistDashboard extends Dashboard
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationLabel = 'Reception Desk';

    protected static ?string $title = 'Reception Desk';

    protected static ?string $slug = 'reception';

    protected static ?int $navigationSort = 1;

    protected function getHeaderWidgets(): array
    {
        return [
            ReceptionistStats::class,
        ];
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        // Allow superadmins/admins, receptionists, or anyone with visits.view permission
        return $user->isSuperAdmin() || $user->isAdmin() || $user->isReceptionist() || $user->hasPermission('visits.view');
    }

    public static function shouldRegisterNavigation(): bool
    {
        if (! static::canAccess()) {
            return false;
        }

        // Only register in navigation if the route exists to avoid RouteNotFoundException during nav rendering
        try {
            return Route::has('filament.admin.pages.reception');
        } catch (Throwable $e) {
            return false;
        }
    }

    public function getWidgets(): array
    {
        return [
            VisitsAwaitingApprovalWidget::class,
            NotCheckedOutWidget::class,
            RecentVisitorsWidget::class,
        ];
    }
}
