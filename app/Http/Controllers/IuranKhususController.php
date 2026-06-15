<?php

namespace App\Http\Controllers;

use App\Models\IuranKhusus;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class IuranKhususController extends Controller
{
    public function index()
    {
        $rtId = $this->resolveRtId(Auth::user());

        $iuranKhusus = IuranKhusus::with(['rt', 'creator'])
            ->withCount([
                'tagihans as total_lunas' => fn (Builder $query) => $query->where('status', 'lunas'),
                'tagihans as total_dikecualikan' => fn (Builder $query) => $query->whereNotNull('dikecualikan_at'),
            ])
            ->where('rt_id', $rtId)
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('iuran_khusus.index', compact('iuranKhusus'));
    }

    public function create()
    {
        $rtId = $this->resolveRtId(Auth::user());
        $warga = $this->targetWarga($rtId)->get();
        $jumlahWarga = $warga->count();

        return view('iuran_khusus.create', compact('warga', 'jumlahWarga'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'jenis' => ['required', 'in:kematian,pembangunan,sosial,kegiatan,lainnya'],
            'judul' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'nominal_per_warga' => ['required', 'integer', 'min:1000'],
            'tanggal_kejadian' => ['nullable', 'date'],
        ]);

        $rtId = $this->resolveRtId($request->user());

        $iuranKhusus = DB::transaction(function () use ($validated, $rtId) {
            $iuranKhusus = IuranKhusus::create([
                ...$validated,
                'rt_id' => $rtId,
                'created_by' => Auth::id(),
                'billing_group' => 'sementara_' . str()->uuid(),
            ]);

            $iuranKhusus->update([
                'billing_group' => 'insidental_' . $iuranKhusus->id,
            ]);

            $warga = $this->targetWarga($rtId)->get();

            foreach ($warga as $target) {
                Tagihan::create([
                    'user_id' => $target->id,
                    'rumah_id' => $target->rumah_id,
                    'rt_id' => $rtId,
                    'judul' => $validated['judul'],
                    'total' => $validated['nominal_per_warga'],
                    'bulan' => now()->month,
                    'tahun' => now()->year,
                    'billing_group' => $iuranKhusus->billing_group,
                    'status' => 'belum_bayar',
                ]);
            }

            $iuranKhusus->update([
                'total_tagihan' => $warga->count(),
            ]);

            return $iuranKhusus;
        });

        return redirect()->route('iuran-khusus.show', $iuranKhusus)
            ->with('success', 'Iuran khusus berhasil dibuat dan tagihan warga telah digenerate.');
    }

    public function show(IuranKhusus $iuranKhusus)
    {
        $this->authorizeIuran($iuranKhusus, Auth::user());
        $iuranKhusus->load(['rt', 'creator', 'tagihans.user']);

        $tagihans = $iuranKhusus->tagihans;
        $terkumpul = (int) $tagihans->where('status', 'lunas')->sum('total');
        $dikecualikan = $tagihans->whereNotNull('dikecualikan_at')->count();
        $belumBayar = $tagihans
            ->where('status', 'belum_bayar')
            ->whereNull('dikecualikan_at')
            ->count();
        $lunas = $tagihans->where('status', 'lunas')->count();

        if ($iuranKhusus->total_terkumpul !== $terkumpul) {
            $iuranKhusus->update(['total_terkumpul' => $terkumpul]);
        }

        return view('iuran_khusus.show', compact(
            'iuranKhusus',
            'terkumpul',
            'dikecualikan',
            'belumBayar',
            'lunas'
        ));
    }

    public function kecualikan(Request $request, Tagihan $tagihan)
    {
        $validated = $request->validate([
            'alasan_dikecualikan' => ['required', 'string', 'min:5', 'max:255'],
        ]);

        $this->authorizeTagihan($tagihan, $request->user());
        abort_unless($tagihan->isInsidental(), 403);
        abort_if($tagihan->status === 'lunas', 403);

        $tagihan->update([
            'dikecualikan_at' => now(),
            'dikecualikan_oleh' => Auth::id(),
            'alasan_dikecualikan' => $validated['alasan_dikecualikan'],
        ]);

        return back()->with('success', 'Warga berhasil dikecualikan dari iuran khusus.');
    }

    public function batalKecualikan(Tagihan $tagihan)
    {
        $this->authorizeTagihan($tagihan, Auth::user());
        abort_unless($tagihan->isInsidental() && $tagihan->isDikecualikan(), 403);

        $tagihan->update([
            'dikecualikan_at' => null,
            'dikecualikan_oleh' => null,
            'alasan_dikecualikan' => null,
        ]);

        return back()->with('success', 'Pengecualian warga berhasil dibatalkan.');
    }

    private function targetWarga(int $rtId): Builder
    {
        return User::query()
            ->where('rt_id', $rtId)
            ->whereHas('role', fn (Builder $query) => $query->where('name', 'warga'))
            ->where(function (Builder $query) {
                $query->where('is_penanggung_jawab_rumah', true)
                    ->orWhere('is_kepala_keluarga', true);
            })
            ->orderBy('name');
    }

    private function resolveRtId(User $user): int
    {
        abort_unless($user->rt_id, 403, 'Akun belum terhubung ke RT.');

        return (int) $user->rt_id;
    }

    private function authorizeIuran(IuranKhusus $iuranKhusus, User $user): void
    {
        abort_unless($iuranKhusus->rt_id === $this->resolveRtId($user), 403);
    }

    private function authorizeTagihan(Tagihan $tagihan, User $user): void
    {
        abort_unless($tagihan->rt_id === $this->resolveRtId($user), 403);
    }
}
