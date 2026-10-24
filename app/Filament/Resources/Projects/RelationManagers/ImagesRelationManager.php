<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Models\ProjectImage;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Project images. "Phone screenshot" images become the 3D phone screen;
 * the others appear in the gallery. Uploads are converted to WebP automatically.
 */
class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $title = 'Images';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('path')
                    ->label('Image')
                    ->image()
                    ->required()
                    ->disk('public')
                    ->visibility('public')
                    ->directory(fn () => 'projects/'.$this->getOwnerRecord()->slug)
                    ->maxSize(8 * 1024)
                    ->imagePreviewHeight('240')
                    ->helperText('PNG, JPG or WebP, max 8 MB. A compressed WebP copy is made automatically.')
                    ->columnSpanFull(),
                Select::make('kind')
                    ->required()
                    ->default('screenshot')
                    ->options(ProjectImage::KINDS)
                    ->helperText('Phone screenshots are shown on the 3D phone.'),
                TextInput::make('caption')->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('caption')
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->paginated(false)
            ->columns([
                ImageColumn::make('path')->label('')->disk('public')->imageHeight(64),
                TextColumn::make('caption')->searchable(),
                TextColumn::make('kind')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ProjectImage::KINDS[$state] ?? $state),
                TextColumn::make('width')
                    ->label('Size')
                    ->formatStateUsing(fn ($record) => $record->width ? "{$record->width}×{$record->height}" : '–')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('kind')->options(ProjectImage::KINDS),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add image')
                    ->mutateDataUsing(function (array $data): array {
                        $data['sort_order'] = (int) $this->getOwnerRecord()->images()->max('sort_order') + 1;

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Delete one by one so files are removed too.
                    DeleteBulkAction::make()->using(fn ($records) => $records->each->delete()),
                ]),
            ]);
    }
}
