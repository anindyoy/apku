<?php

namespace App\Filament\Forms\Components;

use App\Filament\Forms\Components\Concerns\HasMonthNavigationInDatePicker;
use Filament\Forms\Components\DateTimePicker as BaseDateTimePicker;

class DateTimePicker extends BaseDateTimePicker
{
    use HasMonthNavigationInDatePicker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->native(false);
    }
}
