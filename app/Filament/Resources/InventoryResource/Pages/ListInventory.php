<?php

namespace App\Filament\Resources\InventoryResource\Pages;

use App\Filament\Resources\InventoryResource;
use Filament\Resources\Pages\ListRecords;

class ListInventory extends ListRecords
{
    protected static string $resource = InventoryResource::class;

    protected ?string $subheading = 'Un depósito por instalación. Stock disponible = físico − reservado. Los servicios no ingresan al inventario.';
}
