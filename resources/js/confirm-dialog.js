/**
 * SweetAlert-style confirmation for every form that asks before submitting.
 *
 *   <form data-confirm="Teks penjelasan" data-confirm-title="Hapus kategori?"
 *         data-confirm-button="Ya, hapus" data-confirm-icon="danger">
 *
 * The attributes can also sit on the submit button itself (only that button asks).
 * data-confirm-icon: danger (default, red button) | warning (amber) | question (navy).
 * Line breaks in data-confirm are kept; lines starting with "- " become a list.
 */
const ICONS = {
    danger: { ring: 'border-red-200 text-red-500', mark: '!', button: 'bg-brand-600 hover:bg-brand-700 focus-visible:outline-brand-600' },
    warning: { ring: 'border-amber-300 text-amber-500', mark: '!', button: 'bg-brand-600 hover:bg-brand-700 focus-visible:outline-brand-600' },
    question: { ring: 'border-navy-100 text-navy-700', mark: '?', button: 'bg-navy-900 hover:bg-navy-800 focus-visible:outline-navy-600' },
};

let dialog;

function build() {
    const root = document.createElement('div');
    root.className = 'fixed inset-0 z-[100] hidden items-center justify-center p-4';
    root.setAttribute('role', 'alertdialog');
    root.setAttribute('aria-modal', 'true');
    root.setAttribute('aria-labelledby', 'confirm-dialog-title');
    root.innerHTML = `
        <div data-backdrop class="absolute inset-0 bg-navy-950/50 opacity-0 transition-opacity duration-200"></div>
        <div data-panel class="relative w-full max-w-md scale-75 rounded-2xl bg-white px-6 pt-8 pb-6 text-center opacity-0 shadow-2xl transition duration-300 ease-[cubic-bezier(.34,1.56,.64,1)]">
            <div data-icon class="mx-auto flex h-20 w-20 items-center justify-center rounded-full border-4 text-5xl leading-none font-light"></div>
            <h3 id="confirm-dialog-title" data-title class="mt-5 text-xl font-semibold text-navy-900"></h3>
            <div data-text class="mt-3 text-sm leading-relaxed text-slate-600"></div>
            <div class="mt-7 flex flex-col-reverse justify-center gap-2 sm:flex-row">
                <button type="button" data-cancel class="cursor-pointer rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-400">Batal</button>
                <button type="button" data-ok class="cursor-pointer rounded-lg px-5 py-2.5 text-sm font-semibold text-white focus-visible:outline-2 focus-visible:outline-offset-2"></button>
            </div>
        </div>`;
    document.body.appendChild(root);

    return root;
}

/** Text → paragraphs, with "- " lines grouped into a list. Built with textContent, never innerHTML. */
function renderText(container, text) {
    container.replaceChildren();
    let list = null;

    for (const line of text.split('\n')) {
        if (line.startsWith('- ')) {
            if (!list) {
                list = document.createElement('ul');
                list.className = 'mt-2 space-y-1 rounded-lg bg-amber-50 px-4 py-3 text-left text-amber-900';
                container.appendChild(list);
            }
            const item = document.createElement('li');
            item.className = "relative pl-4 before:absolute before:left-0 before:content-['•']";
            item.textContent = line.slice(2);
            list.appendChild(item);
        } else if (line.trim() !== '') {
            list = null;
            const paragraph = document.createElement('p');
            paragraph.className = container.childElementCount ? 'mt-2' : '';
            paragraph.textContent = line;
            container.appendChild(paragraph);
        }
    }
}

export function confirmDialog({ title = 'Yakin?', text = '', button = 'Ya, lanjutkan', icon = 'danger' } = {}) {
    dialog ??= build();
    const style = ICONS[icon] ?? ICONS.danger;
    const iconEl = dialog.querySelector('[data-icon]');
    const ok = dialog.querySelector('[data-ok]');
    const cancel = dialog.querySelector('[data-cancel]');
    const backdrop = dialog.querySelector('[data-backdrop]');
    const panel = dialog.querySelector('[data-panel]');
    const previousFocus = document.activeElement;

    iconEl.className = `mx-auto flex h-20 w-20 items-center justify-center rounded-full border-4 text-5xl leading-none font-light ${style.ring}`;
    iconEl.textContent = style.mark;
    dialog.querySelector('[data-title]').textContent = title;
    renderText(dialog.querySelector('[data-text]'), text);
    ok.className = `cursor-pointer rounded-lg px-5 py-2.5 text-sm font-semibold text-white focus-visible:outline-2 focus-visible:outline-offset-2 ${style.button}`;
    ok.textContent = button;

    dialog.classList.replace('hidden', 'flex');
    document.body.classList.add('overflow-hidden');
    requestAnimationFrame(() => {
        backdrop.classList.replace('opacity-0', 'opacity-100');
        panel.classList.remove('scale-75', 'opacity-0');
    });
    // Cancel gets focus: pressing Enter by reflex must not confirm a destructive action.
    cancel.focus();

    return new Promise((resolve) => {
        const close = (answer) => {
            backdrop.classList.replace('opacity-100', 'opacity-0');
            panel.classList.add('scale-75', 'opacity-0');
            ok.removeEventListener('click', onOk);
            cancel.removeEventListener('click', onCancel);
            backdrop.removeEventListener('click', onCancel);
            document.removeEventListener('keydown', onKey);
            setTimeout(() => {
                dialog.classList.replace('flex', 'hidden');
                document.body.classList.remove('overflow-hidden');
                previousFocus?.focus?.();
            }, 150);
            resolve(answer);
        };
        const onOk = () => close(true);
        const onCancel = () => close(false);
        const onKey = (event) => {
            if (event.key === 'Escape') {
                close(false);
            } else if (event.key === 'Tab') {
                // Keep focus inside the two buttons.
                event.preventDefault();
                (document.activeElement === ok ? cancel : ok).focus();
            }
        };

        ok.addEventListener('click', onOk);
        cancel.addEventListener('click', onCancel);
        backdrop.addEventListener('click', onCancel);
        document.addEventListener('keydown', onKey);
    });
}

document.addEventListener('submit', (event) => {
    const form = event.target;
    const source = event.submitter?.hasAttribute('data-confirm') ? event.submitter : form;

    // Page scripts run first and may already have stopped the submit (e.g. unsaved changes).
    if (event.defaultPrevented || !source.hasAttribute?.('data-confirm') || form.dataset.confirmed === '1') {
        return;
    }

    event.preventDefault();
    const submitter = event.submitter;

    confirmDialog({
        title: source.dataset.confirmTitle,
        text: source.dataset.confirm,
        button: source.dataset.confirmButton,
        icon: source.dataset.confirmIcon,
    }).then((answer) => {
        if (!answer) {
            return;
        }
        form.dataset.confirmed = '1';
        // requestSubmit keeps the clicked button's name/value (e.g. kirim=1) and HTML validation.
        form.requestSubmit(submitter && submitter.form === form ? submitter : undefined);
        delete form.dataset.confirmed;
    });
});

window.confirmDialog = confirmDialog;
