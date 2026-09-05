<?php

namespace App\Services;

use App\Jobs\ProsesImportTransaksi;
use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\ImportTransaksi;
use App\Models\JenisTransaksi;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Throwable;

class ImportTransaksiService
{
    public const BATAS_BARIS_LANGSUNG = 1000;

    public const BATAS_BARIS = 10000;

    public const BATAS_UKURAN_FILE = 10 * 1024 * 1024;

    private const HEADER = ['tanggal', 'jenis', 'buku_kas', 'dompet', 'kategori', 'nominal', 'deskripsi'];

    private const KOLOM_WAJIB = ['tanggal', 'jenis', 'buku_kas', 'dompet', 'kategori', 'nominal'];

    private const ALIAS_HEADER = [
        'tanggal' => ['tanggal', 'date', 'datetime', 'transaction_date', 'waktu'],
        'jenis' => ['jenis', 'tipe', 'type', 'transaction_type'],
        'buku_kas' => ['buku_kas', 'buku', 'kas', 'book', 'account'],
        'dompet' => ['dompet', 'wallet', 'rekening'],
        'kategori' => ['kategori', 'category'],
        'nominal' => ['nominal', 'amount', 'jumlah', 'value'],
        'deskripsi' => ['deskripsi', 'description', 'keterangan', 'note', 'memo'],
    ];

