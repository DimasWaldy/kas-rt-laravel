import './bootstrap';

import Alpine from 'alpinejs';
import lottie from 'lottie-web/build/player/lottie_light';
import { rfidMonitor } from './rfid-monitor';
import sidebarAnimationData from './sidebar-animation.json';

window.Alpine = Alpine;
Alpine.data('rfidMonitor', rfidMonitor);

const revealTargets = document.querySelectorAll('[data-reveal]');

if (revealTargets.length > 0) {
    const io = new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    io.unobserve(entry.target);
                }
            }
        },
        {
            threshold: 0.12,
            rootMargin: '0px 0px -8% 0px',
        },
    );

    revealTargets.forEach((target) => io.observe(target));
}

// Lottie Sidebar Toggle Component
Alpine.data('sidebarLottie', (stateGetter = null) => ({
    anim: null,
    init() {
        this.anim = lottie.loadAnimation({
            container: this.$el,
            renderer: 'svg',
            loop: false,
            autoplay: false,
            animationData: sidebarAnimationData,
        });

        this.anim.setSpeed(1.25);
        this.anim.goToAndStop(0, true);

        if (typeof stateGetter === 'function') {
            let isFirst = true;
            this.$watch(stateGetter, () => {
                if (isFirst) {
                    isFirst = false;
                    return;
                }
                this.play();
            });
        }
    },
    play() {
        if (this.anim) {
            this.anim.goToAndPlay(0, true);
        }
    },
}));

// Command Palette (Ctrl + K) Component
Alpine.data('commandPalette', (initialItems = []) => ({
    open: false,
    search: '',
    activeIndex: 0,
    items: initialItems,

    init() {
        window.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                this.toggle();
            }
        });
    },

    toggle() {
        if (this.open) {
            this.close();
        } else {
            this.show();
        }
    },

    show() {
        this.open = true;
        this.search = '';
        this.activeIndex = 0;
        this.$nextTick(() => {
            if (this.$refs.searchInput) {
                this.$refs.searchInput.focus();
            }
        });
    },

    close() {
        this.open = false;
        this.search = '';
        this.activeIndex = 0;
    },

    get filteredItems() {
        const q = this.search.trim().toLowerCase();
        if (!q) {
            return this.items;
        }
        return this.items.filter((item) => {
            const label = (item.label || '').toLowerCase();
            const group = (item.group || '').toLowerCase();
            const keywords = (item.keywords || '').toLowerCase();
            return label.includes(q) || group.includes(q) || keywords.includes(q);
        });
    },

    selectNext() {
        const max = this.filteredItems.length;
        if (max === 0) return;
        this.activeIndex = (this.activeIndex + 1) % max;
        this.scrollToActive();
    },

    selectPrev() {
        const max = this.filteredItems.length;
        if (max === 0) return;
        this.activeIndex = (this.activeIndex - 1 + max) % max;
        this.scrollToActive();
    },

    scrollToActive() {
        this.$nextTick(() => {
            const activeEl = this.$el.querySelector(`[data-palette-idx="${this.activeIndex}"]`);
            if (activeEl) {
                activeEl.scrollIntoView({ block: 'nearest' });
            }
        });
    },

    executeActive() {
        const current = this.filteredItems[this.activeIndex];
        if (current) {
            this.execute(current);
        }
    },

    execute(item) {
        this.close();
        if (item.type === 'action') {
            if (item.action === 'toggleTheme') {
                window.dispatchEvent(new CustomEvent('toggle-theme'));
            } else if (item.action === 'toggleDesktopSidebar') {
                window.dispatchEvent(new CustomEvent('toggle-sidebar'));
            }
        } else if (item.url) {
            window.location.href = item.url;
        }
    },
}));

