<?php

namespace App\Http\Controllers;

use App\Models\Pemilihan;
use App\Models\PemilihanSuara;
use App\Models\PemilihanVoter;
use App\Models\RfidCard;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EvotingWargaController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $userRtId = $user->rt_id;
        $userRwId = $user->rt?->rw_id;

        $pemilihans = Pemilihan::with(['kandidats', 'voters', 'rt', 'rw'])
            ->where(function ($query) use ($userRtId, $userRwId) {
                $query->where(function ($q) use ($userRtId) {
                    $q->where('scope_type', 'rt');
                    if ($userRtId) {
                        $q->where('rt_id', $userRtId);
                    }
                })->orWhere(function ($q) use ($userRwId) {
                    $q->where('scope_type', 'rw');
                    if ($userRwId) {
                        $q->where('rw_id', $userRwId);
                    }
                });
            })
            ->latest('start_date')
            ->get();

        $hasVerifiedKtp = RfidCard::where('user_id', $user->id)
            ->where(fn ($query) => $query->whereNotNull('ktp_verified_at')->orWhereNotNull('koperasi_verified_at'))
            ->exists();

        return view('evoting.index', compact('pemilihans', 'hasVerifiedKtp'));
    }

    public function show(Pemilihan $pemilihan)
    {
        $user = Auth::user();
        $pemilihan->load(['kandidats.suaras', 'rt', 'rw']);

        $eligibility = $pemilihan->checkUserEligibility($user);
        $hasVoted = $pemilihan->hasUserVoted($user->id);
        $quickCount = $pemilihan->quickCountResults();
        $totalSuara = $pemilihan->suaras()->count();

        $totalDpt = 0;
        if ($pemilihan->scope_type === 'rt' && $pemilihan->rt_id) {
            $totalDpt = User::where('rt_id', $pemilihan->rt_id)->where('status_akun', 'aktif')->count();
        } elseif ($pemilihan->scope_type === 'rw' && $pemilihan->rw_id) {
            $totalDpt = User::whereHas('rt', fn ($q) => $q->where('rw_id', $pemilihan->rw_id))->where('status_akun', 'aktif')->count();
        }

        $partisipasi = $totalDpt > 0 ? round(($pemilihan->voters()->count() / $totalDpt) * 100, 1) : 0;

        return view('evoting.show', compact('pemilihan', 'eligibility', 'hasVoted', 'quickCount', 'totalSuara', 'totalDpt', 'partisipasi'));
    }

    public function vote(Request $request, Pemilihan $pemilihan)
    {
        $user = Auth::user();

        $eligibility = $pemilihan->checkUserEligibility($user);
        if (! $eligibility['eligible']) {
            return back()->with('error', $eligibility['reason'] ?? 'Anda tidak memenuhi syarat untuk memberikan suara.');
        }

        $validated = $request->validate([
            'kandidat_id' => 'required|exists:pemilihan_kandidats,id',
        ]);

        $kandidat = $pemilihan->kandidats()->where('id', $validated['kandidat_id'])->first();
        if (! $kandidat) {
            return back()->with('error', 'Kandidat tidak valid untuk pemilihan ini.');
        }

        DB::transaction(function () use ($pemilihan, $user, $kandidat) {
            PemilihanVoter::create([
                'pemilihan_id' => $pemilihan->id,
                'user_id' => $user->id,
                'voted_at' => now(),
            ]);

            PemilihanSuara::create([
                'pemilihan_id' => $pemilihan->id,
                'kandidat_id' => $kandidat->id,
                'cast_at' => now(),
            ]);
        });

        return redirect()->route('evoting.show', $pemilihan->slug)
            ->with('success', 'Suara Anda berhasil dicatat secara rahasia dan sah! Terima kasih atas partisipasi aktif Anda dalam pesta demokrasi warga.');
    }

    public function quickCount(Pemilihan $pemilihan)
    {
        $quickCount = $pemilihan->quickCountResults();
        $totalSuara = $pemilihan->suaras()->count();

        return response()->json([
            'total_suara' => $totalSuara,
            'kandidats' => $quickCount,
            'is_finished' => $pemilihan->isFinished(),
        ]);
    }
}
