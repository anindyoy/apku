@vite('resources/css/filament-toolbar.css')
<span
    data-kuota-status="{{ $status }}"
    @class([
        'inline-block max-w-full rounded-lg px-3 py-2 text-left text-xs font-medium leading-5',
        'bg-red-100 text-red-900 [.dark_&]:bg-red-950 [.dark_&]:text-red-200' => $status === 'limit',
        'bg-yellow-100 text-yellow-900 [.dark_&]:bg-yellow-950 [.dark_&]:text-yellow-200' => $status === 'warning',
        'bg-gray-100 text-gray-700 [.dark_&]:bg-gray-800 [.dark_&]:text-gray-200' => $status === 'normal',
    ])
>
    {{ $pesan }}
</span>
