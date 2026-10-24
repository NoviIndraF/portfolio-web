<?php

namespace App\Filament\Resources\Projects\Tables;

use App\Models\Project;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->paginated([25, 50, 100])
            ->columns([
                ColorColumn::make('accent_color')->label(''),
                TextColumn::make('title')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn ($record) => str($record->impact_line)->limit(80)),
                TextColumn::make('type')->badge()->color('gray')->toggleable(),
                TextColumn::make('year')->toggleable(),
                TextColumn::make('images_count')->counts('images')->label('Images'),
                ToggleColumn::make('is_published')->label('Published'),
                ToggleColumn::make('is_featured')->label('On home'),
            ])
            ->filters([
                TernaryFilter::make('is_published')->label('Published'),
                TernaryFilter::make('is_featured')->label('On home'),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn ($record) => url('/projects/'.$record->slug))
                    ->openUrlInNewTab(),
                EditAction::make(),
                ActionGroup::make([
                    self::duplicateAction(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::bulkSet('publish', 'Publish', ['is_published' => true], Heroicon::OutlinedEye),
                    self::bulkSet('unpublish', 'Hide (draft)', ['is_published' => false], Heroicon::OutlinedEyeSlash),
                    self::bulkSet('feature', 'Show on home', ['is_featured' => true], Heroicon::OutlinedStar),
                    self::bulkSet('unfeature', 'Remove from home', ['is_featured' => false], Heroicon::OutlinedMinusCircle),
                    DeleteBulkAction::make()->using(fn (Collection $records) => $records->each->delete()),
                ]),
            ]);
    }

    private static function bulkSet(string $name, string $label, array $values, $icon): BulkAction
    {
        return BulkAction::make($name)
            ->label($label)
            ->icon($icon)
            ->action(fn (Collection $records) => $records->each->update($values))
            ->deselectRecordsAfterCompletion();
    }

    /** Copy a project with its features, technologies and links (not images) as a hidden draft. */
    private static function duplicateAction(): ReplicateAction
    {
        return ReplicateAction::make()
            ->label('Duplicate')
            ->modalDescription('Creates a hidden copy with the same features, technologies and links (images are not copied).')
            ->excludeAttributes(['images_count', 'model_glb'])
            ->beforeReplicaSaved(function (Project $replica) {
                $replica->title .= ' (copy)';
                $base = Str::slug($replica->title);
                $slug = $base;
                for ($i = 2; Project::where('slug', $slug)->exists(); $i++) {
                    $slug = "{$base}-{$i}";
                }
                $replica->slug = $slug;
                $replica->is_published = false;
                $replica->sort_order = (int) Project::max('sort_order') + 1;
            })
            ->after(function (Project $record, Project $replica) {
                foreach (['features', 'tech', 'links'] as $relation) {
                    $record->{$relation}()->get()->each(
                        fn ($item) => $replica->{$relation}()->create($item->replicate()->only($item->getFillable()))
                    );
                }
            })
            ->successRedirectUrl(fn (Project $replica) => \App\Filament\Resources\Projects\ProjectResource::getUrl('edit', ['record' => $replica]));
    }
}
