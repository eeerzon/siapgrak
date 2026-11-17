{{--
    Infinite scroll for [data-infinite-list]: the first page renders server-side, and the
    next page loads only after the visitor scrolls (wheel, touch or keyboard) near the end.
    Each page carries its own data-next URL, so the server pagination stays the source of truth.
--}}
<script>
    (() => {
        const list = document.querySelector('[data-infinite-list]');
        const status = document.querySelector('[data-infinite-status]');
        if (!list) return;

        let next = list.dataset.next;
        let loading = false;

        const show = (text) => {
            status.textContent = text;
            status.classList.toggle('hidden', !text);
        };

        const nearEnd = () => list.getBoundingClientRect().bottom < window.innerHeight + 200;

        const load = async () => {
            if (loading || !next || !nearEnd()) return;
            loading = true;
            show('Memuat agenda berikutnya…');

            try {
                const response = await fetch(next, { headers: { Accept: 'text/html' } });
                if (!response.ok) throw new Error(response.status);
                const page = new DOMParser().parseFromString(await response.text(), 'text/html');
                const nextList = page.querySelector('[data-infinite-list]');

                list.append(...nextList.children);
                next = nextList.dataset.next;
                show(next ? '' : 'Semua agenda sudah ditampilkan.');
            } catch {
                // Keep `next` so the following scroll retries.
                show('Gagal memuat agenda. Scroll lagi untuk mencoba.');
            } finally {
                loading = false;
            }

            if (!next) stop();
        };

        // Wheel, touch and keys count as scrolling too, so a screen tall enough to show
        // the whole first page (nothing to scroll) can still ask for more.
        const events = ['scroll', 'wheel', 'touchmove', 'keydown'];
        let queued = false;
        const onUserScroll = () => {
            if (queued) return;
            queued = true;
            requestAnimationFrame(() => {
                queued = false;
                load();
            });
        };
        const stop = () => events.forEach((name) => window.removeEventListener(name, onUserScroll));

        if (next) events.forEach((name) => window.addEventListener(name, onUserScroll, { passive: true }));
    })();
</script>
