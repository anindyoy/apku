<x-filament-panels::page>
    @php
        $infoTrialAkun = $this->infoTrialPremium();
        $trialAkun = $infoTrialAkun['trial'] ?? null;
    @endphp
    @if ($trialAkun)
        @include('filament.components.trial-premium-banner', ['trial' => [
            'sisa' => $trialAkun->sisaHari(),
            'berakhir' => $trialAkun->berakhir_pada?->translatedFormat('d F Y'),
        ]])
    @else
        @include('filament.components.trial-premium-cta', ['info' => $infoTrialAkun])
    @endif

    {{ $this->form }}

    <x-filament::button wire:click="submit">
        Simpan
    </x-filament::button>
</x-filament-panels::page>
