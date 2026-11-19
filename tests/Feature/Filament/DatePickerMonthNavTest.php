<?php

namespace Tests\Feature\Filament;

use App\Filament\Forms\Components\DatePicker;
use App\Filament\Forms\Components\DateTimePicker;
use App\Models\User;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Livewire\Component;
use Livewire\Livewire;
use Tests\TestCase;

class DatePickerMonthNavTest extends Component implements HasForms
{
    use InteractsWithForms;

    public ?string $tanggal = null;

    public ?string $waktu = null;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('tanggal'),
                DateTimePicker::make('waktu')->seconds(false),
            ]);
    }

    public function render()
    {
        return view('livewire.date-picker-month-nav-test');
    }
}

class DatePickerMonthNavRenderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_date_picker_menyertakan_tombol_navigasi_bulan(): void
    {
        $response = Livewire::test(DatePickerMonthNavTest::class);

        $html = $response->html();

        $this->assertStringContainsString('dateTimePickerFormComponentWithMonthNav', $html);
        $this->assertStringContainsString('focusPreviousMonth', $html);
        $this->assertStringContainsString('focusNextMonth', $html);
        $this->assertStringContainsString('fi-fo-date-time-picker-nav-button-prev', $html);
        $this->assertStringContainsString('fi-fo-date-time-picker-nav-button-next', $html);
        $this->assertStringContainsString('dateTimePickerFormComponentWithMonthNav', $html);
    }
}
