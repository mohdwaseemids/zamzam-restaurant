import Alpine from 'alpinejs';

const cfg = window.ZZ || { routes: {}, csrf: '' };

async function post(url, body) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': cfg.csrf,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(body || {}),
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(data.message || 'Something went wrong. Please try again.');
    }

    return data;
}

const rupee = (n) => '₹' + Number(n || 0).toLocaleString('en-IN', { maximumFractionDigits: 2 });

document.addEventListener('alpine:init', () => {
    /* ---------- Cart (session on the server, mirrored here) ---------- */
    Alpine.store('cart', {
        open: false,
        busy: false,
        type: 'delivery',
        data: { items: [], count: cfg.cartCount || 0, subtotal: 0, discount: 0, coupon_code: null, delivery_charge: 0, tax: 0, total: 0, meets_minimum: true, min_order: 0 },

        async load() {
            try {
                const response = await fetch(cfg.routes.cart + '?type=' + this.type, { headers: { Accept: 'application/json' } });
                this.data = await response.json();
            } catch (e) {
                /* keep the last known state */
            }
        },

        qty(id) {
            const line = this.data.items.find((item) => item.id === id);
            return line ? line.quantity : 0;
        },

        async add(id, name) {
            this.busy = true;
            try {
                this.data = await post(cfg.routes.cartAdd, { item_id: id, quantity: 1 });
                Alpine.store('toast').show((name || 'Dish') + ' added to your order');
            } catch (e) {
                Alpine.store('toast').show(e.message, true);
            }
            this.busy = false;
        },

        async set(id, quantity) {
            this.busy = true;
            try {
                this.data = await post(cfg.routes.cartUpdate, { item_id: id, quantity: Math.max(0, quantity), type: this.type });
            } catch (e) {
                Alpine.store('toast').show(e.message, true);
            }
            this.busy = false;
        },

        async applyCoupon(code) {
            this.busy = true;
            let ok = true;
            try {
                this.data = await post(cfg.routes.cartCoupon, { code: code || '', type: this.type });
                Alpine.store('toast').show(code ? 'Coupon applied' : 'Coupon removed');
            } catch (e) {
                ok = false;
                Alpine.store('toast').show(e.message, true);
            }
            this.busy = false;
            return ok;
        },

        async setType(type) {
            this.type = type;
            await this.load();
        },

        money: rupee,
    });

    Alpine.store('toast', {
        message: '',
        error: false,
        visible: false,
        timer: null,
        show(message, error = false) {
            this.message = message;
            this.error = error;
            this.visible = true;
            clearTimeout(this.timer);
            this.timer = setTimeout(() => (this.visible = false), 2600);
        },
    });

    /* ---------- Fullscreen viewer for photos and videos ---------- */
    Alpine.store('viewer', {
        open: false,
        kind: 'image', // image | embed | file
        items: [],
        index: 0,
        get current() {
            return this.items[this.index] || {};
        },
        photos(items, index) {
            this.kind = 'image';
            this.items = items;
            this.index = index;
            this.open = true;
        },
        video(item) {
            this.kind = item.file ? 'file' : 'embed';
            this.items = [item];
            this.index = 0;
            this.open = true;
        },
        step(direction) {
            if (this.kind !== 'image' || this.items.length < 2) return;
            this.index = (this.index + direction + this.items.length) % this.items.length;
        },
        close() {
            this.open = false;
            this.items = []; // also stops any playing video
        },
    });

    /* ---------- Menu page: search + veg filter ---------- */
    Alpine.data('menuPage', () => ({
        q: '',
        veg: false,
        active: null,
        match(name, isVeg) {
            return (!this.veg || isVeg) && (this.q.trim() === '' || name.includes(this.q.trim().toLowerCase()));
        },
        any(items) {
            return items.some(([name, isVeg]) => this.match(name, isVeg));
        },
        init() {
            const sections = this.$root.querySelectorAll('[data-category]');
            const observer = new IntersectionObserver(
                (entries) => entries.forEach((entry) => entry.isIntersecting && (this.active = entry.target.dataset.category)),
                { rootMargin: '-30% 0px -60% 0px' },
            );
            sections.forEach((section) => observer.observe(section));
        },
    }));
});

/* ---------- Scroll reveal ---------- */
function setupReveal() {
    const elements = document.querySelectorAll('.reveal');

    if (!('IntersectionObserver' in window)) {
        elements.forEach((el) => el.classList.add('in'));
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in');
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.12, rootMargin: '0px 0px -40px 0px' },
    );

    elements.forEach((el) => observer.observe(el));
}

window.Alpine = Alpine;
Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    setupReveal();
    Alpine.store('cart').load();
});
