<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * A page that edits one database row (profile, settings) with a Save button.
 */
abstract class SingleRecordPage extends Page
{
    /** @var array<string, mixed>|null */
    public ?array $data = [];

    abstract protected function getRecord(): Model;

    abstract protected function getFormComponents(): array;

    public function mount(): void
    {
        $this->form->fill($this->getRecord()->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->operation('edit')
            ->model($this->getRecord())
            ->statePath('data')
            ->components($this->getFormComponents());
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Save changes')->submit('save')->keyBindings(['mod+s']),
                    ])->key('form-actions'),
                ]),
        ]);
    }

    public function save(): void
    {
        $record = $this->getRecord();
        $record->fill($this->form->getState())->save();

        // Refill so the form shows the stored values (e.g. the WebP path).
        $this->form->fill($record->fresh()->attributesToArray());

        Notification::make()->success()->title('Saved')->send();
    }
}
