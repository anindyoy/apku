<?php

namespace App\Filament\Forms;

use Filament\Forms\Components\Select;
use Filament\Tables\Filters\SelectFilter;
use Throwable;

class SelectKasDanSumberDana
{
    /**
     * Batas jumlah opsi sebelum fitur pencarian dan pramuat diaktifkan.
     * Fitur aktif hanya jika jumlah opsi lebih dari ambang ini (> 7).
     */
    public const AMBANG_SEARCHABLE = 7;

    /**
     * Terapkan searchable dan preload kondisional pada input Select kas atau sumber dana.
     */
    public static function terapkan(Select $select): Select
    {
        return $select
            ->searchable(fn (Select $component): bool => static::harusSearchable($component))
            ->preload(fn (Select $component): bool => static::harusSearchable($component));
    }

    /**
     * Terapkan searchable dan preload kondisional pada filter SelectFilter kas atau sumber dana.
     */
    public static function terapkanFilter(SelectFilter $filter): SelectFilter
    {
        return $filter
            ->searchable(fn (SelectFilter $component): bool => static::harusSearchable($component))
            ->preload(fn (SelectFilter $component): bool => static::harusSearchable($component));
    }

    /**
     * Periksa apakah komponen memiliki opsi lebih dari ambang batas (> 7).
     */
    public static function harusSearchable(Select|SelectFilter $component): bool
    {
        try {
            $options = $component->getOptions();

            return is_array($options) && count($options) > static::AMBANG_SEARCHABLE;
        } catch (Throwable) {
            return false;
        }
    }
}
