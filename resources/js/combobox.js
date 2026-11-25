/**
 * Dropdown with an optional search box, used by <x-ui.combobox>.
 * The chosen value lives in a hidden input, which fires a bubbling "change" event
 * so forms that react to changes (e.g. the public auto-filter) keep working.
 */
export default ({ options = [], value = '', searchable = true }) => ({
    options, // [{ value, label }]
    value,
    searchable,
    open: false,
    query: '',
    active: 0,

    get selected() {
        return this.options.find((option) => option.value === this.value) ?? this.options[0];
    },

    get filtered() {
        const query = this.query.trim().toLowerCase();

        return query === '' ? this.options : this.options.filter((option) => option.label.toLowerCase().includes(query));
    },

    toggle() {
        this.open ? this.close() : this.show();
    },

    show() {
        this.open = true;
        this.query = '';
        this.active = Math.max(0, this.filtered.findIndex((option) => option.value === this.value));
        this.$nextTick(() => {
            (this.searchable ? this.$refs.search : this.$refs.list)?.focus();
            this.scrollToActive();
        });
    },

    close(focusButton = false) {
        this.open = false;
        if (focusButton) {
            this.$refs.button.focus();
        }
    },

    choose(option) {
        if (!option) {
            return;
        }
        const changed = option.value !== this.value;
        this.value = option.value;
        this.close(true);
        if (changed) {
            this.$nextTick(() => this.$refs.input.dispatchEvent(new Event('change', { bubbles: true })));
        }
    },

    move(step) {
        const count = this.filtered.length;
        if (count === 0) {
            return;
        }
        this.active = (this.active + step + count) % count;
        this.scrollToActive();
    },

    scrollToActive() {
        this.$nextTick(() => this.$refs.list?.querySelector(`[data-index="${this.active}"]`)?.scrollIntoView({ block: 'nearest' }));
    },
});
