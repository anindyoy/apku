<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HidesFromAdminNavigation;
use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\Transaksi;
use BackedEnum;
use Carbon\CarbonImmutable;
use Dompdf\Dompdf;
use Dompdf\Options;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class Laporan extends Page
{
    use HidesFromAdminNavigation;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-pie';

    protected static string|UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Laporan Buku Kas';

    protected string $view = 'filament.pages.laporan';

    public string $periode = 'bulanan';

    public string $tanggalAcuan = '';

    public string $bulan = '';

    public int $tahun;

    public string $tanggalMulai = '';

    public string $tanggalSelesai = '';

    public string $bukuKasId = 'semua';

    public string $dompetId = 'semua';

    public function mount(): void
    {
        $hariIni = now()->toDateString();
        $this->tanggalAcuan = $hariIni;
        $this->bulan = now()->format('m');
        $this->tahun = now()->year;
        $this->tanggalMulai = now()->startOfMonth()->toDateString();
        $this->tanggalSelesai = $hariIni;
    }

    public function pilihPeriode(string $periode): void
    {
        if (in_array($periode, ['harian', 'bulanan', 'tahunan', 'custom'], true)) {
            $this->periode = $periode;
        }
    }

    public function geserPeriode(int $arah): void
    {
        if (! in_array($arah, [-1, 1], true) || $this->periode === 'custom') {
            return;
        }

        if ($this->periode === 'harian') {
            $this->tanggalAcuan = CarbonImmutable::parse($this->tanggalAcuan)->addDays($arah)->toDateString();

            return;
        }

        if ($this->periode === 'tahunan') {
            $this->tahun += $arah;

            return;
        }

        $periode = CarbonImmutable::create($this->tahun, (int) $this->bulan, 1)->addMonths($arah);
        $this->bulan = $periode->format('m');
        $this->tahun = $periode->year;
    }

    /** @return array<string, mixed> */
    public function getDataLaporanProperty(): array
    {
        [$mulai, $selesai] = $this->rentangTanggal();
        $query = $this->queryTransaksi();
        $transaksiPeriode = (clone $query)
            ->with(['jenis_transaksi:id,nama_jenis', 'buku_kas:id,nama_buku', 'dompet' => fn ($query) => $query->withTrashed()])
            ->whereBetween('tanggal', [$mulai, $selesai])
            ->orderBy('tanggal')
            ->get();

        $transaksiArusKas = $transaksiPeriode->reject(fn (Transaksi $item): bool => $item->tipe_transfer === 'dompet');
        $pemasukan = $transaksiArusKas->whereIn('jenis', ['Pemasukan', 'Transfer Pemasukan'])->sum('nominal');
        $pengeluaran = $transaksiArusKas->whereIn('jenis', ['Pengeluaran', 'Transfer Pengeluaran'])->sum('nominal');
        $saldoSekarang = $this->dompetId !== 'semua'
            ? (int) Dompet::withTrashed()->whereKey($this->dompetId)->sum('saldo')
            : (int) BukuKas::query()
                ->when($this->bukuKasId !== 'semua', fn (Builder $q) => $q->whereKey($this->bukuKasId))
                ->sum('saldo');
        $setelahMulai = (clone $query)->where('tanggal', '>=', $mulai)->get();
        $perubahanSetelahMulai = $this->perubahanSaldo($setelahMulai);
        $saldoAwal = $saldoSekarang - $perubahanSetelahMulai;
        $perubahanPeriode = $this->perubahanSaldo($transaksiPeriode);

        return [
            'mulai' => $mulai,
            'selesai' => $selesai,
            'label' => $this->labelPeriode($mulai, $selesai),
            'saldoAwal' => $saldoAwal,
            'pemasukan' => $pemasukan,
            'pengeluaran' => $pengeluaran,
            'akumulasi' => $pemasukan - $pengeluaran,
            'saldoAkhir' => $saldoAwal + $perubahanPeriode,
            'kategoriPemasukan' => $this->ringkasanKategori($transaksiPeriode, 'Pemasukan'),
            'kategoriPengeluaran' => $this->ringkasanKategori($transaksiPeriode, 'Pengeluaran'),
            'transaksi' => $transaksiPeriode,
        ];
    }

    public function unduhPdf(): StreamedResponse
    {
        $data = $this->dataEkspor();
        $opsi = new Options;
        $opsi->set('defaultFont', 'DejaVu Sans');

        $pdf = new Dompdf($opsi);
        $pdf->loadHtml(view('laporan.pdf', $data)->render());
        $pdf->setPaper('a4', 'landscape');
        $pdf->render();

        return response()->streamDownload(
            static fn () => print $pdf->output(),
            $this->namaFileLaporan('pdf'),
            ['Content-Type' => 'application/pdf']
        );
    }

    public function unduhExcel(): BinaryFileResponse
    {
        $data = $this->dataEkspor();
        $lokasi = tempnam(sys_get_temp_dir(), 'laporan-');
        $penulis = new Writer;
        $penulis->openToFile($lokasi);

        foreach ($this->barisExcel($data) as $baris) {
            $penulis->addRow(Row::fromValues($baris));
        }

        $penulis->close();

        return response()->download(
            $lokasi,
            $this->namaFileLaporan('xlsx'),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend();
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    private function rentangTanggal(): array
    {
        $acuan = $this->tanggalAcuan ? CarbonImmutable::parse($this->tanggalAcuan) : CarbonImmutable::today();

        if ($this->periode === 'custom') {
            $mulai = $this->tanggalMulai ? CarbonImmutable::parse($this->tanggalMulai) : $acuan->startOfMonth();
            $selesai = $this->tanggalSelesai ? CarbonImmutable::parse($this->tanggalSelesai) : $acuan;

            if ($mulai->greaterThan($selesai)) {
                [$mulai, $selesai] = [$selesai, $mulai];
            }

            return [$mulai->startOfDay(), $selesai->endOfDay()];
        }

        return match ($this->periode) {
            'harian' => [$acuan->startOfDay(), $acuan->endOfDay()],
            'tahunan' => [CarbonImmutable::create($this->tahun)->startOfYear(), CarbonImmutable::create($this->tahun)->endOfYear()],
            default => [
                CarbonImmutable::create($this->tahun, (int) $this->bulan)->startOfMonth(),
                CarbonImmutable::create($this->tahun, (int) $this->bulan)->endOfMonth(),
            ],
        };
    }

    private function queryTransaksi(): Builder
    {
        return Transaksi::query()
            ->when($this->bukuKasId !== 'semua', fn (Builder $q) => $q->where('buku_kas_id', $this->bukuKasId))
            ->when($this->dompetId !== 'semua', fn (Builder $q) => $q->where('dompet_id', $this->dompetId));
    }

    private function perubahanSaldo(Collection $transaksi): int
    {
        return (int) $transaksi->sum(fn (Transaksi $item) => in_array($item->jenis, ['Pemasukan', 'Transfer Pemasukan'], true)
            ? $item->nominal
            : -$item->nominal);
    }

    /** @return array<int, array{nama: string, nominal: int, warna: string, persen: float}> */
    private function ringkasanKategori(Collection $transaksi, string $jenis): array
    {
        $jenisYangDipilih = $jenis === 'Pemasukan'
            ? ['Pemasukan', 'Transfer Pemasukan']
            : ['Pengeluaran', 'Transfer Pengeluaran'];
        $warna = $jenis === 'Pemasukan'
            ? ['#4f8f72', '#79aa91', '#a4c7b4', '#d0e3d8', '#2f6f53']
            : ['#be5b62', '#d47b80', '#e4a1a5', '#f0c5c7', '#9e4149'];
        $ringkasan = $transaksi->reject(fn (Transaksi $item): bool => $item->tipe_transfer === 'dompet')
            ->whereIn('jenis', $jenisYangDipilih)
            ->groupBy(fn (Transaksi $item) => str_starts_with($item->jenis, 'Transfer')
                ? 'Transfer'
                : ($item->jenis_transaksi?->nama_jenis ?? 'Tanpa kategori'))
            ->map(fn (Collection $items, string $nama) => ['nama' => $nama, 'nominal' => (int) $items->sum('nominal')])
            ->sortByDesc('nominal')
            ->values();
        $total = max(1, (int) $ringkasan->sum('nominal'));

        return $ringkasan->map(fn (array $item, int $index) => [
            ...$item,
            'warna' => $warna[$index % count($warna)],
            'persen' => ($item['nominal'] / $total) * 100,
        ])->all();
    }

    private function labelPeriode(CarbonImmutable $mulai, CarbonImmutable $selesai): string
    {
        return match ($this->periode) {
            'harian' => $mulai->locale('id')->translatedFormat('d F Y'),
            'bulanan' => $mulai->locale('id')->translatedFormat('F Y'),
            'tahunan' => $mulai->format('Y'),
            default => $mulai->locale('id')->translatedFormat('d M Y').' – '.$selesai->locale('id')->translatedFormat('d M Y'),
        };
    }

    /** @return array{laporan: array<string, mixed>, namaBuku: string, namaDompet: string, tipePeriode: string} */
    private function dataEkspor(): array
    {
        return [
            'laporan' => $this->dataLaporan,
            'namaBuku' => $this->bukuKasId === 'semua'
                ? 'Semua Buku Kas'
                : BukuKas::findOrFail($this->bukuKasId)->nama_buku,
            'namaDompet' => $this->dompetId === 'semua'
                ? 'Semua Dompet'
                : Dompet::withTrashed()->findOrFail($this->dompetId)->nama_dompet,
            'tipePeriode' => Str::headline($this->periode),
        ];
    }

    /** @param array{laporan: array<string, mixed>, namaBuku: string, namaDompet: string, tipePeriode: string} $data */
    private function barisExcel(array $data): array
    {
        $laporan = $data['laporan'];
        $baris = [
            ['LAPORAN BUKU KAS'],
            ['Buku Kas', $data['namaBuku']],
            ['Dompet', $data['namaDompet']],
            ['Tipe Periode', $data['tipePeriode']],
            ['Periode', $laporan['label']],
            [],
            ['RINGKASAN'],
            ['Saldo Awal', $laporan['saldoAwal']],
            ['Pemasukan', $laporan['pemasukan']],
            ['Pengeluaran', $laporan['pengeluaran']],
            ['Akumulasi', $laporan['akumulasi']],
            ['Saldo Akhir', $laporan['saldoAkhir']],
            [],
            ['RINCIAN TRANSAKSI'],
            ['Tanggal', 'Buku Kas', 'Dompet', 'Jenis', 'Kategori', 'Deskripsi', 'Nominal'],
        ];

        foreach ($laporan['transaksi'] as $transaksi) {
            $baris[] = [
                CarbonImmutable::parse($transaksi->tanggal)->format('d/m/Y H:i'),
                $transaksi->buku_kas?->nama_buku ?? '-',
                $transaksi->labelDompetUntuk(auth()->user()),
                $transaksi->jenis,
                str_starts_with($transaksi->jenis, 'Transfer') ? 'Transfer' : ($transaksi->jenis_transaksi?->nama_jenis ?? 'Tanpa kategori'),
                $transaksi->deskripsi ?? '',
                $transaksi->nominal,
            ];
        }

        return $baris;
    }

    private function namaFileLaporan(string $ekstensi): string
    {
        [$mulai, $selesai] = $this->rentangTanggal();

        return sprintf(
            'laporan-%s-%s-%s.%s',
            $this->periode,
            $mulai->format('Ymd'),
            $selesai->format('Ymd'),
            $ekstensi
        );
    }
}
