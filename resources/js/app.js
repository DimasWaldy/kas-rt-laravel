import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('appShell', () => ({
    mobileMenuOpen: false,
    navigating: false,
    theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
    now: new Date(),
    clockTimer: null,

    init() {
        this.clockTimer = setInterval(() => {
            this.now = new Date();
        }, 1000);
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

Alpine.start();
