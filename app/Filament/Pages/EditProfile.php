<?php

namespace App\Filament\Pages;

use App\Models\Profile;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class EditProfile extends SingleRecordPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 0;

    protected static ?string $navigationLabel = 'Profile';

    protected static ?string $title = 'Profile';

    protected static ?string $slug = 'portfolio-profile';

    protected function getRecord(): Model
    {
        return Profile::query()->firstOrCreate([], [
            'name' => 'Your name',
            'tagline' => 'Software Developer',
            'email' => 'you@example.com',
        ]);
    }

    protected function getFormComponents(): array
    {
        return [
            Section::make('Hero')->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('tagline')->required()->maxLength(255),
                Textarea::make('bio')->rows(4)->columnSpanFull(),
                Repeater::make('stats')
                    ->label('Stats (shown in the hero)')
                    ->columnSpanFull()
                    ->columns(2)
                    ->reorderable()
                    ->maxItems(4)
                    ->addActionLabel('Add stat')
                    ->schema([
                        TextInput::make('value')->required()->maxLength(20)->placeholder('4+'),
                        TextInput::make('label')->required()->maxLength(60)->placeholder('Years of experience'),
                    ]),
            ]),
            Section::make('Photo & CV')->columns(2)->schema([
                FileUpload::make('photo')
                    ->image()
                    ->avatar()
                    ->disk('public')
                    ->visibility('public')
                    ->directory('profile')
                    ->maxSize(8 * 1024)
                    ->helperText('Converted to WebP automatically.'),
                FileUpload::make('cv_pdf')
                    ->label('CV (PDF)')
                    ->disk('public')
                    ->visibility('public')
                    ->directory('profile')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(10 * 1024)
                    ->preserveFilenames()
                    ->helperText('If empty, the "Download CV" button is hidden.'),
            ]),
            Section::make('Contact')->columns(2)->schema([
                TextInput::make('email')->email()->required()->maxLength(255),
                TextInput::make('phone')->tel()->maxLength(50)->helperText('Optional. Hidden if empty.'),
                TextInput::make('github')->url()->maxLength(255),
                TextInput::make('linkedin')->url()->maxLength(255)->helperText('Optional. Hidden if empty.'),
            ]),
        ];
    }
}
