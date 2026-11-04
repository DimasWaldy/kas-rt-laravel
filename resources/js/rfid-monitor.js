export function rfidMonitor() {
    return {
        state: null,
        error: '',
        timer: null,
        stopped: false,
        controller: null,
        init() {
            this.refresh();
        },
        async refresh() {
            if (this.stopped) return;
            this.controller = new AbortController();
            const timeout = setTimeout(() => this.controller?.abort(), 10000);
            try {
                const response = await fetch(this.$el.dataset.statusUrl, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                    cache: 'no-store',
                    signal: this.controller.signal,
                });
                if (!response.ok) {
                    if ([401, 403, 404, 419].includes(response.status)) this.stopped = true;
                    throw new Error('Status tidak dapat dimuat. Periksa koneksi atau login ulang.');
                }
                this.state = await response.json();
                this.error = '';
            } catch (error) {
                this.error = error.name === 'AbortError' ? 'Koneksi terputus. Mencoba kembali…' : error.message;
            } finally {
                clearTimeout(timeout);
                if (!this.stopped) this.timer = setTimeout(() => this.refresh(), 5000);
            }
        },
        destroy() {
            this.stopped = true;
            clearTimeout(this.timer);
            this.controller?.abort();
        },
    };
}
