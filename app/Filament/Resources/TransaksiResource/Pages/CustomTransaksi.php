<?php

namespace App\Filament\Resources\TransaksiResource\Pages;

use App\Filament\Resources\TransaksiResource;
use Filament\Resources\Pages\Page;

class CustomTransaksi extends Page
{
    protected static string $resource = TransaksiResource::class;

    protected static string $view = 'filament.resources.transaksi-resource.pages.custom-transaksi';
}
