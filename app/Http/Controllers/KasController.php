<?php

namespace App\Http\Controllers;

use App\Models\Kas;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KasController extends Controller
{
    public function index()
    {
        $leaderboard = User::withSum('kas', 'jumlah')
            ->orderByDesc('kas_sum_jumlah')
            ->get();

        return view('kas.index', compact('leaderboard'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'jumlah' => ['required', 'integer', 'min:1000'],
        ]);

        Kas::create([
            'user_id' => Auth::id(),
            'jumlah' => $validated['jumlah'],
        ]);

        return redirect()->back()->with('success', 'Iuran berhasil ditambahkan.');
    }
}
