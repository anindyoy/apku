<div class="fi-resource-relation-manager">
    @vite('resources/css/filament-toolbar.css')

    <form wire:submit="tambahKode" class="mb-3" data-voucher-code-create>
        <label for="kode-baru-{{ $this->getId() }}" class="sr-only">
            Kode voucher baru
        </label>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start">
            <div class="min-w-0 flex-1">
                <x-filament::input.wrapper :valid="! $errors->has('kodeBaru')">
                    <x-filament::input
                        id="kode-baru-{{ $this->getId() }}"
                        wire:model="kodeBaru"
                        wire:loading.attr="disabled"
                        wire:target="tambahKode"
                        maxlength="255"
                        required
                        autocomplete="off"
                        placeholder="Contoh: HEMAT25"
                        aria-describedby="kode-baru-error-{{ $this->getId() }}"
                    />
                </x-filament::input.wrapper>
                @error('kodeBaru')
                    <p id="kode-baru-error-{{ $this->getId() }}" role="alert" class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                @enderror
            </div>
            <x-filament::button class="w-full shrink-0 sm:w-auto" type="submit" icon="heroicon-o-plus" wire:loading.attr="disabled" wire:target="tambahKode">
                Buat kode baru
            </x-filament::button>
        </div>
    </form>

    {{ $this->content }}

    <x-filament-panels::unsaved-action-changes-alert />
</div>
