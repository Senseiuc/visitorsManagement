<?php

namespace App\Filament\Resources\VisitorResource\Pages;

use App\Filament\Resources\VisitorResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewVisitor extends ViewRecord
{
    protected static string $resource = VisitorResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        $user = Auth::user();
        return $user?->hasPermission('visitors.view') ?? false;
    }
}
