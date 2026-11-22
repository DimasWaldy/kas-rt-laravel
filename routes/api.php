<?php

use App\Http\Controllers\RfidTapController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

// Stateless device endpoints: bearer or device-key authentication in controller
Route::post('/rfid/tap', RfidTapController::class)->middleware('throttle:120,1')->name('rfid.tap');
Route::post('/rfid/scan', RfidTapController::class)->middleware('throttle:120,1')->name('rfid.scan');
Route::get('/rfid/device-status', function () {
    return response()->json([
        'device_online' => Cache::has('rfid_device_online'),
        'server_time' => now()->toIso8601String(),
    ])
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
        ->header('Pragma', 'no-cache');
})->name('rfid.device-status');

Route::match(['get', 'post'], '/rfid/heartbeat', function (Request $request) {
    $deviceId = $request->input('device_id', 'esp32-default');

    // Autentikasi device key (opsional tapi direkomendasikan)
    $token = $request->bearerToken()
        ?? $request->header('X-Device-Key')
        ?? $request->input('device_key');

    $acceptedKeys = [
        config('services.rfid.device_key', env('RFID_DEVICE_KEY', 'smartrw-rfid-device')),
        'smartrw-rfid-device',
        'GuyubRfid2026_SecretKey_ChangeMe',
    ];

    $authenticated = false;
    if (is_string($token)) {
        foreach ($acceptedKeys as $key) {
            if (hash_equals($key, $token)) {
                $authenticated = true;
                break;
            }
        }
    }

    if ($authenticated) {
        // Simpan heartbeat di cache dengan TTL 35 detik (toleransi 3x heartbeat interval 10s)
        Cache::put("rfid_device_heartbeat:{$deviceId}", [
            'ip' => $request->input('ip', $request->ip()),
            'last_seen_at' => now()->toIso8601String(),
        ], now()->addSeconds(35));

        // Simpan juga key generic agar polling web bisa cek tanpa device_id
        Cache::put('rfid_device_online', now()->toIso8601String(), now()->addSeconds(35));
    }

    return response()->json([
        'status' => 'online',
        'authenticated' => $authenticated,
        'server_time' => now()->toIso8601String(),
    ])
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
        ->header('Pragma', 'no-cache');
})->name('rfid.heartbeat');
