@php
    $record = $getRecord();
    $warna = \App\Filament\Resources\TransaksiResource::getWarnaTipeTransaksi($record->jenis, $record->tipe_transfer);
    $kategori = \App\Filament\Resources\TransaksiResource::getKategoriLabel($record);

    $livewire = $livewire ?? ($component?->getLivewire() ?? null);

    $filterKas = $livewire->filterBukuKas ?? null;
    $filterDompet = $livewire->filterDompet ?? null;

    if (! $filterKas && isset($livewire->tableFilters['buku_kas_id']['value'])) {
        $filterKas = $livewire->tableFilters['buku_kas_id']['value'];
    }

    if (! $filterDompet && isset($livewire->tableFilters['dompet_id']['value'])) {
        $filterDompet = $livewire->tableFilters['dompet_id']['value'];
    }
@endphp

<div class="transaction-mobile-summary" data-transaksi-mobile data-tone="{{ $warna }}">
    <div class="transaction-mobile-main">
        <span class="transaction-mobile-type">{{ $record->jenis }}</span>
        <span class="transaction-mobile-amount">Rp {{ number_format((float) $record->nominal, 0, ',', '.') }}</span>
    </div>
    <div class="transaction-mobile-detail">
        <span class="transaction-mobile-activity">{{ $kategori ?: 'Tanpa kategori' }}</span>
        <span class="transaction-mobile-date">{{ date('d M Y, H:i', strtotime($record->tanggal)) }}</span>
    </div>
    @if ($record->deskripsi)
        <div class="transaction-mobile-detail">
            <span class="transaction-mobile-activity">Deskripsi: {{ $record->deskripsi }}</span>
        </div>
    @endif
    <div class="transaction-mobile-detail">
        @if ($record->tipe_transfer !== 'dompet')
            <span class="transaction-mobile-activity">Kas: {{ $record->buku_kas?->nama_buku ?? '-' }}</span>
        @endif
        <span class="transaction-mobile-activity">Sumber Dana: {{ $record->sumberDana?->nama_dompet ?? '-' }}</span>
    </div>
    @if ($filterKas && ! is_null($record->saldo))
        <div class="transaction-mobile-detail">
            <span class="transaction-mobile-activity">Saldo kas: Rp {{ number_format((float) $record->saldo, 0, ',', '.') }}</span>
        </div>
    @elseif ($filterDompet && ! is_null($record->saldo_dompet))
        <div class="transaction-mobile-detail">
            <span class="transaction-mobile-activity">Saldo sumber dana: Rp {{ number_format((float) $record->saldo_dompet, 0, ',', '.') }}</span>
        </div>
    @endif
</div>
