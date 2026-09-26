<?php

use App\Filament\Resources\BukuKasResource\Pages\ListBukuKas;
use App\Filament\Resources\DompetResource\Pages\ListDompet;
use App\Models\BukuKas;
use App\Models\Dompet;
use App\Models\ShareBuku;
use App\Models\User;
use App\Services\KuotaAkun;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('kuota akun ditampilkan pada daftar dan modal sesuai jumlah dan masa aktif', function (string $jenis, string $status, int $jumlah) {
    $user = User::factory()->create([
        'role' => $status === 'admin' ? 'admin' : 'reguler',
        'masa_aktif' => match ($status) {
            'premium' => today(),
            'kedaluwarsa' => today()->subDay(),
            default => null,
        },
    ]);
    $model = $jenis === 'kas' ? BukuKas::class : Dompet::class;
    $model::factory()->count($jumlah)->create(['user_id' => $user->id]);
    $page = $jenis === 'kas' ? ListBukuKas::class : ListDompet::class;
    $component = Livewire::actingAs($user)->test($page)->assertSuccessful();
    $teks = $component->instance()->getSubheading()->toHtml();

    expect($teks)->toContain('text-xs', 'px-3 py-2')
        ->and(substr_count(trim(strip_tags($teks)), '.'))->toBe(1)
        ->and($component->html())->toContain('data-kuota-status=');

    $tanpaBatas = in_array($status, ['premium', 'admin']);
    if ($tanpaBatas) {
        expect($teks)->toContain(ucfirst($jenis).' '.$jumlah, 'tidak terbatas', 'data-kuota-status="normal"', 'bg-gray-100')
            ->not->toContain('bg-red-100', 'bg-yellow-100');
        if ($status === 'premium') {
            expect($teks)->toContain('hingga '.today()->format('d/m/Y'));
        }
    } else {
        $pesan = match (true) {
            $jumlah >= 2 => 'Sebagai pengguna reguler, Anda tidak bisa menambah '.$jenis.' lagi karena kuota sudah terpenuhi.',
            $jumlah === 1 => 'Sebagai pengguna reguler, Anda hanya bisa menambah satu lagi '.$jenis.'.',
            default => 'Sebagai pengguna reguler, Anda bisa menambah hingga dua '.$jenis.'.',
        };
        expect(trim(strip_tags($teks)))->toBe($pesan);
        $warna = $jumlah >= 2 ? 'red' : ($jumlah === 1 ? 'yellow' : 'gray');
        $statusKuota = $jumlah >= 2 ? 'limit' : ($jumlah === 1 ? 'warning' : 'normal');
        expect(html_entity_decode($teks))->toContain('data-kuota-status="'.$statusKuota.'"', 'bg-'.$warna.'-100', '[.dark_&]:bg-'.$warna.'-'.($warna === 'gray' ? '800' : '950'));
    }

    if ($tanpaBatas || $jumlah < 2) {
        $component->assertActionVisible('create')->mountAction('create');
        expect($component->instance()->getMountedAction()->getModalDescription()->toHtml())->toBe($teks);
    } else {
        $component->assertActionHidden('create');
        expect($teks)->toContain('kuota sudah terpenuhi');
        expect(fn () => $component->instance()->getAction('create')->callBefore())
            ->toThrow(HttpException::class);
        expect($model::where('user_id', $user->id)->count())->toBe($jumlah);
    }
})->with(['kas', 'dompet'])->with([
    ['reguler', 0],
    ['reguler', 1],
    ['reguler', 2],
    ['kedaluwarsa', 3],
    ['premium', 3],
    ['admin', 3],
]);

test('kuota akun mengabaikan kas bersama dan dompet yang sudah dihapus', function () {
    $user = User::factory()->create(['role' => 'reguler', 'masa_aktif' => null]);
    BukuKas::factory()->create(['user_id' => $user->id]);
    $kasBersama = BukuKas::factory()->create(['user_id' => User::factory()->create()->id]);
    ShareBuku::factory()->create(['user_id' => $user->id, 'buku_kas_id' => $kasBersama->id]);
    Dompet::factory()->create(['user_id' => $user->id]);
    Dompet::factory()->create(['user_id' => $user->id])->delete();
    $this->actingAs($user);

    expect(KuotaAkun::kas($user)->toHtml())->toContain('Sebagai pengguna reguler, Anda hanya bisa menambah satu lagi kas.', 'bg-yellow-100')
        ->and(KuotaAkun::dompet($user)->toHtml())->toContain('Sebagai pengguna reguler, Anda hanya bisa menambah satu lagi dompet.', 'bg-yellow-100');
});

test('kuota akun diperbarui setelah penambahan melalui modal', function (string $jenis) {
    $user = User::factory()->create(['role' => 'reguler', 'masa_aktif' => null]);
    BukuKas::factory()->create(['user_id' => $user->id, 'is_default' => true]);
    Dompet::factory()->create(['user_id' => $user->id, 'is_default' => true]);
    $page = $jenis === 'kas' ? ListBukuKas::class : ListDompet::class;
    $nama = $jenis === 'kas' ? 'nama_buku' : 'nama_dompet';
    $component = Livewire::actingAs($user)->test($page)
        ->callAction('create', data: [$nama => 'Tambahan', 'saldo' => 0])
        ->assertHasNoActionErrors()
        ->assertActionHidden('create');

    expect($component->instance()->getSubheading()->toHtml())->toContain('Sebagai pengguna reguler, Anda tidak bisa menambah '.$jenis.' lagi karena kuota sudah terpenuhi.', 'bg-red-100');
})->with(['kas', 'dompet']);
