<?php

namespace App\Filament\Resources\SkillGroups\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SkillGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Group name')->required()->maxLength(100),
            \App\Filament\Resources\Projects\Schemas\ProjectForm::visibleToggle(),
            Repeater::make('skills')
                ->relationship()
                ->orderColumn('sort_order')
                ->reorderable()
                ->columnSpanFull()
                ->columns(3)
                ->itemLabel(fn (array $state): ?string => ($state['name'] ?? null).(($state['is_visible'] ?? true) ? '' : ' (hidden)'))
                ->addActionLabel('Add skill')
                ->defaultItems(0)
                ->schema([
                    TextInput::make('name')->required()->maxLength(100),
                    TextInput::make('icon')
                        ->maxLength(100)
                        ->placeholder('flutter')
                        ->helperText('Optional simple-icons slug (see simpleicons.org).'),
                    \App\Filament\Resources\Projects\Schemas\ProjectForm::visibleToggle(),
                ]),
        ]);
    }
}
