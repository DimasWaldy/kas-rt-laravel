<?php

use App\Http\Controllers\RfidTapController;
use App\Support\RfidAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Stateless device endpoints: bearer or device-key authentication in controller
Route::post('/rfid/tap', RfidTapController::class)->middleware('throttle:120,1')->name('rfid.tap');
Route::post('/rfid/scan', RfidTapController::class)->middleware('throttle:120,1')->name('rfid.scan');
Route::get('/rfid/device-status', function () {
    $snapshot = RfidAccess::deviceStatusSnapshot(allowGlobalFallback: true, includeDeviceMeta: true);

    return response()->json([
        'device_online' => $snapshot['device_online'],
        'last_seen_sec' => $snapshot['last_seen_sec'],
        'last_device' => $snapshot['last_device'],
        'server_time' => now()->toIso8601String(),
        'last_scan' => $snapshot['last_scan'],
    ])
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
        ->header('Pragma', 'no-cache');
})->name('rfid.device-status');

Route::match(['get', 'post'], '/rfid/heartbeat', function (Request $request) {
    $deviceId = $request->input('device_id', 'esp32-default');
    $token = RfidAccess::extractDeviceToken($request);
    $authenticated = RfidAccess::isValidDeviceKey($token);

    if ($authenticated) {
        RfidAccess::touchDeviceHeartbeat(
            is_string($deviceId) ? $deviceId : 'esp32-default',
            is_string($request->input('ip')) ? $request->input('ip') : $request->ip(),
        );
    }

    return response()->json([
        'status' => 'online',
        'authenticated' => $authenticated,
        'device_id' => $deviceId,
        'server_time' => now()->toIso8601String(),
    ])
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
        ->header('Pragma', 'no-cache');
})->name('rfid.heartbeat');
