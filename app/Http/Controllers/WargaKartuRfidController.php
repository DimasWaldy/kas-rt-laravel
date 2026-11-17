<?php

namespace App\Http\Controllers;

use App\Models\RfidCard;
use App\Models\RfidSession;
use Illuminate\Http\Request;

class WargaKartuRfidController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $card = RfidCard::where('user_id', $user->id)
            ->with(['verifiedRt:id,name'])
            ->first();

        $activeSession = null;
        if ($user->rt_id) {
            $activeSession = RfidSession::whereIn('scope_key', [
                'warga:rt:'.$user->rt_id,
                'warga:global',
            ])
                ->where('expires_at', '>', now())
                ->first();
        }

        return view('warga.kartu_rfid', compact('user', 'card', 'activeSession'));
    }
}
