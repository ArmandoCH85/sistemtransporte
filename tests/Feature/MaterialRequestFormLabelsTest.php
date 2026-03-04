<?php

use App\Filament\Resources\MaterialRequestResource;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Support\Contracts\TranslatableContentDriver;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

it('uses referencia labels for pickup and delivery address fields', function (): void {
    $livewire = new class implements HasForms {
        public function dispatchFormEvent(mixed ...$args): void {}
        public function getActiveFormsLocale(): ?string { return null; }
        public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver { return null; }
        public function getForm(string $name): ?Form { return null; }
        public function getFormComponentFileAttachment(string $statePath): ?TemporaryUploadedFile { return null; }
        public function getFormComponentFileAttachmentUrl(string $statePath): ?string { return null; }
        public function getFormSelectOptionLabels(string $statePath): array { return []; }
        public function getFormSelectOptionLabel(string $statePath): ?string { return null; }
        public function getFormSelectOptions(string $statePath): array { return []; }
        public function getFormSelectSearchResults(string $statePath, string $search): array { return []; }
        public function getFormUploadedFiles(string $statePath): ?array { return null; }
        public function getOldFormState(string $statePath): mixed { return null; }
        public function isCachingForms(): bool { return false; }
        public function removeFormUploadedFile(string $statePath, string $fileKey): void {}
        public function reorderFormUploadedFiles(string $statePath, array $fileKeys): void {}
        public function validate($rules = null, $messages = [], $attributes = []): array { return []; }
    };

    $form = MaterialRequestResource::form(Form::make($livewire));
    $fields = $form->getFlatFields(true);

    expect($fields['pickup_address']->getLabel())->toBe('Referencia');
    expect($fields['delivery_address']->getLabel())->toBe('Referencia');

    $pickupOptions = $fields['pickup_location']->getOptions();
    $deliveryOptions = $fields['delivery_location']->getOptions();

    expect($pickupOptions)->not->toHaveKey('torre_hospitalaria_385');
    expect($pickupOptions)->not->toHaveKey('torre_ambulatoria_421');
    expect($deliveryOptions)->not->toHaveKey('torre_hospitalaria_385');
    expect($deliveryOptions)->not->toHaveKey('torre_ambulatoria_421');
});
