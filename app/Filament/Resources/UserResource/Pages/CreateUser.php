<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Location;
use App\Models\Role;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $auth = auth()->user();
        if ($auth && $auth->isAdmin()) {
            // Force constraints for admins
            $data['assigned_location_id'] = $auth->assigned_location_id; // legacy fallback
            $data['created_by_user_id'] = $auth->id;
            // Limit permissions to subset of admin's effective permissions
            $allowed = collect($auth->effectivePermissions())->flip();
            $data['permissions'] = collect($data['permissions'] ?? [])->filter(fn ($p) => $allowed->has($p))->values()->all();
            // Roles will be enforced in afterCreate
            unset($data['role_id']);
        }

        if ($auth && $auth->isSuperAdmin()) {
            // Track creator for auditing
            $data['created_by_user_id'] = $auth->id;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $auth = auth()->user();
        
        // Sync locations from form data (many-to-many relationship)
        if (isset($this->data['locations']) && is_array($this->data['locations'])) {
            $this->record->locations()->sync($this->data['locations']);
        }
        
        if ($auth && $auth->isAdmin()) {
            // Ensure only receptionist role is assigned when created by admin
            $receptionistId = Role::query()->where('slug', 'receptionist')->value('id');
            if ($receptionistId) {
                $this->record->roles()->sync([$receptionistId]);
                // keep legacy role_id in sync if present
                $this->record->forceFill(['role_id' => $receptionistId])->save();
            }
        }
    }

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $canImport = $user && ($user->isSuperAdmin() || $user->hasPermission('users.create'));

        if (! $canImport) {
            return parent::getHeaderActions();
        }

        return array_merge(parent::getHeaderActions(), [
            Actions\Action::make('downloadTemplate')
                ->label('Download Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () use ($user) {
                    $headers = [
                        'staff name', 'staff id', 'dept', 'floor', 'intercom', 'gsm', 'email address', 'role',
                    ];
                    if ($user->isSuperAdmin()) {
                        $headers[] = 'location';
                    }
                    $csv = implode(',', array_map(fn ($h) => '"' . $h . '"', $headers)) . "\n";
                    $csv .= '"Jane Doe","EMP001","Sales","3","1234","08012345678","jane.doe@example.com","receptionist"';
                    if ($user->isSuperAdmin()) {
                        $csv .= ',"HQ"';
                    }
                    $csv .= "\n";

                    return response()->streamDownload(function () use ($csv) {
                        echo $csv;
                    }, 'users-import-template.csv', [
                        'Content-Type' => 'text/csv',
                    ]);
                }),

            Actions\Action::make('bulkImport')
                ->label('Bulk Import Users')
                ->icon('heroicon-o-document-arrow-up')
                ->color('primary')
                ->form([
                    Forms\Components\FileUpload::make('import_file')
                        ->label('Excel/CSV file')
                        ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                        ->directory('imports')
                        ->disk('local')
                        ->required()
                        ->helperText('Headers required: staff name, staff id (optional), dept, floor, intercom, gsm, email address, role' . ($user->isSuperAdmin() ? ', location (name or UUID)' : '') . '. Use CSV for best compatibility.'),
                ])
                ->action(function (array $data) use ($user) {
                    $path = $data['import_file'] ?? null;
                    if (! $path) {
                        Notification::make()->title('No file uploaded')->danger()->send();
                        return;
                    }

                    $fullPath = Storage::disk('local')->path($path);
                    $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

                    if ($ext !== 'csv') {
                        Notification::make()
                            ->title('Only CSV is supported in this build')
                            ->body('Please upload a .csv file saved from Excel. (XLSX support can be enabled later).')
                            ->warning()
                            ->send();
                        return;
                    }

                    [$summary, $failures] = $this->processCsvImport($fullPath, $user);

                    $message = "Created: {$summary['created']}, Updated: {$summary['updated']}, Failed: {$summary['failed']}";
                    $note = Notification::make()->title('Import finished')->body($message)->success();

                    if ($summary['failed'] > 0) {
                        $failCsv = $this->buildFailuresCsv($failures);
                        $failPath = 'imports/failed_rows_' . now()->format('Ymd_His') . '.csv';
                        Storage::disk('local')->put($failPath, $failCsv);
                        $note->actions([
                            Actions\Action::make('downloadFailures')
                                ->label('Download failures CSV')
                                ->icon('heroicon-o-arrow-down-tray')
                                ->url(fn () => route('files.local', ['path' => $failPath]))
                                ->openUrlInNewTab(),
                        ]);
                    }

                    $note->send();
                })
                ->modalSubmitActionLabel('Import'),
        ]);
    }

    /**
     * @return array{0: array{created:int,updated:int,failed:int}, 1: array<int, array<string,string>>}
     */
    protected function processCsvImport(string $fullPath, User $actor): array
    {
        $created = $updated = $failed = 0;
        $failures = [];

        if (! is_readable($fullPath)) {
            return [[ 'created' => 0, 'updated' => 0, 'failed' => 1 ], [ [ 'error' => 'File not readable' ] ]];
        }

        if (($handle = fopen($fullPath, 'r')) === false) {
            return [[ 'created' => 0, 'updated' => 0, 'failed' => 1 ], [ [ 'error' => 'Unable to open uploaded file' ] ]];
        }

        $headers = [];
        $rowIndex = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $rowIndex++;
            
            // Convert encoding to UTF-8 if needed
            $row = array_map(function ($value) {
                if ($value === null || $value === '') {
                    return $value;
                }
                // Detect encoding and convert to UTF-8
                $encoding = mb_detect_encoding($value, ['UTF-8', 'Windows-1252', 'ISO-8859-1', 'ASCII'], true);
                if ($encoding && $encoding !== 'UTF-8') {
                    return mb_convert_encoding($value, 'UTF-8', $encoding);
                }
                return $value;
            }, $row);
            
            if ($rowIndex === 1) {
                $headers = array_map(fn ($h) => Str::of($h)->lower()->trim()->toString(), $row);
                continue;
            }

            if (count(array_filter($row, fn ($v) => $v !== null && $v !== '')) === 0) {
                continue; // skip blank row
            }

            $data = [];
            foreach ($headers as $i => $key) {
                $data[$key] = $row[$i] ?? '';
            }

            $name = trim((string)($data['staff name'] ?? ''));
            $staffId = trim((string)($data['staff id'] ?? ''));
            $email = trim((string)($data['email address'] ?? ''));
            $roleText = trim((string)($data['role'] ?? ''));
            $intercom = trim((string)($data['intercom'] ?? ''));
            $gsm = trim((string)($data['gsm'] ?? ''));
            $locationName = trim((string)($data['location'] ?? ''));

            // Basic validation
            $errors = [];
            if ($name === '') { $errors[] = 'staff name is required'; }
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'valid email address is required'; }
            if ($actor->isSuperAdmin() && $locationName === '') { $errors[] = 'location is required for superadmin import'; }

            // Resolve role if provided
            $roleId = null;
            if ($roleText !== '') {
                $role = Role::query()
                    ->where('slug', Str::slug($roleText))
                    ->orWhereRaw('LOWER(name) = ?', [strtolower($roleText)])
                    ->first();
                if ($role) {
                    $roleId = $role->id;
                } else {
                    $errors[] = 'role not found: ' . $roleText;
                }
            }

            // Resolve location if needed
            $assignedLocationId = null;
            if ($locationName !== '') {
                // Try to match by UUID first, then by name
                $loc = Location::query()
                    ->where('uuid', $locationName)
                    ->orWhereRaw('LOWER(name) = ?', [strtolower($locationName)])
                    ->first();
                if ($loc) {
                    $assignedLocationId = $loc->id;
                } else {
                    $errors[] = 'location not found: ' . $locationName;
                }
            } elseif (! $actor->isSuperAdmin()) {
                // default to actor's assigned location if available
                $assignedLocationId = $actor->assigned_location_id ?: null;
            }

            // Check for duplicate staff_id (only if it would conflict with a different user)
            if ($staffId !== '') {
                $existingStaffId = User::query()
                    ->where('staff_id', $staffId)
                    ->where('email', '!=', $email)
                    ->exists();
                if ($existingStaffId) {
                    $errors[] = 'staff_id already exists for another user: ' . $staffId;
                }
            }

            if (! empty($errors)) {
                $failed++;
                $failures[] = array_merge($data, [ 'error' => implode('; ', $errors) ]);
                continue;
            }

            // Find existing user by email or create new
            $user = User::query()->where('email', $email)->first();
            $isNew = false;
            if (! $user) {
                $user = new User();
                $user->email = $email;
                $user->password = bcrypt(Str::random(12));
                $isNew = true;
            }

            // Update user fields
            $user->name = $name;
            if ($staffId !== '') { $user->staff_id = $staffId; }
            if ($intercom !== '') { $user->intercom = $intercom; }
            if ($gsm !== '') { $user->phone_number = $gsm; }
            if ($assignedLocationId) { $user->assigned_location_id = $assignedLocationId; }
            
            try {
                $user->save();
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                $failed++;
                $failures[] = array_merge($data, [ 'error' => 'Database constraint violation: ' . $e->getMessage() ]);
                continue;
            }

            // Sync locations (many-to-many relationship)
            if ($assignedLocationId) {
                $user->locations()->sync([$assignedLocationId]);
            }

            if ($roleId) {
                $user->roles()->syncWithoutDetaching([$roleId]);
                $user->forceFill(['role_id' => $roleId])->save();
            }

            if ($isNew) { $created++; } else { $updated++; }
        }

        fclose($handle);

        return [[ 'created' => $created, 'updated' => $updated, 'failed' => $failed ], $failures];
    }

    /**
     * @param array<int, array<string,string>> $failures
     */
    protected function buildFailuresCsv(array $failures): string
    {
        if (empty($failures)) {
            return "";
        }
        $headers = array_keys($failures[0]);
        $out = fopen('php://temp', 'r+');
        fputcsv($out, $headers);
        foreach ($failures as $row) {
            $ordered = [];
            foreach ($headers as $h) { $ordered[] = $row[$h] ?? ''; }
            fputcsv($out, $ordered);
        }
        rewind($out);
        return stream_get_contents($out) ?: '';
    }
}
