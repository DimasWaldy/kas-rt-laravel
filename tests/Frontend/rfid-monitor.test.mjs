import assert from 'node:assert/strict';
import test from 'node:test';
import { rfidMonitor } from '../../resources/js/rfid-monitor.js';

test('RFID monitor fetches same-origin private feed and schedules refresh', async (t) => {
    const data = { count: 1, attendees: [{ id: 1, name: 'Warga', time: '19:00' }] };
    t.mock.method(globalThis, 'fetch', async (url, options) => {
        assert.equal(url, '/kegiatan/1/rfid/status');
        assert.equal(options.credentials, 'same-origin');
        assert.equal(options.cache, 'no-store');
        assert.equal(options.headers.Accept, 'application/json');
        assert.equal(options.headers.Authorization, undefined);
        return { ok: true, json: async () => data };
    });
    const monitor = rfidMonitor();
    monitor.$el = { dataset: { statusUrl: '/kegiatan/1/rfid/status' } };
    await monitor.refresh();
    assert.deepEqual(monitor.state, data);
    assert.equal(monitor.error, '');
    assert.ok(monitor.timer);
    monitor.destroy();
    assert.equal(monitor.stopped, true);
    assert.equal(monitor.controller.signal.aborted, true);
});

test('RFID monitor stops polling on revoked access and keeps an explicit error', async (t) => {
    t.mock.method(globalThis, 'fetch', async () => ({ ok: false, status: 403 }));
    const monitor = rfidMonitor();
    monitor.$el = { dataset: { statusUrl: '/status' } };
    await monitor.refresh();
    assert.equal(monitor.stopped, true);
    assert.equal(monitor.timer, null);
    assert.match(monitor.error, /login ulang/);
});

test('RFID monitor retains last snapshot on network failure and retries', async (t) => {
    t.mock.method(globalThis, 'fetch', async () => { throw new Error('Offline'); });
    const monitor = rfidMonitor();
    monitor.$el = { dataset: { statusUrl: '/status' } };
    monitor.state = { count: 3 };
    await monitor.refresh();
    assert.equal(monitor.state.count, 3);
    assert.equal(monitor.error, 'Offline');
    assert.ok(monitor.timer);
    monitor.destroy();
});
