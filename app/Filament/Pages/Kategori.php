<?php

namespace App\Filament\Pages;

use BackedEnum;
use UnitEnum;
use App\Models\JenisTransaksi;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;

class Kategori extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-tag';
    protected static string | UnitEnum | null $navigationGroup = 'Pengaturan';
    protected static ?int $navigationSort = 2;
    protected string $view = 'filament.pages.kategori';
}
