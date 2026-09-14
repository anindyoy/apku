const topics = {
    '': 'dashboard',
    'akun-saya': 'profil-notifikasi',
    profile: 'profil-notifikasi',
    onboarding: 'pengaturan-awal',
    transaksi: 'transaksi',
    'pencarian-transaksi': 'pencarian',
    'riwayat-import-transaksi': 'import',
    'buku-kas': 'kas',
    dompet: 'dompet',
    'audit-saldo-dompet': 'audit-saldo',
    kategori: 'aktivitas',
    'kolaborator-kas': 'kolaborasi',
    'tabungan-emas': 'emas',
    laporan: 'laporan',
    utangs: 'utang-piutang',
    piutangs: 'utang-piutang',
    langganans: 'langganan',
};

export function tutorialUrl(baseUrl, currentUrl, panelUrl) {
    const current = new URL(currentUrl);
    const panel = new URL(panelUrl);
    const prefix = panel.pathname.replace(/\/+$/, '');
    if (current.origin !== panel.origin || (current.pathname !== prefix && !current.pathname.startsWith(`${prefix}/`))) {
        return baseUrl;
    }

    const page = current.pathname.slice(prefix.length).replace(/^\/+/, '').split('/')[0];
    const topic = Object.hasOwn(topics, page) ? topics[page] : null;
    return topic ? `${baseUrl}#${topic}` : baseUrl;
}

if (typeof document !== 'undefined') {
    const updateLinks = () => {
        document.querySelectorAll('[data-tutorial-url]').forEach((link) => {
            link.href = tutorialUrl(link.dataset.tutorialUrl, window.location.href, link.dataset.panelUrl);
        });
    };

    // Perbarui tautan pada navigasi panel serta sebelum klik atau membuka menu tautan.
    ['livewire:navigated', 'DOMContentLoaded', 'pointerdown', 'focusin', 'click', 'contextmenu'].forEach((event) => {
        document.addEventListener(event, updateLinks, true);
    });
    updateLinks();
}
