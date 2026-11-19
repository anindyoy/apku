<?php

namespace App\Filament\Forms\Components;

use App\Filament\Forms\Components\Concerns\HasMonthNavigationInDatePicker;
use Filament\Forms\Components\DatePicker as BaseDatePicker;

class DatePicker extends BaseDatePicker
{
    use HasMonthNavigationInDatePicker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->native(false);
    }
}
