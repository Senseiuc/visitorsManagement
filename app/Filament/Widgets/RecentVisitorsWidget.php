<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\VisitorResource;
use App\Models\Visitor;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action as TableAction;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RecentVisitorsWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Visitors';
    protected static ?int $sort = 8;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Photo')
                    ->circular()
                    ->defaultImageUrl(url('/images/placeholder-user.png')),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('mobile')
                    ->label('Mobile')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_blacklisted')
                    ->label('Blacklisted')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                TableAction::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->visible(fn () => Auth::user()?->hasPermission('visitors.view') ?? false)
                    ->url(fn (Visitor $record) => VisitorResource::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(),

                TableAction::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn () => Auth::user()?->hasPermission('visitors.update') ?? false)
                    ->url(fn (Visitor $record) => VisitorResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),
            ])
            ->paginated([10, 25])
            ->defaultPaginationPageOption(10);
    }

    protected function getTableQuery(): Builder
    {
        return Visitor::query()->latest('created_at');
    }
}