    public function buatTemplateXlsx(string $path): void
    {
        $writer = new XlsxWriter;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues(self::HEADER));
        $writer->addRow(Row::fromValues([
            now()->startOfDay()->format('Y-m-d H:i'),
            'Pemasukan',
            'Kas Utama',
            'Cash',
            'Gaji',
            5000000,
            'Gaji bulan berjalan',
        ]));
        $writer->close();
    }

    /** @return array<string, string> */
    public function bacaHeader(UploadedFile|TemporaryUploadedFile|string $file, ?string $namaFile = null): array
    {
        [$path, $namaFile] = $this->informasiFile($file, $namaFile);
        $extension = $this->validasiFile($path, $namaFile);
        $reader = $extension === 'csv' ? new CsvReader : new XlsxReader;

        try {
            $reader->open($path);

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $nilai = array_map(fn ($cell) => $cell->getValue(), $row->getCells());

                    if ($this->barisKosong($nilai)) {
                        continue;
                    }

                    $header = array_map(fn ($value): string => $this->normalisasiHeader($value), $nilai);
                    $this->pastikanHeaderDasarValid($header);

                    return array_combine($header, array_map(fn ($value): string => trim((string) $value), $nilai));
                }

                break;
            }
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw ValidationException::withMessages(['file' => 'Header file tidak dapat dibaca.']);
        } finally {
            $reader->close();
        }

        throw ValidationException::withMessages(['file' => 'File tidak memiliki header.']);
    }

    /**
     * @param  array<string, string>  $header
     * @return array<string, string>
     */
    public function sarankanPemetaan(array $header): array
    {
        $tersedia = array_keys($header);
        $pemetaan = [];

        foreach (self::ALIAS_HEADER as $tujuan => $alias) {
            $sumber = collect($alias)->first(fn (string $nama): bool => in_array($nama, $tersedia, true));

            if ($sumber !== null) {
                $pemetaan[$tujuan] = $sumber;
            }
        }

        return $pemetaan;
    }

    /**
     * @return array{baris: array<int, array<string, mixed>>, baris_error: array<int, array{nomor_baris: int, data: array<string, mixed>, pesan: string}>, kategori_baru: array<string, array<string>>, errors: array<int, string>, jumlah_baris: int, total_pemasukan: int, total_pengeluaran: int, hash_file: string}
     */
    public function pratinjau(
        User $user,
        UploadedFile|TemporaryUploadedFile|string $file,
        ?string $namaFile = null,
        array $pemetaan = [],
        bool $buatKategoriOtomatis = false,
    ): array {
        [$path, $namaFile] = $this->informasiFile($file, $namaFile);
        $extension = $this->validasiFile($path, $namaFile);

        $hasil = [
            'baris' => [],
            'baris_error' => [],
            'kategori_baru' => [],
            'errors' => [],
            'jumlah_baris' => 0,
            'total_pemasukan' => 0,
            'total_pengeluaran' => 0,
            'hash_file' => hash_file('sha256', $path),
        ];

        $reader = $extension === 'csv' ? new CsvReader : new XlsxReader;

        try {
            $reader->open($path);
            $header = null;

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $nilai = array_map(fn ($cell) => $cell->getValue(), $row->getCells());

                    if ($this->barisKosong($nilai)) {
                        continue;
                    }

                    if ($header === null) {
                        $header = array_map(fn ($value): string => $this->normalisasiHeader($value), $nilai);
                        $this->pastikanHeaderDasarValid($header);
                        $pemetaan = $this->pastikanPemetaanValid(
                            $header,
                            $pemetaan === [] ? $this->sarankanPemetaan(array_fill_keys($header, '')) : $pemetaan,
                        );

                        continue;
                    }

                    $hasil['jumlah_baris']++;
                    $nomorBaris = $hasil['jumlah_baris'] + 1;

                    if ($hasil['jumlah_baris'] > self::BATAS_BARIS) {
                        throw ValidationException::withMessages([
                            'file' => 'File melebihi batas '.number_format(self::BATAS_BARIS, 0, ',', '.').' baris transaksi.',
                        ]);
                    }

                    $dataSumber = array_combine($header, array_slice(array_pad($nilai, count($header), null), 0, count($header)));
                    $dataMentah = collect(self::HEADER)->mapWithKeys(fn (string $tujuan): array => [
                        $tujuan => filled($pemetaan[$tujuan] ?? null) ? ($dataSumber[$pemetaan[$tujuan]] ?? null) : null,
                    ])->all();
                    [$data, $errors] = $this->validasiBaris(
                        $user, $dataMentah, $nomorBaris, $buatKategoriOtomatis,
                    );

                    if ($errors !== []) {
                        array_push($hasil['errors'], ...$errors);
                        $hasil['baris_error'][] = [
                            'nomor_baris' => $nomorBaris,
                            'data' => collect(self::HEADER)->mapWithKeys(fn (string $kolom): array => [
                                $kolom => $this->nilaiLaporan($dataMentah[$kolom] ?? null),
                            ])->all(),
                            'pesan' => implode(' | ', $errors),
                        ];

                        continue;
                    }

                    $hasil['baris'][] = $data;

                    if (filled($data['nama_kategori_baru'] ?? null)) {
                        $hasil['kategori_baru'][$data['jenis']] ??= [];
                        $hasil['kategori_baru'][$data['jenis']][$this->normalisasiNama($data['nama_kategori_baru'])] = $data['nama_kategori_baru'];
                    }

                    $kunciTotal = $data['jenis'] === 'Pemasukan' ? 'total_pemasukan' : 'total_pengeluaran';
                    $hasil[$kunciTotal] += $data['nominal'];
                }

                break;
            }
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw ValidationException::withMessages(['file' => 'Isi file tidak dapat dibaca. Pastikan file tidak rusak dan formatnya benar.']);
        } finally {
            $reader->close();
        }

        if ($header === null || $hasil['jumlah_baris'] === 0) {
            throw ValidationException::withMessages(['file' => 'File tidak memiliki baris transaksi.']);
        }

        if (ImportTransaksi::query()->where('user_id', $user->id)->where('hash_file', $hasil['hash_file'])->where('status', 'berhasil')->exists()) {
            $hasil['errors'][] = 'File yang sama sudah pernah berhasil diimpor.';
        }

        return $hasil;
    }

    public function buatLaporanErrorXlsx(
        User $user,
        UploadedFile|TemporaryUploadedFile|string $file,
        string $path,
        ?string $namaFile = null,
        array $pemetaan = [],
        bool $buatKategoriOtomatis = false,
    ): int {
        $hasil = $this->pratinjau($user, $file, $namaFile, $pemetaan, $buatKategoriOtomatis);

        if ($hasil['baris_error'] === []) {
            throw ValidationException::withMessages(['file' => 'File tidak memiliki baris yang perlu diperbaiki.']);
        }

        $writer = new XlsxWriter;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues([
            'baris', ...self::HEADER, 'kesalahan',
        ]));

        foreach ($hasil['baris_error'] as $baris) {
            $writer->addRow(Row::fromValues([
                $baris['nomor_baris'],
                ...array_map(fn (string $kolom): mixed => $baris['data'][$kolom] ?? null, self::HEADER),
                $baris['pesan'],
            ]));
        }

        $writer->close();

        return count($hasil['baris_error']);
    }

    /** @return array{jumlah_baris: int, total_pemasukan: int, total_pengeluaran: int} */
    public function impor(
        User $user,
        UploadedFile|TemporaryUploadedFile|string $file,
        ?string $namaFile = null,
        array $pemetaan = [],
        bool $buatKategoriOtomatis = false,
        ?ImportTransaksi $batchAntrean = null,
    ): array {
        [$path, $namaFile] = $this->informasiFile($file, $namaFile);
        $hasil = $this->pratinjau($user, $path, $namaFile, $pemetaan, $buatKategoriOtomatis);

        if ($hasil['errors'] !== []) {
            throw ValidationException::withMessages(['file' => $hasil['errors']]);
        }

        $prosesImport = function () use ($user, $namaFile, $hasil, $batchAntrean): array {
            $batch = $batchAntrean ?? ImportTransaksi::query()
                ->where('user_id', $user->id)
                ->where('hash_file', $hasil['hash_file'])
                ->lockForUpdate()
                ->first();

            if ($batch?->status === 'berhasil') {
                throw ValidationException::withMessages(['file' => 'File yang sama sudah pernah berhasil diimpor.']);
            }

            if ($batch) {
                $batch->update([
                    'nama_file' => Str::limit(basename($namaFile), 255, ''),
                    'jumlah_baris' => $hasil['jumlah_baris'],
                    'status' => 'berhasil',
                    'jumlah_diproses' => $hasil['jumlah_baris'],
                    'path_file' => null,
                    'pesan_error' => null,
                    'selesai_diproses_at' => now(),
                    'dibatalkan_at' => null,
                ]);
            } else {
                $batch = ImportTransaksi::query()->create([
                    'user_id' => $user->id,
                    'nama_file' => Str::limit(basename($namaFile), 255, ''),
                    'hash_file' => $hasil['hash_file'],
                    'jumlah_baris' => $hasil['jumlah_baris'],
                    'status' => 'berhasil',
                    'jumlah_diproses' => $hasil['jumlah_baris'],
                    'selesai_diproses_at' => now(),
                ]);
            }

            foreach ($hasil['baris'] as $data) {
                if (filled($data['nama_kategori_baru'] ?? null)) {
                    $kategori = JenisTransaksi::withoutGlobalScopes()->firstOrCreate([
                        'user_id' => $user->id,
                        'tipe' => $data['jenis'],
                        'nama_jenis' => $data['nama_kategori_baru'],
                    ]);
                    $data['jenis_transaksi_id'] = $kategori->id;
                }

                app(TransaksiService::class)->buat($user, [
                    ...$data,
                    'import_transaksi_id' => $batch->id,
                ], $data['jenis']);
            }

            return [
                'jumlah_baris' => $hasil['jumlah_baris'],
                'total_pemasukan' => $hasil['total_pemasukan'],
                'total_pengeluaran' => $hasil['total_pengeluaran'],
            ];
        };

        return DB::transactionLevel() > 0 ? $prosesImport() : DB::transaction($prosesImport);
    }

    public function antrekan(
        User $user,
        UploadedFile|TemporaryUploadedFile|string $file,
        ?string $namaFile = null,
        array $pemetaan = [],
        bool $buatKategoriOtomatis = false,
    ): ImportTransaksi {
        [$path, $namaFile] = $this->informasiFile($file, $namaFile);
        $hasil = $this->pratinjau($user, $path, $namaFile, $pemetaan, $buatKategoriOtomatis);

        if ($hasil['errors'] !== []) {
            throw ValidationException::withMessages(['file' => $hasil['errors']]);
        }

        if ($hasil['jumlah_baris'] <= self::BATAS_BARIS_LANGSUNG) {
            throw ValidationException::withMessages(['file' => 'File kecil dapat diimpor langsung tanpa antrean.']);
        }

        if (ImportTransaksi::query()
            ->where('user_id', $user->id)
            ->where('hash_file', $hasil['hash_file'])
            ->whereIn('status', ['menunggu', 'diproses'])
            ->exists()) {
            throw ValidationException::withMessages(['file' => 'File yang sama sedang menunggu atau diproses.']);
        }

        $extension = strtolower(pathinfo($namaFile, PATHINFO_EXTENSION));
        $pathFile = 'import-transaksi/'.$user->id.'/'.Str::uuid().'.'.$extension;
        Storage::disk('local')->put($pathFile, file_get_contents($path));

        try {
            $batch = ImportTransaksi::query()->updateOrCreate([
                'user_id' => $user->id,
                'hash_file' => $hasil['hash_file'],
            ], [
                'nama_file' => Str::limit(basename($namaFile), 255, ''),
                'path_file' => $pathFile,
                'pemetaan' => $pemetaan,
                'buat_kategori_otomatis' => $buatKategoriOtomatis,
                'jumlah_baris' => $hasil['jumlah_baris'],
                'jumlah_diproses' => 0,
                'status' => 'menunggu',
                'pesan_error' => null,
                'mulai_diproses_at' => null,
                'selesai_diproses_at' => null,
                'dibatalkan_at' => null,
            ]);

            ProsesImportTransaksi::dispatch($batch->id)->onQueue('import-transaksi');

            return $batch;
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($pathFile);

            throw $exception;
        }
    }

    public function batalkan(User $user, ImportTransaksi $batch): void
    {
        if ($batch->user_id !== $user->id || $batch->status !== 'berhasil') {
            throw ValidationException::withMessages(['batch' => 'Batch import tidak dapat dibatalkan.']);
        }

        DB::transaction(function () use ($user, $batch): void {
            $batch = ImportTransaksi::withoutGlobalScopes()->whereKey($batch->id)->lockForUpdate()->firstOrFail();

            if ($batch->user_id !== $user->id || $batch->status !== 'berhasil') {
                throw ValidationException::withMessages(['batch' => 'Batch import tidak dapat dibatalkan.']);
            }

            $transaksi = $batch->transaksi()->orderBy('id')->lockForUpdate()->get();

            if ($transaksi->count() !== $batch->jumlah_baris) {
                throw ValidationException::withMessages([
                    'batch' => 'Batch tidak dapat dibatalkan karena sebagian transaksi sudah tidak tersedia.',
                ]);
            }

            foreach ($transaksi as $item) {
                app(TransaksiService::class)->hapus($user, $item);
            }

            $batch->update([
                'status' => 'dibatalkan',
                'dibatalkan_at' => now(),
            ]);
        });
    }

    /** @return array{string, string} */
    private function informasiFile(UploadedFile|TemporaryUploadedFile|string $file, ?string $namaFile): array
    {
        if ($file instanceof UploadedFile) {
            return [$file->getRealPath(), $namaFile ?? $file->getClientOriginalName()];
        }

        return [$file, $namaFile ?? basename($file)];
    }

    private function validasiFile(string $path, string $namaFile): string
    {
        $extension = strtolower(pathinfo($namaFile, PATHINFO_EXTENSION));

        if (! in_array($extension, ['csv', 'xlsx'], true)) {
            throw ValidationException::withMessages(['file' => 'File harus berformat CSV atau XLSX.']);
        }

        if (! is_file($path) || filesize($path) === 0) {
            throw ValidationException::withMessages(['file' => 'File import kosong atau tidak dapat dibaca.']);
        }

        if (filesize($path) > self::BATAS_UKURAN_FILE) {
            throw ValidationException::withMessages(['file' => 'Ukuran file import maksimal 10 MB.']);
        }

        return $extension;
    }

    /** @param array<int, mixed> $nilai */
    private function barisKosong(array $nilai): bool
    {
        return collect($nilai)->every(fn ($value): bool => $value === null || trim((string) $value) === '');
    }

    private function nilaiLaporan(mixed $value): bool|float|int|string|null
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i');
        }

        if (is_bool($value) || is_float($value) || is_int($value) || is_string($value) || $value === null) {
            return $value;
        }

        return (string) $value;
    }

    private function normalisasiHeader(mixed $value): string
    {
        return str_replace(' ', '_', mb_strtolower(trim((string) $value, "\xEF\xBB\xBF \t\n\r\0\x0B")));
    }

    private function normalisasiNama(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    /** @param array<int, string> $header */
    private function pastikanHeaderDasarValid(array $header): void
    {
        if ($header === [] || in_array('', $header, true)) {
            throw ValidationException::withMessages(['file' => 'Header file tidak boleh kosong.']);
        }

        if (count($header) !== count(array_unique($header))) {
            throw ValidationException::withMessages(['file' => 'Header file tidak boleh duplikat setelah dinormalisasi.']);
        }
    }

    /**
     * @param  array<int, string>  $header
     * @param  array<string, mixed>  $pemetaan
     * @return array<string, string>
     */
    private function pastikanPemetaanValid(array $header, array $pemetaan): array
    {
        $pemetaan = collect($pemetaan)
            ->only(self::HEADER)
            ->filter(fn ($sumber): bool => filled($sumber))
            ->map(fn ($sumber): string => $this->normalisasiHeader($sumber))
            ->all();
        $belumDipetakan = array_diff(self::KOLOM_WAJIB, array_keys($pemetaan));

        if ($belumDipetakan !== []) {
            throw ValidationException::withMessages([
                'pemetaan' => 'Kolom wajib belum dipetakan: '.implode(', ', $belumDipetakan).'.',
            ]);
        }

        $tidakDitemukan = array_diff(array_values($pemetaan), $header);

        if ($tidakDitemukan !== []) {
            throw ValidationException::withMessages([
                'pemetaan' => 'Kolom sumber tidak ditemukan: '.implode(', ', array_unique($tidakDitemukan)).'.',
            ]);
        }

        if (count($pemetaan) !== count(array_unique($pemetaan))) {
            throw ValidationException::withMessages(['pemetaan' => 'Satu kolom sumber tidak boleh dipakai untuk lebih dari satu tujuan.']);
        }

        return $pemetaan;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{array<string, mixed>, array<int, string>}
     */
    private function validasiBaris(
        User $user,
        array $data,
        int $nomorBaris,
        bool $buatKategoriOtomatis,
    ): array {
        $errors = [];
        $jenis = Str::title(trim((string) ($data['jenis'] ?? '')));
        $bukuKas = $this->cariBukuKas($user, $data['buku_kas'] ?? null);
        $dompet = $this->cariDompet($user, $data['dompet'] ?? null);
        $kategori = $this->cariKategori($user, $data['kategori'] ?? null, $jenis);
        $namaKategoriBaru = trim((string) ($data['kategori'] ?? ''));
        $tanggal = $this->parseTanggal($data['tanggal'] ?? null);
        $nominal = filter_var($data['nominal'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if (! in_array($jenis, ['Pemasukan', 'Pengeluaran'], true)) {
            $errors[] = "Baris {$nomorBaris}, kolom jenis: hanya Pemasukan atau Pengeluaran yang didukung.";
        }

        if ($tanggal === null || $tanggal->isFuture()) {
            $errors[] = "Baris {$nomorBaris}, kolom tanggal: tanggal tidak valid atau berada di masa depan.";
        }

        if ($nominal === false) {
            $errors[] = "Baris {$nomorBaris}, kolom nominal: harus berupa bilangan bulat lebih dari nol tanpa pemisah ribuan.";
        }

        if ($bukuKas === null) {
            $errors[] = "Baris {$nomorBaris}, kolom buku_kas: buku kas tidak ditemukan atau tidak dapat dikelola.";
        }

        if ($dompet === null) {
            $errors[] = "Baris {$nomorBaris}, kolom dompet: dompet aktif tidak ditemukan atau tidak dapat dikelola.";
        }

        $kategoriDapatDibuat = $buatKategoriOtomatis
            && in_array($jenis, ['Pemasukan', 'Pengeluaran'], true)
            && $namaKategoriBaru !== ''
            && mb_strlen($namaKategoriBaru) <= 255;

        if ($kategori === null && ! $kategoriDapatDibuat) {
            $errors[] = "Baris {$nomorBaris}, kolom kategori: kategori tidak ditemukan atau tipenya tidak sesuai.";
        }

        return [[
            'tanggal' => $tanggal,
            'jenis' => $jenis,
            'buku_kas_id' => $bukuKas?->id,
            'dompet_id' => $dompet?->id,
            'jenis_transaksi_id' => $kategori?->id,
            'nama_kategori_baru' => $kategori === null && $kategoriDapatDibuat ? $namaKategoriBaru : null,
            'nominal' => $nominal === false ? 0 : $nominal,
            'deskripsi' => filled($data['deskripsi'] ?? null) ? trim((string) $data['deskripsi']) : null,
        ], $errors];
    }

    private function cariBukuKas(User $user, mixed $nama): ?BukuKas
    {
        $hasil = BukuKas::withoutGlobalScopes()->where('user_id', $user->id)
            ->whereRaw('LOWER(nama_buku) = ?', [mb_strtolower(trim((string) $nama))])->get();

        return $hasil->count() === 1 && $user->dapatMengelolaTransaksiPada($hasil->first()) ? $hasil->first() : null;
    }

    private function cariDompet(User $user, mixed $nama): ?Dompet
    {
        $hasil = Dompet::withoutGlobalScopes()->where('user_id', $user->id)->whereNull('deleted_at')
            ->whereRaw('LOWER(nama_dompet) = ?', [mb_strtolower(trim((string) $nama))])->get();

        return $hasil->count() === 1 && $user->dapatMengelolaTransaksiPadaDompet($hasil->first()) ? $hasil->first() : null;
    }

    private function cariKategori(User $user, mixed $nama, string $jenis): ?JenisTransaksi
    {
        $hasil = JenisTransaksi::withoutGlobalScopes()->where('user_id', $user->id)->where('tipe', $jenis)
            ->whereRaw('LOWER(nama_jenis) = ?', [mb_strtolower(trim((string) $nama))])->get();

        return $hasil->count() === 1 ? $hasil->first() : null;
    }

    private function parseTanggal(mixed $value): ?CarbonImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value);
        }

        try {
            $teks = trim((string) $value);
            $tanggal = CarbonImmutable::createFromFormat('Y-m-d H:i', $teks);

            return $tanggal->format('Y-m-d H:i') === $teks ? $tanggal : null;
        } catch (Throwable) {
            return null;
        }
    }
}
