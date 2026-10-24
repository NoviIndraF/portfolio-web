<?php

namespace App\Filament\Resources\SkillGroups\Pages;

use App\Filament\Resources\SkillGroups\SkillGroupResource;
use App\Models\SkillGroup;
use Filament\Resources\Pages\CreateRecord;

class CreateSkillGroup extends CreateRecord
{
    protected static string $resource = SkillGroupResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['sort_order'] = (int) SkillGroup::max('sort_order') + 1;

        return $data;
    }
}
