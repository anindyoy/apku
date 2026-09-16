@php
    $record = $getRecord();
    $warna = \App\Filament\Resources\TransaksiResource::getWarnaTipeTransaksi($record->jenis, $record->tipe_transfer);
    $aktivitas = \App\Filament\Resources\TransaksiResource::getKategoriLabel($record);
@endphp

<div class="transaction-mobile-summary" data-transaksi-mobile data-tone="{{ $warna }}">
    <div class="transaction-mobile-main">
        <span class="transaction-mobile-type">{{ $record->jenis }}</span>
        <span class="transaction-mobile-amount">Rp {{ number_format((float) $record->nominal, 0, ',', '.') }}</span>
    </div>
    <div class="transaction-mobile-detail">
        <span class="transaction-mobile-activity">{{ $aktivitas ?: 'Tanpa aktivitas' }}</span>
        <span class="transaction-mobile-date">{{ date('d M Y, H:i', strtotime($record->tanggal)) }}</span>
    </div>
</div>
