<?php

namespace App\Providers\Filament;

use App\Filament\Pages\ReceptionistDashboard;
use App\Filament\Pages\Profile;
use App\Filament\Widgets\NotCheckedOutWidget;
use App\Filament\Widgets\RecentVisitorsWidget;
use App\Filament\Widgets\ReceptionistStats;
use App\Filament\Widgets\VisitorStatsWidget;
use App\Filament\Widgets\VisitsAwaitingApprovalWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Throwable;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::hex('#642d86'),
            ])
            ->brandName('Visitor Management')
            ->brandLogo(asset('images/image.png'))
            ->brandLogoHeight('2.5rem')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
                ReceptionistDashboard::class,
            ])
            ->homeUrl(function () {
                $user = auth()->user();
                return Dashboard::getUrl();
            })
//            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                // Admin/SuperAdmin Widgets
                \App\Filament\Widgets\VisitorStatsWidget::class,
                \App\Filament\Widgets\TodayVisitsStatsWidget::class,
                \App\Filament\Widgets\VisitsOverTimeChart::class,
                \App\Filament\Widgets\TopVisitReasonsChart::class,
                \App\Filament\Widgets\PeakVisitTimesChart::class,
                \App\Filament\Widgets\AverageVisitDurationChart::class,
                \App\Filament\Widgets\CheckedOutVisitsWidget::class,
                \App\Filament\Widgets\RecentVisitorsWidget::class,
                
                // Receptionist Widgets
                ReceptionistStats::class,
                VisitsAwaitingApprovalWidget::class,
                NotCheckedOutWidget::class,
            ])
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->label('My Profile')
                    ->icon('heroicon-o-user-circle')
                    ->url(fn () => Profile::getUrl()),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
