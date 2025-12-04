<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisitResource\Pages;
use App\Models\Visit;
use App\Models\Visitor;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class VisitResource extends Resource
{
    protected static ?string $model = Visit::class;

    protected static \BackedEnum|null|string $navigationIcon = 'heroicon-o-queue-list';

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user) {
            return $query;
        }

        if ($user->isSuperAdmin()) {
            return $query;
        }

        // For Admin and Receptionist, scope by location
        if ($user->isAdmin() || $user->isReceptionist()) {
            $ids = $user->accessibleLocationIds();
            if (is_array($ids) && ! empty($ids)) {
                $query->whereIn('location_id', $ids);
            }
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('visitor_id')
                ->label('Visitor')
                ->relationship('visitor', 'id')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                ->getSearchResultsUsing(function (string $search) {
                    return Visitor::query()
                        ->when($search, function ($q) use ($search) {
                            $q->where(function ($qq) use ($search) {
                                $qq->where('first_name', 'like', "%{$search}%")
                                   ->orWhere('last_name', 'like', "%{$search}%")
                                   ->orWhere('email', 'like', "%{$search}%")
                                   ->orWhere('mobile', 'like', "%{$search}%");
                            });
                        })
                        ->orderBy('last_name')
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(function (Visitor $v) {
                            $email = $v->email ? " ({$v->email})" : '';
                            $mobile = $v->mobile ? " • {$v->mobile}" : '';
                            return [$v->id => $v->full_name . $email . $mobile];
                        })
                        ->all();
                })
                ->getOptionLabelUsing(function ($value): ?string {
                    if (blank($value)) return null;
                    $v = Visitor::find($value);
                    if (! $v) return null;
                    $email = $v->email ? " ({$v->email})" : '';
                    $mobile = $v->mobile ? " • {$v->mobile}" : '';
                    return $v->full_name . $email . $mobile;
                })
                ->searchable()
                ->preload()
                ->required(),

            \Filament\Schemas\Components\Section::make('Visitor Details')
                ->schema([
                    Forms\Components\Placeholder::make('visitor_full_name')
                        ->label('Name')
                        ->content(function ($get): ?string {
                            $id = $get('visitor_id');
                            $v = $id ? Visitor::find($id) : null;
                            return $v?->full_name;
                        }),
                    Forms\Components\Placeholder::make('visitor_email')
                        ->label('Email')
                        ->content(function ($get): ?string {
                            $id = $get('visitor_id');
                            $v = $id ? Visitor::find($id) : null;
                            return $v?->email;
                        }),
                    Forms\Components\Placeholder::make('visitor_mobile')
                        ->label('Mobile')
                        ->content(function ($get): ?string {
                            $id = $get('visitor_id');
                            $v = $id ? Visitor::find($id) : null;
                            return $v?->mobile;
                        }),
                    Forms\Components\Placeholder::make('visitor_image')
                        ->label('Image')
                        ->content(function ($get): ?HtmlString {
                            $id = $get('visitor_id');
                            $v = $id ? Visitor::find($id) : null;
                            if (! $v?->image_url) return null;
                            $src = e($v->image_url);
                            return new HtmlString("<img src=\"{$src}\" alt=\"Visitor image\" style=\"max-width: 160px; border-radius: 0.5rem;\">");
                        }),
                ])
                ->columns(2)
                ->visible(fn ($get) => filled($get('visitor_id'))),

            Forms\Components\Select::make('staff_visited_id')
                ->label('Staff Visited')
                ->relationship('staff', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('reason_for_visit_id')
                ->label('Reason')
                ->relationship('reason', 'name')
                ->searchable()
                ->preload(),
            Forms\Components\TextInput::make('tag_number')
                ->label('Tag Number')
                ->maxLength(100)
                ->nullable(),
            Forms\Components\Select::make('status')
                ->options([
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                ])
                ->native(false)
                ->required()
                ->default('pending'),
            Forms\Components\DateTimePicker::make('checkin_time')
                ->native(false)
                ->nullable(),
            Forms\Components\DateTimePicker::make('checkout_time')
                ->native(false)
                ->label('Checkout Time')
                ->nullable(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                $user = auth()->user();
                if ($user && method_exists($user, 'isReceptionist') && $user->isReceptionist()) {
                    $query->whereDate('created_at', today());
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('visitor.full_name')
                    ->label('Visitor')
                    ->searchable(['first_name', 'last_name']),
                // Staff column - read-only, editable via approval modal
                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Staff')
                    ->searchable()
                    ->formatStateUsing(fn ($state) => $state ?: '—'),
                Tables\Columns\TextColumn::make('reason.name')->label('Reason')->toggleable(isToggledHiddenByDefault: true),
                // Tag number - read-only, editable via approval modal
                Tables\Columns\TextColumn::make('tag_number')
                    ->label('Tag')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('checkin_time')->dateTime()->sortable()->label('Check-in')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('checkout_time')->dateTime()->sortable()->label('Checkout')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                    ])
                    ->label('Status'),

                Tables\Filters\SelectFilter::make('staff_visited_id')
                    ->relationship('staff', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Staff Member'),

                Tables\Filters\SelectFilter::make('reason_for_visit_id')
                    ->relationship('reason', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Visit Reason'),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Created From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Created Until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators[] = 'Created from ' . \Carbon\Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'Created until ' . \Carbon\Carbon::parse($data['created_until'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),

                Tables\Filters\Filter::make('checkin_time')
                    ->form([
                        Forms\Components\DatePicker::make('checkin_from')
                            ->label('Check-in From'),
                        Forms\Components\DatePicker::make('checkin_until')
                            ->label('Check-in Until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['checkin_from'], fn ($q, $date) => $q->whereDate('checkin_time', '>=', $date))
                            ->when($data['checkin_until'], fn ($q, $date) => $q->whereDate('checkin_time', '<=', $date));
                    }),

                Tables\Filters\TernaryFilter::make('checkout_time')
                    ->label('Checkout Status')
                    ->placeholder('All visits')
                    ->trueLabel('Checked out')
                    ->falseLabel('Still on-site')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('checkout_time'),
                        false: fn ($query) => $query->whereNull('checkout_time'),
                    ),

                Tables\Filters\Filter::make('today')
                    ->label("Today's Visits")
                    ->query(fn ($query) => $query->whereDate('created_at', today()))
                    ->toggle(),

                Tables\Filters\Filter::make('this_week')
                    ->label("This Week")
                    ->query(fn ($query) => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]))
                    ->toggle(),
            ])
            ->defaultSort('checkin_time', 'desc')
            ->actions([
                Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->visible(fn ($record) => $record->status !== 'approved' && !$record->visitor->is_blacklisted)
                    ->form(function (Visit $record) {
                        // Check if visitor is blacklisted BEFORE showing form
                        if ($record->visitor->is_blacklisted) {
                            \Filament\Notifications\Notification::make()
                                ->title('Cannot Approve - Visitor is Blacklisted')
                                ->body('Reason: ' . ($record->visitor->reasons_for_blacklisting ?: 'No reason provided'))
                                ->danger()
                                ->persistent()
                                ->send();
                                
                            return [];
                        }

                        return [
                            Forms\Components\Placeholder::make('visitor_image_preview')
                                ->label('Current Photo')
                                ->content(function () use ($record) {
                                    if (! $record->visitor->image_url) {
                                        return new \Illuminate\Support\HtmlString('<span class="text-gray-500 italic">No photo available</span>');
                                    }
                                    return new \Illuminate\Support\HtmlString('<img src="' . e($record->visitor->image_url) . '" style="max-width: 150px; border-radius: 8px;" />');
                                }),

                            Forms\Components\FileUpload::make('visitor_image')
                                ->label('Upload Photo')
                                ->image()
                                ->directory('visitors')
                                ->visibility('public')
                                ->disk(config('cloudinary.cloud.cloud_name') ? 'cloudinary' : 'public')
                                ->imageEditor()
                                ->required(fn () => blank($record->visitor->image_url))
                                ->helperText('Required if the visitor has no photo.'),

                            Forms\Components\Select::make('staff_visited_id')
                                ->label('Staff Member')
                                ->relationship('staff', 'name')
                                ->default($record->staff_visited_id)
                                ->searchable()
                                ->required(),

                            Forms\Components\Select::make('location_id')
                                ->label('Location')
                                ->relationship('location', 'name')
                                ->default(function () use ($record) {
                                    if ($record->location_id) return $record->location_id;
                                    $staff = $record->staff;
                                    if ($staff && $staff->assigned_location_id) {
                                        return $staff->assigned_location_id;
                                    }
                                    return session('checkin_location_id');
                                })
                                ->searchable()
                                ->required(),

                            Forms\Components\TextInput::make('tag_number')
                                ->label('Tag Number')
                                ->default($record->tag_number)
                                ->required()
                                ->rule(function () use ($record) {
                                    return function (string $attribute, $value, \Closure $fail) use ($record) {
                                        if (blank($value)) {
                                            return;
                                        }
                                        $existingVisit = \App\Models\Visit::query()
                                            ->where('tag_number', $value)
                                            ->where('id', '!=', $record->id)
                                            ->where('status', 'approved')
                                            ->whereNotNull('checkin_time')
                                            ->whereNull('checkout_time')
                                            ->first();
                                        if ($existingVisit) {
                                            $fail("Tag number {$value} is already assigned to an active visitor ({$existingVisit->visitor->full_name}).");
                                        }
                                    };
                                }),

                            Forms\Components\Select::make('reason_for_visit_id')
                                ->label('Reason for Visit')
                                ->relationship('reason', 'name')
                                ->default($record->reason_for_visit_id)
                                ->searchable()
                                ->required(),
                        ];
                    })
                    ->requiresConfirmation()
                    ->action(function (Visit $record, array $data) {
                        // Double-check if visitor is blacklisted
                        if ($record->visitor->is_blacklisted) {
                            \Filament\Notifications\Notification::make()
                                ->title('Cannot Approve - Visitor is Blacklisted')
                                ->body('Reason: ' . ($record->visitor->reasons_for_blacklisting ?: 'No reason provided'))
                                ->danger()
                                ->persistent()
                                ->send();
                            return;
                        }

                        // Update visitor image if uploaded
                        if (! empty($data['visitor_image'])) {
                            $disk = config('cloudinary.cloud.cloud_name') ? 'cloudinary' : 'public';
                            $imageUrl = \Illuminate\Support\Facades\Storage::disk($disk)->url($data['visitor_image']);
                            $record->visitor->update(['image_url' => $imageUrl]);
                        }

                        $updates = [
                            'staff_visited_id' => $data['staff_visited_id'],
                            'location_id' => $data['location_id'],
                            'tag_number' => $data['tag_number'],
                            'reason_for_visit_id' => $data['reason_for_visit_id'],
                            'status' => 'approved',
                            'checkin_time' => now(),
                        ];

                        $record->update($updates);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Visit Approved')
                            ->success()
                            ->send();
                    }),
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVisits::route('/'),
            'create' => Pages\CreateVisit::route('/create'),
            'edit' => Pages\EditVisit::route('/{record}/edit'),
        ];
    }
}
