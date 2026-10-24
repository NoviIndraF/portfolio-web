<?php

namespace App\Filament\Resources\SkillGroups\Pages;

use App\Filament\Resources\SkillGroups\SkillGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSkillGroups extends ListRecords
{
    protected static string $resource = SkillGroupResource::class;

    protected ?string $subheading = 'Drag rows to change the order of skill groups.';

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
