<?php

namespace App\Filament\Resources\PageSections\Pages;

use App\Filament\Resources\PageSections\PageSectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPageSections extends ListRecords
{
    protected static string $resource = PageSectionResource::class;

    protected ?string $subheading = 'Sections below the hero, in this order. Drag to reorder, switch off to hide. Built-in sections can be hidden but not deleted.';

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Add section')];
    }
}
