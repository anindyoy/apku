<?php

use App\Filament\Clusters\Pengaturan;
use App\Filament\Pages\Setting;
use Filament\Facades\Filament;

test('setting menampilkan submenu sesuai peran dan menyederhanakan navbar', function (string $role) {
    $user = createRegularUserWithBukuKas();
    $user->update(['role' => $role]);
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::bootCurrentPanel();

    $menus = app(Pengaturan::class)->getCachedSubNavigation();
    $menus = collect($menus)->flatMap(fn ($group) => $group->getItems())->values();
    // Admin melihat submenu Pengaturan Trial untuk saklar dan durasi coba gratis.
    $expected = $role === 'admin'
        ? ['Pengguna', 'Harga Emas', 'Pengaturan Trial']
        : ['Kas', 'Sumber Dana', 'Kategori', 'Kolaborator Kas', 'Tabungan Emas', 'Akun Saya'];
    expect($menus->map(fn ($item) => $item->getLabel())->all())->toEqualCanonicalizing($expected);
    $this->get(Pengaturan::getUrl())->assertRedirect($menus->first()->getUrl());

    foreach ($menus as $menu) {
        expect(parse_url($menu->getUrl(), PHP_URL_PATH))->toStartWith('/admin/pengaturan/');
        $response = $this->get($menu->getUrl())->assertSuccessful();
        $document = new DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new DOMXPath($document);
        foreach ($menus as $submenu) {
            expect($xpath->query('//a[@href="'.$submenu->getUrl().'"]')->length)->toBeGreaterThan(0);
        }
    }

    $groups = collect(Filament::getNavigation());
    $labels = $groups->map(fn ($group) => $group->getLabel());
    expect($labels->last())->toBe('Langganan')
        ->and($labels->all())->not->toContain('Pengaturan');
    $items = $groups->flatMap(fn ($group) => $group->getItems());
    expect($items->filter(fn ($item) => $item->getLabel() === 'Setting'))->toHaveCount(1);
    foreach ($expected as $label) {
        expect($items->map(fn ($item) => $item->getLabel())->all())->not->toContain($label);
    }

    if ($role !== 'admin') {
        $urutan = $labels->values()->all();
        $posisiUtang = array_search('Utang Piutang', $urutan, true);
        expect($posisiUtang)->not->toBeFalse();
        expect($urutan[$posisiUtang + 1] ?? null)->toBe('Laporan');
        $this->get(Setting::getUrl())->assertForbidden();
    }
})->with(['reguler', 'premium', 'admin']);
