<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Visit;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action as TableAction;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class VisitsAwaitingApprovalWidget extends BaseWidget
{
    use \App\Traits\HasImageUpload;

    protected static ?string $heading = 'Visits Awaiting Approval';
    protected static ?int $sort = 5;
    protected int|string|array $columnSpan = 'full';
    protected ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        $user = auth()->user();
        // Only show to receptionists (operational widget)
        return $user && $user->isReceptionist();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('visitor.full_name')
                    ->label('Visitor')
                    ->searchable(['first_name', 'last_name'])
                    ->description(fn (Visit $record) => $record->visitor->is_blacklisted 
                        ? '⚠️ BLACKLISTED: ' . ($record->visitor->reasons_for_blacklisting ?: 'No reason provided')
                        : null
                    )
                    ->color(fn (Visit $record) => $record->visitor->is_blacklisted ? 'danger' : null),
                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Staff')
                    ->formatStateUsing(fn ($state) => $state ?: '—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('reason.name')->label('Reason')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->label('Requested At')->dateTime()->sortable(),
            ])
            ->filters([
            ])
            ->actions([
                TableAction::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn () => Auth::user()?->hasPermission('visits.update') ?? false)
                    ->form(function (Visit $record) {
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
                                ->disk(static::cloudinaryEnabled() ? 'cloudinary' : 'public')
                                ->imageEditor()
                                ->required(fn () => blank($record->visitor->image_url)) // Required if no image exists
                                ->helperText('Required if the visitor has no photo.')
                                ->saveUploadedFileUsing(function ($file) {
                                    return static::handleImageUpload($file);
                                }),

                            Forms\Components\Select::make('staff_visited_id')
                                ->label('Staff Member')
                                ->options(User::query()->orderBy('name')->pluck('name', 'id'))
                                ->default($record->staff_visited_id)
                                ->searchable()
                                ->required(),

                            Forms\Components\Select::make('location_id')
                                ->label('Location')
                                ->options(\App\Models\Location::query()->pluck('name', 'id'))
                                ->default(function () use ($record) {
                                    // Default to record's location, or staff's location, or session location
                                    if ($record->location_id) return $record->location_id;
                                    
                                    $staff = $record->staff;
                                    if ($staff && $staff->assigned_location_id) {
                                        return $staff->assigned_location_id;
                                    }

                                    // Fallback to session location if available (from checkin controller)
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

                                        // Check if tag is already assigned to an active visit
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
                                ->options(\App\Models\ReasonForVisit::query()->pluck('name', 'id'))
                                ->default($record->reason_for_visit_id)
                                ->searchable()
                                ->required(),
                        ];
                    })
                    ->requiresConfirmation()
                    ->visible(fn (Visit $record) => !$record->visitor->is_blacklisted)
                    ->action(function (Visit $record, array $data) {

                        // Update visitor image if uploaded
                        if (! empty($data['visitor_image'])) {
                            $imageUrl = static::getImageUrl($data['visitor_image']);
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
            ])
            ->bulkActions([
            ])
            ->paginated([10, 25])
            ->defaultPaginationPageOption(10);
    }

    protected function getTableQuery(): Builder
    {
        $query = Visit::query()
            ->with(['visitor', 'staff', 'reason'])
            ->where('status', 'pending')
            ->latest('created_at');

        // Apply location scoping
        $user = auth()->user();
        $ids = $user?->accessibleLocationIds();

        if (is_array($ids) && !empty($ids)) {
            $query->where(function ($q) use ($ids) {
                // Check if visit's location_id matches accessible locations
                $q->whereIn('location_id', $ids)
                  // OR if staff belongs to accessible locations (for backward compatibility)
                  ->orWhereHas('staff.locations', function ($qr) use ($ids) {
                      $qr->whereIn('locations.id', $ids);
                  })
                  ->orWhereHas('staff', function ($qr) use ($ids) {
                      $qr->whereIn('assigned_location_id', $ids);
                  });
            });
        }

        return $query;
    }
}
