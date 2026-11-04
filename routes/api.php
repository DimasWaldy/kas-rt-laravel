<?php

use App\Http\Controllers\RfidTapController;
use Illuminate\Support\Facades\Route;

// Stateless device endpoint: bearer authentication in controller, not a browser/CSRF route.
Route::post('/rfid/tap', RfidTapController::class)->middleware('throttle:120,1')->name('rfid.tap');
