import assert from 'node:assert/strict';
import test from 'node:test';
import { fileURLToPath } from 'node:url';
import { build } from 'vite';

test('Tailwind configuration still compiles forms, responsive and dark variants', async () => {
    // Exercise the real Vite plugin, CSS entrypoint and Blade source detection.
    const result = await build({
        configFile: fileURLToPath(new URL('../../vite.config.js', import.meta.url)),
        logLevel: 'silent',
        build: { write: false, minify: false, cssMinify: false },
    });
    const css = result.output.filter((item) => item.type === 'asset' && item.fileName.endsWith('.css'))
        .map((item) => String(item.source)).join('\n');
    assert.match(css, /\.md\\:grid-cols-2/);
    assert.match(css, /\.dark\\:bg-slate-900/);
    assert.match(css, /:where\(\.dark, \.dark \*\)/);
    assert.match(css, /\[type=['"]text['"]\]/);
    assert.match(css, /\.focus\\:ring-emerald-500/);
    assert.match(css, /\.shadow-xs/);
    assert.match(css, /\.bg-black\\\/50/);
    assert.match(css, /\.dark \.bg-lime-50\s*\{\s*background-color:\s*(?:rgb\(6 78 59 \/ (?:0?\.25)\)|#064e3b40)\s*!important/);
    assert.match(css, /Font Awesome 6 Free/);
    assert.match(css, /Figtree/);
    assert.doesNotMatch(css, /@tailwind|@source|@plugin/);
});

test('application Axios bootstrap retains AJAX header and request behavior', async () => {
    globalThis.window = {};
    try {
        await import('../../resources/js/bootstrap.js');
        const axios = window.axios;
        const response = await axios.get('/notifications', {
            adapter: async (config) => {
                assert.equal(config.method, 'get');
                assert.equal(config.url, '/notifications');
                assert.equal(config.headers.get('X-Requested-With'), 'XMLHttpRequest');
                return { data: { success: true }, status: 200, statusText: 'OK', headers: {}, config };
            },
        });
        assert.equal(response.data.success, true);
    } finally {
        delete globalThis.window;
    }
});