Alpine.data('appShell', () => ({
    mobileMenuOpen: false,
    desktopSidebarCollapsed: localStorage.getItem('smart-rw-desktop-sidebar') === 'collapsed',
    navigating: false,
    theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
    now: new Date(),
    clockTimer: null,

    init() {
        this.clockTimer = setInterval(() => {
            this.now = new Date();
        }, 1000);
    },

    toggleMobileMenu() {
        this.mobileMenuOpen = !this.mobileMenuOpen;
    },

    toggleDesktopSidebar() {
        this.desktopSidebarCollapsed = !this.desktopSidebarCollapsed;
        localStorage.setItem('smart-rw-desktop-sidebar', this.desktopSidebarCollapsed ? 'collapsed' : 'expanded');
    },

    get formattedDate() {
        return new Intl.DateTimeFormat('id-ID', {
            weekday: 'long',
            day: '2-digit',
            month: 'long',
            year: 'numeric',
            timeZone: 'Asia/Jakarta',
        }).format(this.now);
    },

    get formattedTime() {
        return new Intl.DateTimeFormat('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false,
            timeZone: 'Asia/Jakarta',
        }).format(this.now).replaceAll('.', ':');
    },

    toggleTheme() {
        this.theme = this.theme === 'dark' ? 'light' : 'dark';
        document.documentElement.classList.toggle('dark', this.theme === 'dark');
        localStorage.setItem('smart-rw-theme', this.theme);
    },

    goToMenu(event) {
        const link = event.currentTarget;
        const href = link?.href;

        if (! href || link.target === '_blank' || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) {
            return;
        }

        if (href === window.location.href) {
            this.mobileMenuOpen = false;
            return;
        }

        event.preventDefault();
        this.navigating = true;
        this.mobileMenuOpen = false;
        window.location.assign(href);
    },
}));

Alpine.data('publicUmkmCatalog', (config = {}) => ({
    activeTab: config.defaultTab || 'produk',
    search: '',
    selectedCategory: '',
    showAllProduk: false,
    showAllUsaha: false,
    produkMeta: config.produkMeta || [],
    usahaMeta: config.usahaMeta || [],
    umkmDetails: config.umkmDetails || {},
    modalOpen: false,
    selectedUmkm: null,
    quantities: {},
    orderNote: '',

    matchesItem(kategori, searchText) {
        if (this.selectedCategory && kategori !== this.selectedCategory) {
            return false;
        }

        const q = this.search.trim().toLowerCase();
        if (! q) {
            return true;
        }

        return (searchText || '').toLowerCase().includes(q);
    },

    shouldShowProduk(index, kategori, searchText) {
        if (! this.matchesItem(kategori, searchText)) {
            return false;
        }

        if (this.showAllProduk || this.search.trim() !== '' || this.selectedCategory !== '') {
            return true;
        }

        return index < 6;
    },

    shouldShowUsaha(index, kategori, searchText) {
        if (! this.matchesItem(kategori, searchText)) {
            return false;
        }

        if (this.showAllUsaha || this.search.trim() !== '' || this.selectedCategory !== '') {
            return true;
        }

        return index < 6;
    },

    get filteredProdukCount() {
        return this.produkMeta.filter((item) => this.matchesItem(item.kategori, item.search)).length;
    },

    get filteredUsahaCount() {
        return this.usahaMeta.filter((item) => this.matchesItem(item.kategori, item.search)).length;
    },

    resetFilters() {
        this.search = '';
        this.selectedCategory = '';
    },

    openUmkm(umkmId) {
        const detail = this.umkmDetails[String(umkmId)] || this.umkmDetails[umkmId];
        if (! detail) {
            return;
        }

        this.selectedUmkm = detail;
        this.orderNote = '';
        const nextQuantities = {};
        for (const produk of detail.produk || []) {
            nextQuantities[produk.id] = 1;
        }
        this.quantities = nextQuantities;
        this.modalOpen = true;
    },

    closeModal() {
        this.modalOpen = false;
    },

    getQty(produkId) {
        return Math.max(1, Number(this.quantities[produkId] || 1));
    },

    incrementQty(produkId) {
        this.quantities[produkId] = Math.min(99, this.getQty(produkId) + 1);
    },

    decrementQty(produkId) {
        this.quantities[produkId] = Math.max(1, this.getQty(produkId) - 1);
    },

    formatRupiah(nominal) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(nominal);
    },

    buildProductWhatsappUrl(produk) {
        if (! this.selectedUmkm || ! this.selectedUmkm.whatsapp_phone) {
            return '#';
        }

        const qty = this.getQty(produk.id);
        const satuan = produk.satuan_harga ? ' ' + produk.satuan_harga : ' item';
        let totalTeks = '';

        if (produk.harga !== null && produk.harga !== undefined && qty > 1) {
            totalTeks = ` (Estimasi: ${this.formatRupiah(produk.harga * qty)})`;
        }

        let pesan = `Halo ${this.selectedUmkm.nama_usaha}, perkenalkan saya Warga (Pengunjung Smart RW).\n\n`;
        pesan += `Saya melihat etalase UMKM di halaman utama Smart RW dan tertarik untuk memesan:\n`;
        pesan += `• Produk: *${produk.nama_produk}*\n`;
        pesan += `• Harga: ${produk.harga_formatted}\n`;
        pesan += `• Jumlah: ${qty}${satuan}${totalTeks}\n`;

        const note = this.orderNote.trim();
        if (note) {
            pesan += `• Catatan: ${note}\n`;
        }

        pesan += `\nApakah produk ini masih tersedia untuk dipesan? Terima kasih! 🙏`;

        return `https://wa.me/${this.selectedUmkm.whatsapp_phone}?text=${encodeURIComponent(pesan)}`;
    },
}));

