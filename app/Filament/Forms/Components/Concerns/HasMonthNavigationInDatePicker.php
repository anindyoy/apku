<?php

namespace App\Filament\Forms\Components\Concerns;

trait HasMonthNavigationInDatePicker
{
    public function toEmbeddedHtml(): string
    {
        $original = parent::toEmbeddedHtml();

        $locale = app()->getLocale();
        $previousLabel = $locale === 'id' ? 'Bulan sebelumnya' : 'Previous month';
        $nextLabel = $locale === 'id' ? 'Bulan berikutnya' : 'Next month';

        $headerPattern = '/<div class="fi-fo-date-time-picker-panel-header">(.*?)<\/div>/s';

        $newHeader = <<<HTML
<div class="fi-fo-date-time-picker-panel-header">
                                    <button
                                        type="button"
                                        x-on:click="focusPreviousMonth()"
                                        class="fi-fo-date-time-picker-nav-button fi-fo-date-time-picker-nav-button-prev"
                                        aria-label="{$previousLabel}"
                                    >
                                        <svg class="fi-fo-date-time-picker-nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    $1
                                    <button
                                        type="button"
                                        x-on:click="focusNextMonth()"
                                        class="fi-fo-date-time-picker-nav-button fi-fo-date-time-picker-nav-button-next"
                                        aria-label="{$nextLabel}"
                                    >
                                        <svg class="fi-fo-date-time-picker-nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
HTML;

        $original = preg_replace($headerPattern, $newHeader, $original, 1);

        $original = str_replace(
            'dateTimePickerFormComponent({',
            'dateTimePickerFormComponentWithMonthNav({',
            $original
        );

        return $original;
    }
}
