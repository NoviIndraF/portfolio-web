<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Models\Project;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Project')
                    ->columnSpanFull()
                    ->persistTabInQueryString()
                    ->tabs([
                        self::overviewTab(),
                        self::featuresTab(),
                        self::techLinksTab(),
                        self::recognitionTab(),
                    ]),
            ]);
    }

    /** "Show on website" switch used by every hideable item. */
    public static function visibleToggle(): Toggle
    {
        return Toggle::make('is_visible')
            ->label('Show on website')
            ->default(true)
            ->inline(false);
    }

    /**
     * Phone screenshots of the project, for linking a feature to its screen.
     *
     * @return array<int, string>
     */
    private static function screenOptions(?Project $project): array
    {
        if (! $project) {
            return [];
        }

        return $project->images()->where('kind', 'screenshot')->get()
            ->mapWithKeys(fn ($img, $i) => [$img->id => ($i + 1).'. '.($img->caption ?: 'Screenshot')])
            ->all();
    }

    private static function label(?string $text, array $state): ?string
    {
        return $text && ! ($state['is_visible'] ?? true) ? $text.' (hidden)' : $text;
    }

    private static function overviewTab(): Tab
    {
        return Tab::make('Overview')->icon('heroicon-o-information-circle')->schema([
            Grid::make(2)->schema([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, ?string $state, Set $set) {
                        if ($operation === 'create') {
                            $set('slug', Str::slug((string) $state));
                        }
                    }),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->alphaDash()
                    ->helperText('Used in the URL: /projects/{slug}'),
                Grid::make(2)->schema([
                    TextInput::make('type')
                        ->required()
                        ->maxLength(50)
                        ->datalist(Project::TYPES)
                        ->helperText('e.g. mobile, mobile+web, web, iot. Used for the filter on /projects.'),
                    TextInput::make('year')
                        ->maxLength(20)
                        ->placeholder('2023'),
                ]),
                Select::make('scene_type')
                    ->label('3D scene')
                    ->required()
                    ->options(Project::SCENE_TYPES)
                    ->helperText('Themed 3D scene shown around the phone.'),
                ColorPicker::make('accent_color')
                    ->required()
                    ->regex('/^#[0-9a-fA-F]{6}$/'),
                Grid::make(2)->schema([
                    Toggle::make('is_published')
                        ->label('Published')
                        ->helperText('Off = hidden from the website (draft).')
                        ->default(true)
                        ->inline(false),
                    Toggle::make('is_featured')
                        ->label('Featured on home')
                        ->helperText('Shown in the home carousel. All published projects are on /projects.')
                        ->default(true)
                        ->inline(false),
                ]),
            ]),
            TextInput::make('impact_line')
                ->label('Impact line')
                ->maxLength(500)
                ->helperText('One sentence on the project card. Add real numbers if you have them (users, downloads, results).'),
            Textarea::make('short_description')
                ->required()
                ->rows(3),
            Grid::make(2)->schema([
                Textarea::make('problem')
                    ->rows(3)
                    ->helperText('Optional. The problem this app solves.'),
                Textarea::make('solution')
                    ->rows(3)
                    ->helperText('Optional. How the app solves it.'),
            ]),
        ]);
    }

    private static function featuresTab(): Tab
    {
        return Tab::make('Features')->icon('heroicon-o-sparkles')->schema([
            Repeater::make('features')
                ->hiddenLabel()
                ->relationship()
                ->orderColumn('sort_order')
                ->reorderable()
                ->collapsible()
                ->itemLabel(fn (array $state): ?string => self::label($state['title'] ?? null, $state))
                ->addActionLabel('Add feature')
                ->defaultItems(0)
                ->schema([
                    Grid::make(['default' => 1, 'sm' => 4])->schema([
                        TextInput::make('title')->required()->maxLength(255)->columnSpan(['sm' => 3]),
                        self::visibleToggle(),
                    ]),
                    Textarea::make('description')->rows(2),
                    Grid::make(['default' => 1, 'sm' => 2])->schema([
                        TextInput::make('icon')
                            ->maxLength(100)
                            ->placeholder('e.g. heroicon-o-chat-bubble-left-right')
                            ->helperText('Optional icon name.'),
                        Select::make('project_image_id')
                            ->label('Screen')
                            ->options(fn ($livewire): array => self::screenOptions($livewire->getRecord()))
                            ->placeholder('None')
                            ->helperText('Phone screenshot that shows this feature. Save new images first.'),
                    ]),
                ]),
        ]);
    }

    private static function techLinksTab(): Tab
    {
        return Tab::make('Tech & links')->icon('heroicon-o-code-bracket')->schema([
            Grid::make(2)->schema([
                Repeater::make('tech')
                    ->label('Technologies')
                    ->relationship()
                    ->orderColumn('sort_order')
                    ->reorderable()
                    ->table([
                        TableColumn::make('Name')->markAsRequired(),
                        TableColumn::make('Show'),
                    ])
                    ->schema([
                        TextInput::make('name')->required()->maxLength(100),
                        Toggle::make('is_visible')->default(true)->hiddenLabel(),
                    ])
                    ->addActionLabel('Add technology')
                    ->defaultItems(0),
                Repeater::make('links')
                    ->label('Links')
                    ->helperText('Leave empty to hide link buttons for this project.')
                    ->relationship()
                    ->orderColumn('sort_order')
                    ->reorderable()
                    ->itemLabel(fn (array $state): ?string => self::label($state['label'] ?? null, $state))
                    ->addActionLabel('Add link')
                    ->defaultItems(0)
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 3])->schema([
                            TextInput::make('label')->required()->maxLength(50)->placeholder('Web, Mobile, Backend...')->columnSpan(['sm' => 2]),
                            self::visibleToggle(),
                        ]),
                        TextInput::make('url')->required()->url()->maxLength(255),
                    ]),
            ]),
        ]);
    }

    private static function recognitionTab(): Tab
    {
        return Tab::make('Recognition & 3D model')->icon('heroicon-o-trophy')->schema([
            Section::make('Recognition')
                ->description('For example a software copyright registration (HKI). Leave empty if none.')
                ->columns(3)
                ->schema([
                    TextInput::make('recognition.type')->label('Type')->maxLength(255),
                    TextInput::make('recognition.holder')->label('Holder')->maxLength(255),
                    TextInput::make('recognition.published')->label('Date')->maxLength(100),
                ]),
            Section::make('Custom 3D model')
                ->description('Optional .glb file shown in the accent scene. Without it, a built-in model is used.')
                ->schema([
                    FileUpload::make('model_glb')
                        ->label('GLB model')
                        ->disk('public')
                        ->visibility('public')
                        ->directory('models')
                        ->acceptedFileTypes(['model/gltf-binary', 'application/octet-stream'])
                        ->maxSize(20 * 1024)
                        ->helperText('Max 20 MB. Keep it small (under 3 MB) so the page loads fast on phones.'),
                ]),
        ]);
    }
}
