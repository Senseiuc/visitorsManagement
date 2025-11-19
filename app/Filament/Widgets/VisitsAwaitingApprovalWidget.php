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
    protected static ?string $heading = 'Visits Awaiting Approval';
    protected static ?int $sort = 5;
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && ($user->isReceptionist() || $user->isAdmin() || $user->isSuperAdmin());
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('visitor.full_name')->label('Visitor')->searchable(),
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
                        $schema = [];

                        if (! $record->staff_visited_id) {
                            $schema[] = Forms\Components\Select::make('staff_visited_id')
                                ->label('Select staff to approve')
                                ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id')->all())
                                ->searchable()
                                ->required();
                        }

                        if (! $record->reason_for_visit_id) {
                            $schema[] = Forms\Components\Select::make('reason_for_visit_id')
                                ->label('Reason for Visit')
                                ->options(fn () => \App\Models\ReasonForVisit::query()->pluck('name', 'id')->all())
                                ->searchable()
                                ->required();
                        }

                        return $schema;
                    })
                    ->requiresConfirmation()
                    ->action(function (Visit $record, array $data) {
                        // Check if visitor is blacklisted
                        if ($record->visitor->is_blacklisted) {
                            \Filament\Notifications\Notification::make()
                                ->title('Visitor is Blacklisted')
                                ->body('Reason: ' . $record->visitor->reasons_for_blacklisting)
                                ->danger()
                                ->persistent()
                                ->send();
                                
                            return;
                        }

                        $updates = [];

                        // If staff not set yet, require it now before approval
                        if (empty($record->staff_visited_id)) {
                            $staffId = (int)($data['staff_visited_id'] ?? 0);
                            if ($staffId > 0) {
                                $updates['staff_visited_id'] = $staffId;
                            }
                        }

                        // If reason not set yet, require it now
                        if (empty($record->reason_for_visit_id)) {
                            $reasonId = (int)($data['reason_for_visit_id'] ?? 0);
                            if ($reasonId > 0) {
                                $updates['reason_for_visit_id'] = $reasonId;
                            }
                        }

                        // Set status approved and record check-in time now
                        $updates['status'] = 'approved';
                        $updates['checkin_time'] = now();

                        $record->forceFill($updates)->save();
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

        // TODO: If visits are associated to a location, scope by Auth::user()->accessibleLocationIds()

        return $query;
    }
}
