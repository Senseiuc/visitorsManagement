<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Role;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $auth = auth()->user();
        if ($auth && $auth->isAdmin()) {
            // Admins cannot change roles; will enforce after save to receptionist only
            $data['assigned_location_id'] = $auth->assigned_location_id;
            // Limit permissions to subset the admin already has (effective)
            $allowed = collect($auth->effectivePermissions())->flip();
            $data['permissions'] = collect($data['permissions'] ?? [])->filter(fn ($p) => $allowed->has($p))->values()->all();
            unset($data['role_id']);
        }

        // Prevent empty password update
        if (empty($data['password'] ?? null)) {
            unset($data['password']);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $auth = auth()->user();
        if ($auth && $auth->isAdmin()) {
            // Ensure only receptionist role remains when edited by admin
            $receptionistId = Role::query()->where('slug', 'receptionist')->value('id');
            if ($receptionistId) {
                $this->record->roles()->sync([$receptionistId]);
                $this->record->forceFill(['role_id' => $receptionistId])->save();
            }
        }
    }
}
