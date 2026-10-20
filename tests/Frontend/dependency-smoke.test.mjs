import assert from 'node:assert/strict';
import test from 'node:test';
import { fileURLToPath } from 'node:url';
import postcss from 'postcss';
import tailwind from 'tailwindcss';
import loadConfig from 'tailwindcss/loadConfig.js';

const tailwindConfig = loadConfig(fileURLToPath(new URL('../../tailwind.config.js', import.meta.url)));

test('Tailwind configuration still compiles forms, responsive and dark variants', async () => {
    const result = await postcss([
        tailwind({
            ...tailwindConfig,
            content: [{
                raw: '<form class="grid md:grid-cols-2 dark:bg-slate-900"><input class="form-input focus:ring-emerald-500"></form>',
                extension: 'html',
            }],
        }),
    ]).process('@tailwind base; @tailwind components; @tailwind utilities;', { from: undefined });

    assert.match(result.css, /\.md\\:grid-cols-2/);
    assert.match(result.css, /\.dark\\:bg-slate-900/);
    assert.match(result.css, /\.dark/);
    assert.match(result.css, /\.form-input/);
    assert.match(result.css, /\.focus\\:ring-emerald-500/);
    assert.equal(result.warnings().length, 0);
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
