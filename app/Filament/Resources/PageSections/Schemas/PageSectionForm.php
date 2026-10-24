<?php

namespace App\Filament\Resources\PageSections\Schemas;

use App\Models\PageSection;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class PageSectionForm
{
    public static function configure(Schema $schema): Schema
    {
        // Built-in sections show their default text as a placeholder.
        $default = fn (string $field) => function (?PageSection $record) use ($field) {
            if (! $record?->isBuiltIn()) {
                return null;
            }
            $key = "site.{$record->type}.{$field}";

            return __($key) !== $key ? __($key) : null;
        };

        return $schema->components([
            Section::make('Section')->columns(2)->columnSpanFull()->schema([
                Select::make('type')
                    ->required()
                    ->live()
                    ->options(fn (?PageSection $record) => $record?->isBuiltIn()
                        ? PageSection::TYPES
                        : Arr::only(PageSection::TYPES, ['text', 'timeline']))
                    ->disabled(fn (?PageSection $record) => (bool) $record?->isBuiltIn())
                    ->dehydrated(fn (?PageSection $record) => ! $record?->isBuiltIn())
                    ->helperText('Text block: free text. Timeline: a list such as work experience, education or certificates.'),
                Toggle::make('is_visible')
                    ->label('Show on website')
                    ->default(true)
                    ->inline(false),
                TextInput::make('title')
                    ->maxLength(255)
                    ->placeholder($default('title'))
                    ->required(fn (?PageSection $record) => ! $record?->isBuiltIn())
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, ?string $state, Set $set) {
                        if ($operation === 'create') {
                            $set('anchor', Str::slug((string) $state));
                            $set('nav_label', Str::limit((string) $state, 30, ''));
                        }
                    })
                    ->helperText(fn (?PageSection $record) => $record?->isBuiltIn() ? 'Leave empty to use the default text.' : null),
                TextInput::make('eyebrow')
                    ->label('Small label above the title')
                    ->maxLength(100)
                    ->placeholder($default('eyebrow')),
                Textarea::make('intro')
                    ->rows(2)
                    ->columnSpanFull()
                    ->placeholder($default('intro')),
                TextInput::make('nav_label')
                    ->label('Menu label')
                    ->maxLength(60)
                    ->helperText('Shown in the top menu. Leave empty to keep this section out of the menu.'),
                TextInput::make('anchor')
                    ->label('Link name')
                    ->required()
                    ->alphaDash()
                    ->maxLength(60)
                    ->unique(ignoreRecord: true)
                    ->prefix('/#')
                    ->helperText('Used in links to this section, e.g. /#experience.'),
            ]),

            Section::make('Text')
                ->columnSpanFull()
                ->visible(fn (Get $get) => $get('type') === 'text')
                ->schema([
                    RichEditor::make('body')
                        ->hiddenLabel()
                        ->toolbarButtons([['bold', 'italic', 'link'], ['h2', 'h3'], ['bulletList', 'orderedList'], ['undo', 'redo']]),
                ]),

            Section::make('Timeline items')
                ->description('Newest first is easiest to read. Drag to reorder.')
                ->columnSpanFull()
                ->visible(fn (Get $get) => $get('type') === 'timeline')
                ->schema([
                    Repeater::make('items')
                        ->hiddenLabel()
                        ->reorderable()
                        ->collapsible()
                        ->defaultItems(1)
                        ->addActionLabel('Add item')
                        ->itemLabel(fn (array $state): ?string => trim(($state['period'] ?? '').' · '.($state['title'] ?? ''), ' ·')
                            .(($state['is_visible'] ?? true) ? '' : ' (hidden)'))
                        ->schema([
                            Grid::make(['default' => 1, 'sm' => 3])->schema([
                                TextInput::make('period')->maxLength(60)->placeholder('2022 – Present'),
                                TextInput::make('title')->required()->maxLength(150)->placeholder('Mobile Developer'),
                                TextInput::make('subtitle')->maxLength(150)->placeholder('Company / school / issuer'),
                            ]),
                            Textarea::make('description')->rows(2),
                            Grid::make(['default' => 1, 'sm' => 3])->schema([
                                TextInput::make('url')->url()->maxLength(255)->label('Link (optional)'),
                                TextInput::make('link_label')->maxLength(40)->placeholder('View'),
                                Toggle::make('is_visible')->label('Show on website')->default(true)->inline(false),
                            ]),
                        ]),
                ]),
        ]);
    }
}
