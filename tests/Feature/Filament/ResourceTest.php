<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use App\Models\BukuKas;
use App\Models\Transaksi;
use App\Models\UtangPiutang;
use App\Models\ShareBuku;
use App\Models\JenisTransaksi;
use Livewire\Livewire;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([
        \Database\Seeders\UserSeeder::class,
        \Database\Seeders\JenisTransaksiSeeder::class,
        \Database\Seeders\WilayahSeeder::class,
    ]);
});

function createRegularUserWithBukuKas(): User
{
    $user = User::factory()->create([
        'role' => 'reguler',
        'email_verified_at' => now(),
    ]);
    
    BukuKas::factory()->create([
        'user_id' => $user->id,
        'nama_buku' => 'Kas Test',
        'saldo' => 100000,
    ]);
    
    return $user;
}

function createSuperUser(): User
{
    return User::factory()->create([
        'name' => 'Super Admin',
        'email' => 'super@test.com',
        'role' => 'super',
        'email_verified_at' => now(),
    ]);
}

// ==================== BUKU KAS RESOURCE ====================

test('buku kas resource dapat menampilkan halaman list', function () {
    $user = createRegularUserWithBukuKas();
    
    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\BukuKasResource\Pages\ListBukuKas::class)
        ->assertSuccessful()
        ->assertSee('Kas Test');
})
    ->group('filament', 'buku-kas');

test('buku kas resource dapat membuat record baru', function () {
    $user = createRegularUserWithBukuKas();
    
    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\BukuKasResource\Pages\CreateBukuKas::class)
        ->assertSuccessful()
        ->set('data.nama_buku', 'Kas Baru')
        ->set('data.saldo', 50000)
        ->call('create')
        ->assertHasNoErrors()
        ->assertSessionHas('success');
})
    ->group('filament', 'buku-kas');

test('buku kas resource dapat mengedit record', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();
    
    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\BukuKasResource\Pages\EditBukuKas::class, ['record' => $bukuKas])
        ->assertSuccessful()
        ->set('data.nama_buku', 'Kas Updated')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSessionHas('success');
    
    $this->assertEquals('Kas Updated', $bukuKas->fresh()->nama_buku);
})
    ->group('filament', 'buku-kas');

test('buku kas resource validasi form required', function () {
    $user = createRegularUserWithBukuKas();
    
    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\BukuKasResource\Pages\CreateBukuKas::class)
        ->assertSuccessful()
        ->set('data.nama_buku', '')
        ->set('data.saldo', '')
        ->call('create')
        ->assertHasErrors(['data.nama_buku' => 'required'])
        ->assertHasErrors(['data.saldo' => 'required']);
})
    ->group('filament', 'buku-kas');

// ==================== TRANSAKSI RESOURCE ====================

test('transaksi resource dapat menampilkan halaman list', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();
    
    Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pemasukan',
        'nominal' => 100000,
    ]);
    
    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\TransaksiResource\Pages\ListTransaksis::class)
        ->assertSuccessful()
        ->assertSee('100000');
})
    ->group('filament', 'transaksi');

test('transaksi resource dapat mengedit nominal transaksi', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();
    
    $transaksi = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pemasukan',
        'nominal' => 100000,
    ]);
    
    $initialSaldo = $bukuKas->saldo;
    
    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\TransaksiResource\Pages\EditTransaksi::class, ['record' => $transaksi])
        ->assertSuccessful()
        ->set('data.nominal', 150000)
        ->call('save')
        ->assertHasNoErrors();
    
    $bukuKas->refresh();
    $this->assertEquals($initialSaldo + 50000, $bukuKas->saldo);
})
    ->group('filament', 'transaksi');

test('transaksi resource dapat menghapus transaksi pemasukan', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();
    
    $transaksi = Transaksi::factory()->create([
        'user_id' => $user->id,
        'buku_kas_id' => $bukuKas->id,
        'jenis' => 'Pemasukan',
        'nominal' => 100000,
    ]);
    
    $saldoBefore = $bukuKas->saldo;
    
    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\TransaksiResource\Pages\EditTransaksi::class, ['record' => $transaksi])
        ->callTableAction('delete', $transaksi);
    
    $bukuKas->refresh();
    
    $this->assertEquals($saldoBefore - $transaksi->nominal, $bukuKas->saldo);
    $this->assertSoftDeleted('transaksi', $transaksi->getAttributes());
})
    ->group('filament', 'transaksi');

