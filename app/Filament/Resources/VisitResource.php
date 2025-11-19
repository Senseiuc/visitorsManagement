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
                Tables\Columns\TextColumn::make('visitor.full_name')->label('Visitor')->searchable(),
                // Make staff editable inline
                Tables\Columns\SelectColumn::make('staff_visited_id')
                    ->label('Staff')
                    ->options(fn () => \App\Models\User::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
                Tables\Columns\TextColumn::make('reason.name')->label('Reason')->toggleable(isToggledHiddenByDefault: true),
                // Allow reception to enter tag number inline
                \Filament\Tables\Columns\TextInputColumn::make('tag_number')
                    ->label('Tag')
                    ->rules(['max:100'])
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
                    ->visible(fn ($record) => $record->status !== 'approved')
                    ->requiresConfirmation()
                    ->action(function (Visit $record) {
                        if (blank($record->checkin_time)) {
                            $record->checkin_time = now();
                        }
                        $record->status = 'approved';
                        $record->save();
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
