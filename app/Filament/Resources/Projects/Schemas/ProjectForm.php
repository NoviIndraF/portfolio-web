<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Models\Project;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
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
                Select::make('type')
                    ->required()
                    ->options([
                        'mobile' => 'Mobile',
                        'mobile+web' => 'Mobile + Web',
                        'mobile+iot' => 'Mobile + IoT',
                        'web' => 'Web',
                    ]),
                Select::make('scene_type')
                    ->label('3D scene')
                    ->required()
                    ->options(Project::SCENE_TYPES)
                    ->helperText('Themed 3D scene shown around the phone.'),
                ColorPicker::make('accent_color')
                    ->required()
                    ->regex('/^#[0-9a-fA-F]{6}$/'),
                Toggle::make('is_published')
                    ->label('Published')
                    ->default(true)
                    ->inline(false),
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
                ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                ->addActionLabel('Add feature')
                ->defaultItems(0)
                ->schema([
                    TextInput::make('title')->required()->maxLength(255),
                    Textarea::make('description')->rows(2),
                    TextInput::make('icon')
                        ->maxLength(100)
                        ->placeholder('e.g. heroicon-o-chat-bubble-left-right')
                        ->helperText('Optional icon name.'),
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
                    ->simple(TextInput::make('name')->required()->maxLength(100))
                    ->addActionLabel('Add technology')
                    ->defaultItems(0),
                Repeater::make('links')
                    ->label('Links')
                    ->helperText('Leave empty to hide link buttons for this project.')
                    ->relationship()
                    ->orderColumn('sort_order')
                    ->reorderable()
                    ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                    ->addActionLabel('Add link')
                    ->defaultItems(0)
                    ->schema([
                        TextInput::make('label')->required()->maxLength(50)->placeholder('Web, Mobile, Backend...'),
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
