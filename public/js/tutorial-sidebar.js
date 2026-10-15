export function initTutorialSidebar(doc, win) {
    const sidebar = doc.querySelector('[data-tutorial-sidebar]');
    const position = doc.querySelector('[data-topic-position]');
    if (!sidebar || !position) return;

    const entries = Array.from(sidebar.querySelectorAll('[data-topic-link]'))
        .map(link => {
            const article = doc.getElementById(link.hash.slice(1));
            return { link, article, section: article?.closest?.('section') ?? null };
        })
        .filter(entry => entry.article);
    if (!entries.length) return;

    const baseTitle = doc.title;

    // Di layar kecil, daftar isi berupa menu yang dilipat; tutup setelah topik dipilih
    // agar konten langsung terlihat.
    const mobileNav = sidebar.querySelector('[data-mobile-nav]');
    const isDesktop = () => win.matchMedia?.('(min-width:1024px)').matches ?? true;
    entries.forEach(({ link }) => {
        link.addEventListener?.('click', () => {
            if (mobileNav && !isDesktop()) mobileNav.open = false;
        });
    });

    // Kolom kanan "Di halaman ini": hanya daftar milik topik yang sedang tampil.
    const tocRoot = doc.querySelector('[data-toc]');
    const tocLists = Array.from(doc.querySelectorAll('[data-toc-for]'));
    const markToc = (list, link) => {
        list.querySelectorAll('a').forEach(a => {
            if (a === link) a.setAttribute('aria-current', 'location');
            else a.removeAttribute('aria-current');
        });
    };
    tocLists.forEach(list => list.addEventListener?.('click', event => {
        const link = event.target?.closest?.('a');
        if (link) markToc(list, link);
    }));

    let spy = null;
    const showToc = (article) => {
        if (!tocRoot) return;
        tocRoot.hidden = false;
        spy?.disconnect();
        spy = null;
        tocLists.forEach(list => {
            const on = list.getAttribute('data-toc-for') === article.id;
            list.hidden = !on;
            if (!on) return;
            const links = Array.from(list.querySelectorAll('a'));
            if (links[0]) markToc(list, links[0]);
            if (!win.IntersectionObserver) return;
            spy = new win.IntersectionObserver(items => {
                items.forEach(item => {
                    if (!item.isIntersecting) return;
                    const link = links.find(a => a.hash === `#${item.target.id}`);
                    if (link) markToc(list, link);
                });
            }, { rootMargin: '-80px 0px -60% 0px' });
            links.forEach(a => {
                const target = doc.getElementById(a.hash.slice(1));
                if (target) spy.observe(target);
            });
        });
    };

    // Hanya satu topik yang ditampilkan; sisanya disembunyikan.
    let active = -1;
    const select = (index, target = null) => {
        const entry = entries[index];
        const changed = index !== active;
        active = index;
        entries.forEach((item, i) => {
            const on = i === index;
            item.article.hidden = !on;
            if (item.section) item.section.hidden = item.section !== entry.section;
            if (on) item.link.setAttribute('aria-current', 'location');
            else item.link.removeAttribute('aria-current');
        });
        if (!changed) return;

        position.textContent = `Topik ${index + 1} dari ${entries.length} yang ditampilkan`;
        doc.title = `${entry.link.textContent.trim()} · ${baseTitle}`;
        showToc(entry.article);

        // Geser hanya area sidebar agar penanda aktif terlihat tanpa menggeser isi panduan.
        const linkRect = entry.link.getBoundingClientRect();
        const sidebarRect = sidebar.getBoundingClientRect();
        if (sidebar.scrollHeight > sidebar.clientHeight) {
            if (linkRect.bottom > sidebarRect.bottom) sidebar.scrollTop += linkRect.bottom - sidebarRect.bottom + 12;
            else if (linkRect.top < sidebarRect.top) sidebar.scrollTop -= sidebarRect.top - linkRect.top + 12;
        }

        // Elemen tujuan tadinya tersembunyi sehingga browser tidak bisa menggulir ke sana.
        if (target) {
            const anchor = target === entry.article ? (entry.section ?? target) : target;
            anchor.scrollIntoView?.();
        }
    };

    const trackHash = () => {
        const hash = win.location.hash.slice(1);
        const target = hash ? doc.getElementById(hash) : null;
        const article = target?.closest?.('article[id]');
        const index = article ? entries.findIndex(entry => entry.article === article) : -1;
        if (index >= 0) select(index, target);
        else if (!hash || active < 0) select(0);
    };

    win.addEventListener('hashchange', trackHash);
    trackHash();
}

if (typeof document !== 'undefined') initTutorialSidebar(document, window);