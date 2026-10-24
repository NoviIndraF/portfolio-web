<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use Filament\Resources\Pages\CreateRecord;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // New projects go to the end of the list.
        $data['sort_order'] = (int) Project::max('sort_order') + 1;

        return ProjectResource::cleanRecognition($data);
    }

    protected function getRedirectUrl(): string
    {
        // Go straight to edit so images can be added.
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
