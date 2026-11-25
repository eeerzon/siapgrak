{{-- Submits the page's [data-auto-filter] form on change, and 500 ms after typing in its search field. --}}
<script>
    (() => {
        const form = document.querySelector('[data-auto-filter]');
        const search = form.querySelector('[name=q]');
        let timer;

        // Leave empty filters out of the URL so shared links stay short.
        form.addEventListener('submit', () => {
            form.querySelectorAll('input, select').forEach((field) => {
                if (field.value === '') field.disabled = true;
            });
        });

        // Unnamed fields (e.g. the search box inside a dropdown) are not filters.
        form.addEventListener('change', (event) => {
            if (event.target.name && event.target !== search) form.requestSubmit();
        });

        // Reloading moves focus away, so remember it and put the caret back after typing-triggered searches.
        search.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(() => {
                sessionStorage.setItem('site-search-focus', '1');
                form.requestSubmit();
            }, 500);
        });

        if (sessionStorage.getItem('site-search-focus')) {
            sessionStorage.removeItem('site-search-focus');
            search.focus();
            search.setSelectionRange(search.value.length, search.value.length);
        }
    })();
</script>
