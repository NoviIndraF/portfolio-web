<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class SiteSettings extends SingleRecordPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Site';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $title = 'Site settings';

    protected static ?string $slug = 'settings';

    protected function getRecord(): Model
    {
        return Setting::current();
    }

    protected function getFormComponents(): array
    {
        return [
            Section::make('SEO & sharing')->schema([
                TextInput::make('site_title')->required()->maxLength(255),
                Textarea::make('meta_description')
                    ->rows(2)
                    ->maxLength(300)
                    ->helperText('Shown in Google results and link previews.'),
                FileUpload::make('og_image')
                    ->label('Share image (Open Graph)')
                    ->image()
                    ->disk('public')
                    ->visibility('public')
                    ->directory('og')
                    ->acceptedFileTypes(['image/png', 'image/jpeg'])
                    ->maxSize(4 * 1024)
                    ->helperText('1200×630 PNG or JPG works best. If empty, your profile photo is used.'),
            ]),
            Section::make('Theme')->columns(3)->schema([
                ColorPicker::make('primary_color')->required()->regex('/^#[0-9a-fA-F]{6}$/')
                    ->helperText('Used in both light and dark mode.'),
                ColorPicker::make('background_color')->label('Dark mode background')->required()->regex('/^#[0-9a-fA-F]{6}$/'),
                ColorPicker::make('text_color')->label('Dark mode text')->required()->regex('/^#[0-9a-fA-F]{6}$/'),
                Select::make('default_theme')
                    ->label('Default mode')
                    ->options(Setting::THEMES)
                    ->required()
                    ->helperText('What new visitors see first. They can switch with the sun/moon button.'),
                ColorPicker::make('light_background_color')->label('Light mode background')->required()->regex('/^#[0-9a-fA-F]{6}$/'),
                ColorPicker::make('light_text_color')->label('Light mode text')->required()->regex('/^#[0-9a-fA-F]{6}$/'),
            ]),
            Section::make('3D effects')->schema([
                Toggle::make('toggle_3d')
                    ->label('Enable 3D scenes')
                    ->helperText('Turn off to show static images instead of 3D (faster on old phones).'),
            ]),
            Section::make('Language')->schema([
                Toggle::make('show_language_switch')
                    ->label('Show the ID / EN switch')
                    ->helperText('Menus, buttons and labels in Indonesian or English. Texts you write in the admin stay as written.'),
            ]),
            Section::make('Analytics')
                ->description('Privacy-friendly visitor stats (Umami or Plausible). Nothing loads while the script URL is empty.')
                ->columns(2)
                ->schema([
                    TextInput::make('analytics_script_url')
                        ->label('Script URL')
                        ->url()
                        ->maxLength(255)
                        ->placeholder('https://cloud.umami.is/script.js'),
                    TextInput::make('analytics_site_id')
                        ->label('Website ID / domain')
                        ->maxLength(120)
                        ->helperText('Umami: the website ID. Plausible: your domain.'),
                ]),
        ];
    }
}
