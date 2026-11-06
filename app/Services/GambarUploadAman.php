<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class GambarUploadAman
{
    public function simpan(TemporaryUploadedFile $file, string $disk, string $direktori): string
    {
        $isi = file_get_contents($file->getRealPath());
        $gambar = is_string($isi) ? @imagecreatefromstring($isi) : false;

        if ($gambar === false) {
            throw ValidationException::withMessages(['file' => 'Isi gambar tidak valid. Unggah ulang gambar PNG, JPEG, atau WebP yang valid.']);
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($isi);
        $ekstensi = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => null,
        };

        if ($ekstensi === null) {
            imagedestroy($gambar);
            throw ValidationException::withMessages(['file' => 'Format gambar harus PNG, JPEG, atau WebP.']);
        }

        ob_start();
        $berhasil = match ($ekstensi) {
            'jpg' => imagejpeg($gambar, null, 90),
            'png' => imagepng($gambar),
            'webp' => imagewebp($gambar, null, 90),
        };
        $hasil = ob_get_clean();
        imagedestroy($gambar);

        if (! $berhasil || ! is_string($hasil)) {
            throw ValidationException::withMessages(['file' => 'Gambar tidak dapat diproses.']);
        }

        $path = trim($direktori, '/').'/'.Str::uuid().'.'.$ekstensi;
        Storage::disk($disk)->put($path, $hasil);

        return $path;
    }
}
