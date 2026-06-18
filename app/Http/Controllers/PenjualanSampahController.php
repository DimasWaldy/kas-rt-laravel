<?php

namespace App\Http\Controllers;

use App\Models\JenisSampah;
use App\Models\PenjualanSampah;
use App\Models\Rw;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PenjualanSampahController extends Controller
{
    public function index(Request $request)
    {
        $rwId = $this->resolveRwId($request->user());

        $penjualans = PenjualanSampah::with(['jenisSampah', 'petugas'])
            ->where('rw_id', $rwId)
            ->latest('tanggal_jual')
            ->latest()
            ->paginate(12);

        $totalKas = (int) PenjualanSampah::where('rw_id', $rwId)->sum('total');
        $totalBulanIni = (int) PenjualanSampah::where('rw_id', $rwId)
            ->whereBetween('tanggal_jual', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->sum('total');

        return view('bank_sampah.penjualan.index', compact('penjualans', 'totalKas', 'totalBulanIni'));
    }

    public function create(Request $request)
    {
        $rwId = $this->resolveRwId($request->user());
        $jenisSampah = JenisSampah::where('rw_id', $rwId)
            ->where('is_active', true)
            ->orderBy('nama')
            ->get();

        return view('bank_sampah.penjualan.create', compact('jenisSampah'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $rwId = $this->resolveRwId($user);

        $validated = $request->validate([
            'jenis_sampah_id' => [
                'required',
                'integer',
                Rule::exists('jenis_sampahs', 'id')->where('rw_id', $rwId)->where('is_active', true),
            ],
            'tanggal_jual' => ['required', 'date'],
            'berat_total' => ['required', 'numeric', 'min:0.1'],
            'harga_jual' => ['required', 'integer', 'min:1'],
            'nama_pengepul' => ['nullable', 'string', 'max:255'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $total = (int) round((float) $validated['berat_total'] * (int) $validated['harga_jual']);

        PenjualanSampah::create([
            ...$validated,
            'rw_id' => $rwId,
            'petugas_id' => $user->id,
            'total' => $total,
        ]);

        return redirect()->route('penjualan-sampah.index')
            ->with('success', 'Penjualan sampah ke pengepul berhasil dicatat.');
    }

    private function resolveRwId(User $user): int
    {
        $rwId = $user->rt()->value('rw_id');

        if (! $rwId && ($this->isRwLevelBankSampahOperator($user) || $user->isGlobalOperator())) {
            $rwId = Rw::where('is_active', true)->orderBy('id')->value('id');
        }

        abort_unless($rwId, 403, 'Akun belum terhubung ke wilayah.');

        return (int) $rwId;
    }

    private function isRwLevelBankSampahOperator(User $user): bool
    {
        return $user->isRwOfficial() || $user->role_name === 'petugas_bank_sampah';
    }
}
