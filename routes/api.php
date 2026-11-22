<?php

use App\Http\Controllers\RfidTapController;
use Illuminate\Support\Facades\Route;

// Stateless device endpoints: bearer or device-key authentication in controller
Route::post('/rfid/tap', RfidTapController::class)->middleware('throttle:120,1')->name('rfid.tap');
Route::post('/rfid/scan', RfidTapController::class)->middleware('throttle:120,1')->name('rfid.scan');
Route::match(['get', 'post'], '/rfid/heartbeat', function () {
    return response()->json([
        'status' => 'online',
        'server_time' => now()->toIso8601String(),
    ]);
})->name('rfid.heartbeat');