Alpine.data('assistantWidget', (config = {}) => ({
    open: false,
    loading: false,
    input: '',
    endpoint: config.endpoint || '/asisten/chat',
    csrfToken: config.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '',
    storageKey: 'smart-rw-assistant-history-v1',
    messages: [],
    quickPrompts: [
        'Cara bayar iuran bulanan?',
        'Alur pengajuan surat pengantar?',
        'Jadwal & cara setor Bank Sampah?',
        'Cari produk UMKM warga',
    ],

    init() {
        try {
            const raw = sessionStorage.getItem(this.storageKey);
            if (raw) {
                const parsed = JSON.parse(raw);
                if (Array.isArray(parsed) && parsed.length > 0) {
                    this.messages = parsed.slice(-20);
                }
            }
        } catch {
            this.messages = [];
        }

        if (this.messages.length === 0) {
            this.seedWelcomeMessage();
        }
    },

    seedWelcomeMessage() {
        this.messages = [
            {
                id: 'welcome',
                role: 'model',
                text: 'Halo! Saya **Asisten Smart RW**. Tanya saya seputar cara buat surat, cek/bayar iuran, Bank Sampah, agenda kegiatan, atau cari produk **UMKM warga** untuk langsung order ke WhatsApp.',
                products: [],
                stores: [],
                links: [],
            },
        ];
    },

    toggle() {
        this.open = !this.open;
        if (this.open) {
            this.$nextTick(() => {
                this.scrollToBottom();
                this.$refs.chatInput?.focus();
            });
        }
    },

    close() {
        this.open = false;
    },

    resetChat() {
        this.seedWelcomeMessage();
        this.saveMessages();
        this.$nextTick(() => this.scrollToBottom());
    },

    saveMessages() {
        try {
            sessionStorage.setItem(this.storageKey, JSON.stringify(this.messages.slice(-20)));
        } catch {
            // Abaikan error kuota sessionStorage pada mode privat browser.
        }
    },

    scrollToBottom() {
        const container = this.$refs.chatScroll;
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    },

    formatReplyHtml(text) {
        const escaped = String(text || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');

        return escaped
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n/g, '<br>');
    },

    askQuickPrompt(promptText) {
        this.input = promptText;
        this.sendMessage();
    },

    async sendMessage() {
        const text = this.input.trim();
        if (!text || this.loading) {
            return;
        }

        const history = this.messages
            .filter((item) => item.id !== 'welcome')
            .slice(-6)
            .map((item) => ({
                role: item.role === 'model' ? 'model' : 'user',
                text: String(item.text || '').slice(0, 500),
            }));

        this.messages.push({
            id: `user-${Date.now()}`,
            role: 'user',
            text,
            products: [],
            stores: [],
            links: [],
        });

        this.input = '';
        this.loading = true;
        this.saveMessages();
        this.$nextTick(() => this.scrollToBottom());

        try {
            const response = await fetch(this.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    message: text,
                    history,
                }),
            });

            if (response.status === 429) {
                throw new Error('Permintaan terlalu cepat. Tunggu beberapa detik lalu coba lagi ya.');
            }

            if (!response.ok) {
                throw new Error('Maaf, asisten sedang sibuk. Silakan coba beberapa saat lagi.');
            }

            const data = await response.json();

            this.messages.push({
                id: `bot-${Date.now()}`,
                role: 'model',
                text: data.reply || 'Maaf, belum ada jawaban yang bisa ditampilkan.',
                products: Array.isArray(data.products) ? data.products : [],
                stores: Array.isArray(data.stores) ? data.stores : [],
                links: Array.isArray(data.links) ? data.links : [],
            });
        } catch (error) {
            this.messages.push({
                id: `err-${Date.now()}`,
                role: 'model',
                text: error?.message || 'Koneksi terganggu. Silakan coba kirim pertanyaan kembali.',
                products: [],
                stores: [],
                links: [],
            });
        } finally {
            this.loading = false;
            this.saveMessages();
            this.$nextTick(() => {
                this.scrollToBottom();
                this.$refs.chatInput?.focus();
            });
        }
    },
}));

Alpine.start();
