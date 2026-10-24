<?php

namespace App\Filament\Resources\PageSections\Pages;

use App\Filament\Resources\PageSections\PageSectionResource;
use App\Models\PageSection;
use Filament\Resources\Pages\CreateRecord;

class CreatePageSection extends CreateRecord
{
    protected static string $resource = PageSectionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['sort_order'] = (int) PageSection::max('sort_order') + 1;

        return $data;
    }
}
