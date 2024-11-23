<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Wilayah extends Model
{
    use HasFactory;

    protected $table = 'wilayah';

    // Fungsi untuk mendapatkan nama daerah berdasarkan ID
    public static function getNamaDaerah($id)
    {
        $wilayah = self::where('kode', $id)->first();
        return $wilayah ? $wilayah->nama : null;
    }

    // Fungsi untuk mendapatkan nilai provinsi, kota, dan kecamatan
    public static function getDetailWilayah($id)
    {
        $wilayah = self::find($id);
        if ($wilayah) {
            $idParts = explode('.', $id);
            return [
                'provinsi' => self::getNamaDaerah($idParts[0]),
                'kota' => count($idParts) > 1 ?
                    self::getNamaDaerah($idParts[0] . '.' . $idParts[1]) : null,
            ];
        }
        return null;
    }

    public static function getDaftarProvinsi()
    {
        return self::where('kode', 'not like', '%.%')->pluck('nama', 'kode');
    }

    public static function getDaftarKota()
    {
        return self::where('kode', 'like', '%.%')
            ->where('kode', 'not like', '%.%.%')
            ->pluck('nama', 'kode');
    }

    public static function getDaftarKotaByProvinsi($provinsiId)
    {
        return self::where('kode', 'like', "$provinsiId.%")
            ->where('kode', 'not like', '%.%.%')->pluck('nama', 'kode');
    }

    public static function getDaftarWilayahByName($nama)
    {
        $daftarProvinsi = self::where('nama', 'like', "%$nama%")
            ->where('kode', 'not like', '%.%')->get(['kode', 'nama']);

        $daftarKota = self::where('nama', 'like', "%$nama%")
            ->where('kode', 'like', '%.%')
            ->where('kode', 'not like', '%.%.%')->get(['kode', 'nama']);

        return [
            'provinsi' => $daftarProvinsi,
            'kota' => $daftarKota,
        ];
    }
}
