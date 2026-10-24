<?php

namespace App\Filament\Resources\PageSections\Tables;

use App\Models\PageSection;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class PageSectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->paginated(false)
            ->columns([
                TextColumn::make('title')
                    ->label('Section')
                    ->weight('bold')
                    ->getStateUsing(fn (PageSection $record) => $record->text('title') ?? '(untitled)')
                    ->description(fn (PageSection $record) => '/#'.$record->anchor),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (PageSection $record) => $record->isBuiltIn() ? 'gray' : 'info')
                    ->formatStateUsing(fn (string $state) => str(PageSection::TYPES[$state] ?? $state)->before(' (')),
                TextColumn::make('nav_label')->label('Menu')->placeholder('—'),
                ToggleColumn::make('is_visible')->label('Show'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->hidden(fn (PageSection $record) => $record->isBuiltIn()),
            ]);
    }
}
