<?php

namespace App\Filament\Resources\MaterialRequestResource\Pages;

use App\Filament\Resources\MaterialRequestResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;

class ListMaterialRequests extends ListRecords
{
    protected static string $resource = MaterialRequestResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            ViewAction::make()
                ->label('Ver Detalle')
                ->icon('heroicon-o-eye')
                ->color('info'),

            DeleteAction::make()
                ->label('Eliminar')
                ->icon('heroicon-o-trash')
                ->color('danger'),

            EditAction::make()
                ->label('Editar')
                ->icon('heroicon-o-pencil')
                ->color('warning'),
        ];
    }
}
