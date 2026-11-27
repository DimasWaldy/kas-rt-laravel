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

test('RFID monitor exposes new live scan alert, tracks enrollment reload, and allows dismissal', async (t) => {
    let call = 0;
    const snapshots = [
        { count: 0, device_online: true, last_scan: { id: 'scan-1', status: 'error', type: 'http_error', uid_masked: 'DE••••EF', message: 'Kartu belum terdaftar.' } },
        { count: 1, device_online: true, last_scan: { id: 'scan-2', status: 'success', type: 'ktp_registered', uid_masked: 'A1••••D4', message: 'Berhasil.' } },
    ];
    t.mock.method(globalThis, 'fetch', async () => ({
        ok: true,
        json: async () => snapshots[call++],
    }));
    const monitor = rfidMonitor();
    monitor.$el = { dataset: { statusUrl: '/admin/warga/rfid/status' } };

    await monitor.refresh();
    assert.equal(monitor.liveScan.id, 'scan-1');
    assert.equal(monitor.needsReload, false);

    monitor.dismissScan();
    assert.equal(monitor.liveScan, null);

    await monitor.refresh();
    assert.equal(monitor.liveScan.id, 'scan-2');
    assert.equal(monitor.needsReload, true);
    monitor.destroy();
});