// ==================== PIUTANG RESOURCE ====================

test('piutang resource dapat menampilkan halaman list', function () {
    $user = createRegularUserWithBukuKas();
    
    $jenis = JenisTransaksi::factory()->create(['nama_jenis' => 'Piutang Test']);
    
    UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'jenis_transaksi_id' => $jenis->id,
        'tipe' => 'piutang',
        'nama' => 'Test Piutang',
        'nominal' => 500000,
    ]);
    
    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\PiutangResource\Pages\ListPiutangs::class)
        ->assertSuccessful()
        ->assertSee('Test Piutang');
})
    ->group('filament', 'piutang');

test('piutang resource dapat menampilkan halaman detail', function () {
    $user = createRegularUserWithBukuKas();
    
    $jenis = JenisTransaksi::factory()->create(['nama_jenis' => 'Piutang Test']);
    
    $piutang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'jenis_transaksi_id' => $jenis->id,
        'tipe' => 'piutang',
        'nama' => 'Test Piutang',
        'nominal' => 500000,
    ]);
    
    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\PiutangResource\Pages\PiutangDetail::class, ['record' => $piutang])
        ->assertSuccessful()
        ->assertSee('Test Piutang');
})
    ->group('filament', 'piutang');

// ==================== UTANG RESOURCE ====================

test('utang resource dapat menampilkan halaman list', function () {
    $user = createRegularUserWithBukuKas();
    
    $jenis = JenisTransaksi::factory()->create(['nama_jenis' => 'Utang Test']);
    
    UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'jenis_transaksi_id' => $jenis->id,
        'tipe' => 'utang',
        'nama' => 'Test Utang',
        'nominal' => 300000,
    ]);
    
    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\UtangResource\Pages\ListUtangs::class)
        ->assertSuccessful()
        ->assertSee('Test Utang');
})
    ->group('filament', 'utang');

test('utang resource dapat menampilkan halaman detail', function () {
    $user = createRegularUserWithBukuKas();
    
    $jenis = JenisTransaksi::factory()->create(['nama_jenis' => 'Utang Test']);
    
    $utang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'jenis_transaksi_id' => $jenis->id,
        'tipe' => 'utang',
        'nama' => 'Test Utang',
        'nominal' => 300000,
    ]);
    
    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\UtangResource\Pages\UtangDetail::class, ['record' => $utang])
        ->assertSuccessful()
        ->assertSee('Test Utang');
})
    ->group('filament', 'utang');

// ==================== USER RESOURCE ====================

test('user resource dapat menampilkan halaman list (super user only)', function () {
    $superUser = createSuperUser();
    
    User::factory(5)->create();
    
    Livewire::actingAs($superUser)
        ->test(\App\Filament\Resources\UserResource\Pages\ListUsers::class)
        ->assertSuccessful()
        ->assertSee('Super Admin');
})
    ->group('filament', 'user');

test('user resource dapat mengedit user (super user only)', function () {
    $superUser = createSuperUser();
    $user = User::factory()->create();
    
    Livewire::actingAs($superUser)
        ->test(\App\Filament\Resources\UserResource\Pages\EditUser::class, ['record' => $user])
        ->assertSuccessful()
        ->set('data.name', 'Updated Name')
        ->call('save')
        ->assertHasNoErrors();
    
    $this->assertEquals('Updated Name', $user->fresh()->name);
})
    ->group('filament', 'user');

// ==================== SHARE BUKU RESOURCE ====================

test('share buku resource dapat menampilkan halaman list', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();
    
    $otherUser = User::factory()->create();
    
    ShareBuku::factory()->create([
        'buku_kas_id' => $bukuKas->id,
        'user_id' => $otherUser->id,
        'privilege' => 'edit',
    ]);
    
    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\ShareBukuResource\Pages\ListShareBukus::class)
        ->assertSuccessful();
})
    ->group('filament', 'share-buku');

test('share buku resource dapat membuat share baru', function () {
    $user = createRegularUserWithBukuKas();
    $bukuKas = $user->buku_kas()->first();
    
    $otherUser = User::factory()->create();
    
    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\ShareBukuResource\Pages\CreateShareBuku::class)
        ->assertSuccessful()
        ->set('data.buku_kas_id', $bukuKas->id)
        ->set('data.user_id', $otherUser->id)
        ->set('data.privilege', 'view')
        ->call('create')
        ->assertHasNoErrors()
        ->assertSessionHas('success');
    
    $this->assertDatabaseHas('share_bukus', [
        'buku_kas_id' => $bukuKas->id,
        'user_id' => $otherUser->id,
        'privilege' => 'view',
    ]);
})
    ->group('filament', 'share-buku');

