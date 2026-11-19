<?php

namespace App\Filament\Forms\Components;

use Devletes\ChronoPicker\Forms\Components\DatePicker as BaseDatePicker;

class DatePicker extends BaseDatePicker
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->native(false);
    }
}
