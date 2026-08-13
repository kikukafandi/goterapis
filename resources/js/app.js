import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';

Alpine.plugin(collapse);
Alpine.plugin(focus);

Alpine.data('selectDropdown', select => ({
    select,
    open: false,
    active: 0,
    options: [],
    get disabled() { return this.select.disabled; },
    get value() { return this.select.value; },
    get label() { return this.options.find(option => option.value === this.value)?.label || 'Pilih opsi'; },
    init() {
        this.refresh();
        this.select.addEventListener('change', () => this.refresh());
        this.select.addEventListener('invalid', () => this.$refs.button.focus());
        new MutationObserver(() => this.refresh()).observe(this.select, { attributes: true, childList: true, subtree: true });
    },
    refresh() {
        this.options = [...this.select.options].map((option, index) => ({ value: option.value, label: option.text, disabled: option.disabled, index }));
        this.active = Math.max(0, this.options.findIndex(option => option.value === this.select.value));
    },
    toggle() {
        if (!this.disabled) {
            this.open ? this.close() : this.show();
        }
    },
    show() {
        this.open = true;
        this.active = Math.max(0, this.options.findIndex(option => option.value === this.value));
        this.$nextTick(() => {
            this.place();
            this.scroll();
        });
    },
    place() {
        const list = this.$refs.list;
        const button = this.$refs.button;
        const gutter = 16;
        const available = window.innerWidth - (gutter * 2);

        list.style.width = `${Math.min(Math.max(button.offsetWidth, list.scrollWidth), available)}px`;
        list.classList.toggle('is-right-aligned', button.getBoundingClientRect().left + list.offsetWidth > window.innerWidth - gutter);
    },
    close(focus = false) {
        this.open = false;
        if (focus) this.$refs.button.focus();
    },
    move(direction) {
        if (!this.open) this.show();
        let next = this.active;
        do {
            next = Math.max(0, Math.min(this.options.length - 1, next + direction));
        } while (this.options[next]?.disabled && next !== 0 && next !== this.options.length - 1);
        this.active = next;
        this.scroll();
    },
    edge(index) {
        this.show();
        this.active = index;
        while (this.options[this.active]?.disabled && this.active > 0 && this.active < this.options.length - 1) this.active += index === 0 ? 1 : -1;
        this.scroll();
    },
    choose(option = this.options[this.active]) {
        if (!option || option.disabled) return;
        this.select.value = option.value;
        this.select.dispatchEvent(new Event('change', { bubbles: true }));
        this.close(true);
    },
    scroll() {
        this.$nextTick(() => this.$refs.list?.querySelector(`[data-index="${this.active}"]`)?.scrollIntoView({ block: 'nearest' }));
    },
}));

Alpine.data('notifications', (initialItems = [], channel = null) => ({
    items: initialItems,
    init() {
        this.items = this.items.map(item => this.prepare(item));
        this.items.forEach(item => this.schedule(item));

        if (channel) {
            window.Echo?.private(channel).notification(notification => this.add({ ...notification, type: 'notification' }));
        }
    },
    prepare(item) {
        return { ...item, toastId: crypto.randomUUID() };
    },
    schedule(item, delay = item.type === 'error' ? 7000 : 4500) {
        setTimeout(() => this.close(item.toastId), delay);
    },
    add(item) {
        const toast = this.prepare(item);

        this.items = [toast, ...this.items].slice(0, 3);
        this.schedule(toast, 5000);
    },
    close(id) {
        this.items = this.items.filter(item => item.toastId !== id);
    },
}));

document.querySelectorAll('select:not([multiple]):not([data-native-select])').forEach(select => {
    const id = select.id || `select-${crypto.randomUUID()}`;
    const wrapper = document.createElement('span');
    const classes = [...select.classList];
    const isLayoutClass = className => /^(?:(?:sm|md|lg|xl|2xl):)?(?:block|inline-block|flex|inline-flex|grid|inline-grid|w-|min-w-|max-w-|m[trblxy]?-|self-|place-self-|order-|grow|shrink|basis-|flex-)/.test(className);
    const layoutClasses = classes.filter(isLayoutClass);
    if (classes.includes('isian') && !layoutClasses.some(className => /^(?:(?:sm|md|lg|xl|2xl):)?w-/.test(className))) layoutClasses.push('w-full');
    const fieldClasses = classes.filter(className => !isLayoutClass(className) && className !== 'appearance-none');
    const isPlain = classes.includes('appearance-none') && classes.includes('bg-transparent');

    select.id = id;
    wrapper.classList.add('select-dropdown', ...layoutClasses);
    wrapper.classList.toggle('select-dropdown--plain', isPlain);
    wrapper.setAttribute('x-data', 'selectDropdown($refs.native)');
    wrapper.innerHTML = `
        <button x-ref="button" type="button" class="select-dropdown__button ${fieldClasses.join(' ')}" :disabled="disabled" :aria-expanded="open" aria-haspopup="listbox" aria-controls="${id}-list" @click.stop="toggle" @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.home.prevent="edge(0)" @keydown.end.prevent="edge(options.length - 1)" @keydown.enter.prevent="open ? choose() : show()" @keydown.space.prevent="open ? choose() : show()" @keydown.escape.prevent="close(true)" @keydown.tab="close()"><span x-text="label"></span></button>
        <div x-ref="list" x-show="open" x-cloak id="${id}-list" class="select-dropdown__list" role="listbox" :aria-activedescendant="open ? '${id}-option-' + active : null" @click.outside="close()">
            <template x-for="option in options" :key="option.index">
                <button type="button" role="option" :id="'${id}-option-' + option.index" :data-index="option.index" :aria-selected="option.value === value" :disabled="option.disabled" class="select-dropdown__option" :class="{ 'is-active': option.index === active, 'is-selected': option.value === value }" @mouseenter="active = option.index" @click="choose(option)" x-text="option.label"></button>
            </template>
        </div>`;
    select.before(wrapper);
    select.classList.add('select-dropdown__native');
    select.setAttribute('x-ref', 'native');
    wrapper.prepend(select);
});

window.Alpine = Alpine;
Alpine.start();

import './echo';
