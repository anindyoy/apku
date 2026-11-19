<?php

namespace App\Filament\Forms\Components;

use Devletes\ChronoPicker\Forms\Components\DateTimePicker as BaseDateTimePicker;

class DateTimePicker extends BaseDateTimePicker
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->native(false);
        $this->default(fn (): \Illuminate\Support\Carbon => now());
    }
}
