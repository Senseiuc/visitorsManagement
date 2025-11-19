<?php

namespace App\Filament\Pages;

use App\Models\Location;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class Profile extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $slug = 'profile';

    protected static ?string $title = 'My Profile';


    protected static ?string $navigationLabel = 'My Profile';

    protected static ?int $navigationSort = 1000;

    public ?array $data = [];

    protected string $view = 'filament.pages.profile';

    public static function canAccess(): bool
    {
        return Auth::check();
    }

    public static function shouldRegisterNavigation(): bool
    {
        // We won't place Profile in the main sidebar; it will appear in the user menu instead.
        return false;
    }

    public function mount(): void
    {
        $user = Auth::user();
        abort_unless(!empty($user), 403);

        $this->form->fill([
            'name' => $user->name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'intercom' => $user->intercom,
            'locations' => $user->locations()->pluck('locations.id')->all(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $user = Auth::user();

        return $schema
            ->schema([
                Section::make('Personal Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->disabled()
                            ->required(),

                        Forms\Components\TextInput::make('phone_number')
                            ->label('Phone Number')
                            ->tel()
                            ->maxLength(50),

                        Forms\Components\TextInput::make('intercom')
                            ->label('Intercom')
                            ->maxLength(50),
                    ])
                    ->columns(2),

                Section::make('Change Password')
                    ->schema([
                        Forms\Components\TextInput::make('current_password')
                            ->password()
                            ->revealable()
                            ->label('Current Password')
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('new_password')
                            ->password()
                            ->revealable()
                            ->label('New Password')
                            ->dehydrated(false)
                            ->rule('nullable')
                            ->minLength(8),

                        Forms\Components\TextInput::make('new_password_confirmation')
                            ->password()
                            ->revealable()
                            ->label('Confirm New Password')
                            ->dehydrated(false)
                            ->same('new_password')
                            ->helperText('Leave blank to keep your current password.'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Changes')
                ->submit('save')
                ->color('primary')
                ->icon('heroicon-o-check'),
        ];
    }

    public function save(): void
    {
        $user = Auth::user();
        abort_unless(!empty($user), 403);

        $state = $this->form->getState();

        // Validate email uniqueness manually to ignore current record accurately
        $this->validate([
            'data.email' => [
                'required', 'email', Rule::unique('users', 'email')->ignore($user->id),
            ],
            'data.name' => ['required', 'string', 'max:255'],
            'data.phone_number' => ['nullable', 'string', 'max:50'],
            'data.intercom' => ['nullable', 'string', 'max:50'],
        ]);

        // Handle password change if provided
        $newPassword = (string) ($state['new_password'] ?? '');
        if ($newPassword !== '') {
            $current = (string) ($state['current_password'] ?? '');
            if (! Hash::check($current, $user->password)) {
                Notification::make()
                    ->title('Current password is incorrect')
                    ->danger()
                    ->send();
                return;
            }
            if (($state['new_password_confirmation'] ?? '') !== $newPassword) {
                Notification::make()
                    ->title('Password confirmation does not match')
                    ->danger()
                    ->send();
                return;
            }
            $user->password = $newPassword; // cast: hashed in User model casts
        }

        // Update basic fields
        $user->name = (string) $state['name'];
        $user->email = isset($state['email']) ? (string) $state['email'] : $user->email;
        $user->phone_number = $state['phone_number'] ?? null;
        $user->intercom = $state['intercom'] ?? null;

        $user->save();

        // Update locations only for superadmins
        if ($user->isSuperAdmin()) {
            $locIds = array_map('intval', (array) ($state['locations'] ?? []));
            $user->locations()->sync($locIds);
        }

        Notification::make()
            ->title('Profile updated')
            ->success()
            ->send();
    }
}
