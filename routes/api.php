<?php

use App\Http\Controllers\RfidTapController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

// Stateless device endpoints: bearer or device-key authentication in controller
Route::post('/rfid/tap', RfidTapController::class)->middleware('throttle:120,1')->name('rfid.tap');
Route::post('/rfid/scan', RfidTapController::class)->middleware('throttle:120,1')->name('rfid.scan');
Route::get('/rfid/device-status', function () {
    $lastSeen = (float) Cache::get('rfid_device_online_at', 0);
    $interval = (float) Cache::get('rfid_device_hb_interval', 1.5);
    $timeout = max(3.5, min(12.0, $interval * 2.2 + 0.8));
    $isOnline = $lastSeen > 0 && (microtime(true) - $lastSeen) <= $timeout;

    $lastScan = Cache::get('rfid_last_scan_global');
    if ($lastScan && isset($lastScan['timestamp'])) {
        if ((microtime(true) - (float) $lastScan['timestamp']) > 30.0) {
            $lastScan = null;
        }
    }

    return response()->json([
        'device_online' => $isOnline,
        'last_seen_sec' => $lastSeen > 0 ? round(microtime(true) - $lastSeen, 2) : null,
        'server_time' => now()->toIso8601String(),
        'last_scan' => $lastScan,
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
        $nowFloat = microtime(true);
        $prevSeen = (float) Cache::get('rfid_device_online_at', 0);
        if ($prevSeen > 0 && ($nowFloat - $prevSeen) <= 60) {
            $delta = $nowFloat - $prevSeen;
            Cache::put('rfid_device_hb_interval', $delta, now()->addMinutes(5));
        }

        Cache::put('rfid_device_online_at', $nowFloat, now()->addMinutes(2));
        Cache::put('rfid_device_online', now()->toIso8601String(), now()->addMinutes(2));
        Cache::put("rfid_device_heartbeat:{$deviceId}", [
            'ip' => $request->input('ip', $request->ip()),
            'last_seen_at' => now()->toIso8601String(),
        ], now()->addMinutes(2));
    }

    return response()->json([
        'status' => 'online',
        'authenticated' => $authenticated,
        'server_time' => now()->toIso8601String(),
    ])
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
        ->header('Pragma', 'no-cache');
})->name('rfid.heartbeat');
