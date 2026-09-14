<x-filament-panels::page>
    <form wire:submit="simpan" class="space-y-6">
        {{ $this->form }}
        <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="simpan">
            Simpan pengaturan
        </x-filament::button>
    </form>
</x-filament-panels::page>
