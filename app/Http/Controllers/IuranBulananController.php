<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\IuranBulanan;
use App\Models\Tagihan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IuranBulananController extends Controller
{
    public function index()
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }

        $items = IuranBulanan::orderByDesc('tahun')
            ->orderByDesc('bulan')
            ->orderBy('nama')
            ->get();

        return view('iuran_bulanan.index', compact('items'));
    }

    public function create()
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }

        return view('iuran_bulanan.create');
    }

    public function store(Request $request)
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:500'],
            'jumlah' => ['required', 'numeric', 'min:0'],
            'bulan' => ['required', 'integer', 'between:1,12'],
            'tahun' => ['required', 'integer', 'min:2024'],
        ]);

        $iuran = IuranBulanan::create($data);

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => IuranBulanan::class,
            'auditable_id' => $iuran->id,
            'event' => 'iuran_created',
            'new_values' => $iuran->toArray(),
            'notes' => 'Iuran bulanan baru ditambahkan untuk bulan ' . $iuran->bulan . ' tahun ' . $iuran->tahun,
        ]);

        Tagihan::generateForMonth($data['bulan'], $data['tahun']);

        return redirect()->route('iuran-bulanan.index')->with('success', 'Iuran bulanan berhasil ditambahkan dan tagihan dibuat untuk warga.');
    }
}
