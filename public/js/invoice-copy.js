// Menyalin nomor tujuan invoice tanpa reload; memberi umpan balik singkat bila berhasil.
export async function salinNomorTujuan(nomor, opsi = {}) {
    const clipboard = opsi.clipboard;
    const salinManual = opsi.salinManual ?? (() => false);

    if (clipboard?.writeText) {
        try {
            await clipboard.writeText(nomor);

            return 'clipboard';
        } catch {
            return salinManual() ? 'manual' : 'gagal';
        }
    }

    return salinManual() ? 'manual' : 'gagal';
}

export function initPenyalinanInvoice(dokumen = (typeof document !== 'undefined' ? document : undefined)) {
    if (!dokumen) return;

    dokumen.querySelectorAll('[data-copy-account-button]').forEach((tombol) => {
        tombol.addEventListener?.('click', async () => {
            const nomor = tombol.getAttribute('data-copy-account-button') || '';
            const kotak = tombol.closest?.('[data-invoice-account-box]');
            const umpanBalik = kotak?.querySelector?.('[data-copy-feedback]');

            const tandaiBerhasil = () => {
                tombol.textContent = 'Tersalin';
                umpanBalik?.classList?.remove?.('hidden');
                setTimeout(() => {
                    tombol.textContent = 'Salin';
                    umpanBalik?.classList?.add?.('hidden');
                }, 2000);
            };

            const salinManual = () => {
                const area = dokumen.createElement('textarea');
                area.value = nomor;
                area.setAttribute('readonly', '');
                area.style.position = 'fixed';
                area.style.opacity = '0';
                dokumen.body.appendChild(area);
                area.select();
                try {
                    return dokumen.execCommand('copy');
                } finally {
                    dokumen.body.removeChild(area);
                }
            };

            const hasil = await salinNomorTujuan(nomor, {
                clipboard: dokumen.defaultView?.navigator?.clipboard ?? (typeof navigator !== 'undefined' ? navigator.clipboard : undefined),
                salinManual,
            });

            if (hasil !== 'gagal') tandaiBerhasil();
        });
    });
}

if (typeof document !== 'undefined') initPenyalinanInvoice(document);
