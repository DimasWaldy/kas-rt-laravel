<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rumah;
use App\Models\Tagihan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RumahController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->query('search', ''));
        $bulan = (int) $request->query('bulan', now()->month);
        $tahun = (int) $request->query('tahun', now()->year);

        $rumahs = Rumah::with(['penanggungJawab', 'warga'])
            ->withCount([
                'warga',
                'warga as kepala_keluarga_count' => fn($query) => $query->where('is_kepala_keluarga', true),
            ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('kode_rumah', 'like', "%{$search}%")
                        ->orWhere('alamat', 'like', "%{$search}%")
                        ->orWhereHas('penanggungJawab', fn($userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('kode_rumah')
            ->paginate(15)
            ->withQueryString();

        $tagihanByRumah = Tagihan::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->whereNotNull('rumah_id')
            ->get()
            ->keyBy('rumah_id');

        $stats = [
            'total' => Rumah::count(),
            'aktif' => Rumah::where('status', 'aktif')->count(),
            'tanpa_pj' => Rumah::whereNull('penanggung_jawab_id')->count(),
            'belum_lunas' => Tagihan::where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->whereNotNull('rumah_id')
                ->where('status', '!=', 'lunas')
                ->count(),
        ];

        return view('admin.rumah.index', compact('rumahs', 'tagihanByRumah', 'stats', 'bulan', 'tahun'));
    }
}
