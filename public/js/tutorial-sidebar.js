export function initTutorialSidebar(doc, win) {
    const sidebar = doc.querySelector('[data-tutorial-sidebar]');
    const position = doc.querySelector('[data-topic-position]');
    if (!sidebar || !position) return;

    const entries = Array.from(sidebar.querySelectorAll('[data-topic-link]'))
        .map(link => ({ link, article: doc.getElementById(link.hash.slice(1)) }))
        .filter(entry => entry.article);
    if (!entries.length) return;

    let active = -1;
    const select = (index) => {
        if (index === active) return;
        active = index;
        entries.forEach(({ link }, i) => {
            if (i === index) link.setAttribute('aria-current', 'location');
            else link.removeAttribute('aria-current');
        });
        position.textContent = `Topik ${index + 1} dari ${entries.length} yang ditampilkan`;

        // Geser hanya area sidebar agar penanda aktif terlihat tanpa menggeser isi panduan.
        const linkRect = entries[index].link.getBoundingClientRect();
        const sidebarRect = sidebar.getBoundingClientRect();
        if (sidebar.scrollHeight > sidebar.clientHeight) {
            if (linkRect.bottom > sidebarRect.bottom) sidebar.scrollTop += linkRect.bottom - sidebarRect.bottom + 12;
            else if (linkRect.top < sidebarRect.top) sidebar.scrollTop -= sidebarRect.top - linkRect.top + 12;
        }
    };
    const trackScroll = () => {
        let index = 0;
        entries.forEach(({ article }, i) => {
            if (article.getBoundingClientRect().top <= 140) index = i;
        });
        select(index);
    };
    const trackHash = () => {
        const index = entries.findIndex(({ link }) => link.hash === win.location.hash);
        if (index >= 0) select(index);
        else trackScroll();
    };

    let scheduled = false;
    win.addEventListener('scroll', () => {
        if (scheduled) return;
        scheduled = true;
        win.requestAnimationFrame(() => { scheduled = false; trackScroll(); });
    }, { passive: true });
    win.addEventListener('hashchange', trackHash);
    win.addEventListener('resize', trackScroll);
    trackHash();
}

if (typeof document !== 'undefined') initTutorialSidebar(document, window);
