<?php

namespace App\Filament\Resources\MaterialRequestResource\Pages;

use App\Filament\Resources\MaterialRequestResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMaterialRequest extends EditRecord
{
    protected static string $resource = MaterialRequestResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
