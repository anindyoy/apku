<?php

namespace App\Livewire;

use App\Enums\StatusLangganan;
use App\Models\Langganan;
use App\Models\MetodePembayaran;
use App\Models\PaketLangganan;
use App\Models\VoucherCode;
use App\Services\BuatOrderLangganan;
use App\Services\Langganan\HitungHematPaket;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Js;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class PilihPaketLangganan extends Component
{
    // Paket yang dipilih untuk modal konfirmasi.
    public ?int $paketId = null;

    // Metode pembayaran yang dipilih di modal.
    public ?int $metodeId = null;

    // Kode voucher yang diketik pengguna (belum tentu valid).
    public string $kodeVoucher = '';

    // Hasil validasi voucher untuk ringkasan (id kode dan persen diskon).
    public ?int $voucherCodeId = null;

    public int $voucherPersen = 0;

    // Pesan status voucher untuk dibaca pengguna.
    public string $pesanVoucher = '';

    public bool $voucherValid = false;

    // Status modal konfirmasi.
    public bool $modalTerbuka = false;

    // Membuka modal untuk paket tertentu dan mereset status voucher.
    public function pilihPaket(int $id): void
    {
        // Pastikan paket masih aktif agar paket yang dinonaktifkan tidak bisa dipesan.
        $paket = PaketLangganan::query()->where('is_active', true)->findOrFail($id);

        $this->paketId = $paket->id;
        $this->kodeVoucher = '';
        $this->voucherCodeId = null;
        $this->voucherPersen = 0;
        $this->pesanVoucher = '';
        $this->voucherValid = false;

        // Pilih metode pertama sebagai bawaan agar pengguna tinggal konfirmasi.
        // Validasi ulang pilihan lama karena daftar metode aktif dapat berubah.
        $metodeAktif = $this->daftarMetode();
        if ($this->metodeId === null || ! $metodeAktif->contains('id', $this->metodeId)) {
            $this->metodeId = $metodeAktif->first()?->id;
        }

        $this->modalTerbuka = true;
    }

    // Mereset status voucher saat kode diubah agar ringkasan tidak memakai diskon basi.
    public function updatedKodeVoucher(): void
    {
        $this->voucherCodeId = null;
        $this->voucherPersen = 0;
        $this->voucherValid = false;
        $this->pesanVoucher = '';
    }

    // Menutup modal dan mengembalikan fokus ke tombol Beli lewat event browser.
    public function tutupModal(): void
    {
        $paketId = $this->paketId;
        $this->modalTerbuka = false;
        $this->dispatch('modal-paket-ditutup', paketId: $paketId);
    }

    // Memvalidasi kode voucher di server dan memperbarui ringkasan.
    public function terapkanVoucher(): void
    {
        $kode = strtoupper(trim($this->kodeVoucher));

        if ($kode === '') {
            $this->voucherCodeId = null;
            $this->voucherPersen = 0;
            $this->voucherValid = false;
            $this->pesanVoucher = '';

            return;
        }

        $kodeModel = VoucherCode::query()->with('voucher')->where('code', $kode)->first();

        if (! $kodeModel) {
            $this->voucherCodeId = null;
            $this->voucherPersen = 0;
            $this->voucherValid = false;
            $this->pesanVoucher = 'Kode voucher tidak ditemukan. Periksa penulisannya.';

            return;
        }

        if (! $kodeModel->voucher->masihBerlaku()) {
            $this->voucherCodeId = null;
            $this->voucherPersen = 0;
            $this->voucherValid = false;
            $this->pesanVoucher = 'Voucher sudah kedaluwarsa.';

            return;
        }

        if (! $kodeModel->voucher->dapat_dipakai_berulang
            && $kodeModel->langganans()->where('status', '!=', StatusLangganan::Dibatalkan->value)->exists()) {
            $this->voucherCodeId = null;
            $this->voucherPersen = 0;
            $this->voucherValid = false;
            $this->pesanVoucher = 'Kode voucher sudah pernah dipakai.';

            return;
        }

        $this->voucherCodeId = $kodeModel->id;
        $this->voucherPersen = (int) $kodeModel->voucher->jumlah_diskon;
        $this->voucherValid = true;
        $this->pesanVoucher = 'Voucher dipakai: diskon '.$this->voucherPersen.'%.';
    }

    // Membuat order lewat alur yang sudah ada; total dihitung ulang di server.
    public function buatOrder(): void
    {
        Gate::authorize('create', Langganan::class);
        $pengguna = auth()->user();

        // Pengguna wajib login; Gate di atas sudah menolak admin dan tamu.
        if (! $pengguna) {
            $this->addError('paketId', 'Silakan login kembali untuk membuat order.');

            return;
        }

        $this->validate([
            'paketId' => ['required', 'integer'],
            'metodeId' => ['required', 'integer'],
        ], [
            'paketId.required' => 'Pilih paket terlebih dahulu.',
            'metodeId.required' => 'Pilih metode pembayaran terlebih dahulu.',
        ]);

        // Validasi ulang paket dan metode aktif agar perubahan admin saat modal terbuka ikut terbaca.
        $paket = PaketLangganan::query()->where('is_active', true)->find($this->paketId);
        if (! $paket) {
            $this->addError('paketId', 'Paket langganan tidak aktif.');
            Notification::make()->title('Paket tidak tersedia')->body('Paket tersebut sudah dinonaktifkan admin. Pilih paket lain.')->danger()->send();

            return;
        }

        $metode = MetodePembayaran::query()->where('is_active', true)->find($this->metodeId);
        if (! $metode) {
            $this->addError('metodeId', 'Metode pembayaran tidak aktif.');
            $this->metodeId = $this->daftarMetode()->first()?->id;

            return;
        }

        $kodeModel = null;
        if (filled($this->kodeVoucher)) {
            $kodeModel = VoucherCode::query()->with('voucher')->where('code', strtoupper(trim($this->kodeVoucher)))->first();

            if (! $kodeModel) {
                $this->voucherValid = false;
                $this->pesanVoucher = 'Kode voucher tidak ditemukan. Periksa penulisannya.';
                $this->addError('kodeVoucher', $this->pesanVoucher);

                return;
            }
        }

        try {
            $order = app(BuatOrderLangganan::class)->handle($pengguna, $paket, $metode, $kodeModel);
        } catch (ValidationException $e) {
            // Tampilkan pesan di dalam modal agar pengguna dapat memperbaiki tanpa kehilangan isian.
            $galat = $e->errors();
            $pesan = collect($galat)->flatten()->first() ?? 'Order tidak dapat dibuat.';
            $this->pesanVoucher = (string) $pesan;
            $this->voucherValid = false;
            foreach ($galat as $kolom => $daftarPesan) {
                $tujuan = str_contains((string) $kolom, 'voucher') || str_contains((string) $kolom, 'kode') ? 'kodeVoucher' : 'metodeId';
                foreach ((array) $daftarPesan as $pesanKolom) {
                    $this->addError($tujuan, (string) $pesanKolom);
                }
            }

            return;
        }

        $urlInvoice = route('langganan.invoice', $order);

        $this->modalTerbuka = false;
        $this->reset(['paketId', 'kodeVoucher', 'voucherCodeId', 'voucherPersen', 'pesanVoucher', 'voucherValid']);

        Notification::make()
            ->title('Order langganan berhasil dibuat')
            ->body('Invoice dibuka di tab baru. Gunakan Lihat invoice jika tab tidak terbuka otomatis.')
            ->success()
            ->actions([
                Action::make('lihatInvoice')
                    ->label('Lihat invoice')
                    ->url($urlInvoice, shouldOpenInNewTab: true)
                    ->button(),
            ])
            ->send();

        // Buka invoice di tab baru seperti alur order yang sudah ada; notifikasi menyediakan tombol cadangan.
        $this->js('window.open('.Js::from($urlInvoice).", '_blank', 'noopener')");
        $this->dispatch('order-langganan-dibuat');
    }

    // Daftar paket aktif yang sudah dihitung hematnya (urutan terpanjang dulu).
    public function paketHemat(): Collection
    {
        $paket = PaketLangganan::query()
            ->where('is_active', true)
            ->orderByDesc('durasi_hari')
            ->get();

        return app(HitungHematPaket::class)->handle($paket);
    }

    // Metode pembayaran aktif untuk pilihan di modal.
    public function daftarMetode(): Collection
    {
        return MetodePembayaran::query()
            ->where('is_active', true)
            ->orderBy('urutan')
            ->orderBy('label')
            ->get();
    }

    // Ringkasan harga untuk modal (pratinjau; total final dihitung ulang saat order).
    public function ringkasan(?Collection $paketHemat = null): ?array
    {
        $paket = ($paketHemat ?? $this->paketHemat())->firstWhere('id', $this->paketId);

        if (! $paket) {
            return null;
        }

        $persen = $this->voucherValid ? $this->voucherPersen : 0;
        $potongan = intdiv((int) $paket['harga'] * $persen, 100);

        return [
            'paket' => $paket,
            'persen' => $persen,
            'potongan' => $potongan,
            'total' => (int) $paket['harga'] - $potongan,
        ];
    }

    public function render()
    {
        // Hitung sekali agar grid, ringkasan, dan label hemat memakai data yang sama.
        $paketHemat = $this->paketHemat();

        return view('livewire.pilih-paket-langganan', [
            'paketHemat' => $paketHemat,
            'metode' => $this->daftarMetode(),
            'ringkasan' => $this->ringkasan($paketHemat),
            'sudahPremium' => auth()->user()?->masaAktifBerlaku() ?? false,
            'hargaTermurah' => $paketHemat->min('per_bulan'),
        ]);
    }
}
