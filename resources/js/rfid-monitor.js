export function rfidMonitor() {
    return {
        state: null,
        error: '',
        timer: null,
        stopped: false,
        controller: null,
        dismissedScanId: null,
        lastSeenScanId: null,
        needsReload: false,
        visibilityHandler: null,
        init() {
            if (typeof document !== 'undefined') {
                this.visibilityHandler = () => {
                    if (!document.hidden && !this.stopped) {
                        clearTimeout(this.timer);
                        this.refresh();
                    }
                };
                document.addEventListener('visibilitychange', this.visibilityHandler);
            }
            this.refresh();
        },
        get liveScan() {
            const scan = this.state?.last_scan;
            if (!scan || (scan.id && scan.id === this.dismissedScanId)) {
                return null;
            }
            return scan;
        },
        dismissScan() {
            if (this.state?.last_scan?.id) {
                this.dismissedScanId = this.state.last_scan.id;
            }
        },
        async refresh() {
            if (this.stopped) return;
            this.controller = new AbortController();
            const timeout = setTimeout(() => this.controller?.abort(), 10000);
            try {
                const response = await fetch(this.$el.dataset.statusUrl, {
                    headers: {
                        Accept: 'application/json',
                        'Cache-Control': 'no-cache, no-store, must-revalidate',
                        Pragma: 'no-cache',
                    },
                    credentials: 'same-origin',
                    cache: 'no-store',
                    signal: this.controller.signal,
                });
                if (!response.ok) {
                    if ([401, 403, 404, 419].includes(response.status)) this.stopped = true;
                    throw new Error('Status tidak dapat dimuat. Periksa koneksi atau login ulang.');
                }
                const data = await response.json();
                this.state = data;
                this.error = '';

                const scan = data?.last_scan;
                if (scan?.id && scan.id !== this.lastSeenScanId) {
                    const isNewScan = this.lastSeenScanId !== null;
                    this.lastSeenScanId = scan.id;
                    if (isNewScan && ['registered', 'ktp_registered', 'koperasi_registered'].includes(scan.type)) {
                        this.needsReload = true;
                    }
                    if (isNewScan && typeof navigator !== 'undefined' && navigator.vibrate) {
                        try {
                            navigator.vibrate(scan.status === 'error' ? [200, 100, 200] : [140, 60, 140]);
                        } catch (e) {}
                    }
                }
            } catch (error) {
                this.error = error.name === 'AbortError' ? 'Koneksi terputus. Mencoba kembali…' : error.message;
            } finally {
                clearTimeout(timeout);
                if (!this.stopped) this.timer = setTimeout(() => this.refresh(), 1500);
            }
        },
        destroy() {
            this.stopped = true;
            clearTimeout(this.timer);
            this.controller?.abort();
            if (this.visibilityHandler && typeof document !== 'undefined') {
                document.removeEventListener('visibilitychange', this.visibilityHandler);
            }
        },
    };
}
