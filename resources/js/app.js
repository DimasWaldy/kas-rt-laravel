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

Alpine.start();