// ==================== PAGES ====================

test('halaman akun saya dapat ditampilkan', function () {
    $user = createRegularUserWithBukuKas();
    
    Livewire::actingAs($user)
        ->test(\App\Filament\Pages\AkunSaya::class)
        ->assertSuccessful()
        ->assertSee($user->name);
})
    ->group('filament', 'pages');

test('halaman akun saya dapat mengupdate profil', function () {
    $user = createRegularUserWithBukuKas();
    
    Livewire::actingAs($user)
        ->test(\App\Filament\Pages\AkunSaya::class)
        ->assertSuccessful()
        ->set('data.name', 'Updated Name')
        ->set('data.email', 'updated@test.com')
        ->set('data.hp', '081234567890')
        ->set('data.provinsi', 'Jawa Barat')
        ->set('data.kota', 'Bandung')
        ->set('data.penggunaan', 'Pribadi/Keluarga')
        ->call('submit')
        ->assertHasNoErrors();
    
    $this->assertEquals('Updated Name', $user->fresh()->name);
    $this->assertEquals('updated@test.com', $user->fresh()->email);
})
    ->group('filament', 'pages');

test('halaman akun saya dapat mengubah password', function () {
    $user = createRegularUserWithBukuKas();
    
    Livewire::actingAs($user)
        ->test(\App\Filament\Pages\AkunSaya::class)
        ->assertSuccessful()
        ->set('data.password', 'newpassword123')
        ->call('submit')
        ->assertHasNoErrors();
    
    $this->assertTrue(password_verify('newpassword123', $user->fresh()->password));
})
    ->group('filament', 'pages');

test('halaman kategori dapat ditampilkan', function () {
    $user = createRegularUserWithBukuKas();
    
    Livewire::actingAs($user)
        ->test(\App\Filament\Pages\Kategori::class)
        ->assertSuccessful();
})
    ->group('filament', 'pages');

// ==================== WIDGETS ====================

test('widget utang piutang detail dapat ditampilkan', function () {
    $user = createRegularUserWithBukuKas();
    
    $jenis = JenisTransaksi::factory()->create(['nama_jenis' => 'Test']);
    
    $utangPiutang = UtangPiutang::factory()->create([
        'user_id' => $user->id,
        'jenis_transaksi_id' => $jenis->id,
        'tipe' => 'utang',
        'nama' => 'Test Utang',
    ]);
    
    Livewire::actingAs($user)
        ->test(\App\Filament\Resources\UtangResource\Pages\UtangPiutangDetail::class, ['record' => $utangPiutang])
        ->assertSuccessful()
        ->assertSee('Total');
})
    ->group('filament', 'widgets');

// ==================== AUTHORIZATION TESTS ====================

test('regular user tidak dapat mengakses user management', function () {
    $user = createRegularUserWithBukuKas();
    
    $this->get(route('filament.admin.resources.users.index'))
        ->assertForbidden();
})
    ->group('filament', 'authorization');

test('super user dapat mengakses user management', function () {
    $superUser = createSuperUser();
    
    $this->actingAs($superUser)
        ->get(route('filament.admin.resources.users.index'))
        ->assertSuccessful();
})
    ->group('filament', 'authorization');

test('regular user hanya dapat melihat data sendiri di transaksi', function () {
    $user1 = createRegularUserWithBukuKas();
    $user2 = createRegularUserWithBukuKas();
    
    $bukuKas2 = $user2->buku_kas()->first();
    
    Transaksi::factory()->create([
        'user_id' => $user2->id,
        'buku_kas_id' => $bukuKas2->id,
        'jenis' => 'Pemasukan',
        'nominal' => 200000,
    ]);
    
    $bukuKas1 = $user1->buku_kas()->first();
    
    Livewire::actingAs($user1)
        ->test(\App\Filament\Resources\TransaksiResource\Pages\ListTransaksis::class)
        ->assertSuccessful()
        ->assertDontSee('200000');
})
    ->group('filament', 'authorization');