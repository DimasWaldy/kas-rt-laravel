<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rumah;
use App\Models\Tagihan;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RumahController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->query('search', ''));
        $bulan = (int) $request->query('bulan', now()->month);
        $tahun = (int) $request->query('tahun', now()->year);

        $rumahs = Rumah::with(['penanggungJawab', 'warga'])
            ->billingVisibleTo($request->user())
            ->when($request->boolean('perlu_pj'), fn ($query) => $query->needsBillingContact())
            ->withCount([
                'warga',
                'warga as kepala_keluarga_count' => fn ($query) => $query->whereHas(
                    'warga',
                    fn ($warga) => $warga->where('status_dalam_kk', 'kepala_keluarga')
                ),
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

        $tagihanByRumah = Tagihan::visibleTo($request->user())
            ->whereIn('rumah_id', Rumah::billingVisibleTo($request->user())->select('id'))
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->whereNotNull('rumah_id')
            ->get()
            ->groupBy('rumah_id')
            ->map(function ($items) {
                $status = match (true) {
                    $items->every(fn ($tagihan) => $tagihan->status === 'lunas') => 'lunas',
                    $items->contains(fn ($tagihan) => in_array($tagihan->status, ['pending_transfer', 'pending_offline'], true)) => 'pending',
                    default => 'belum_bayar',
                };

                return (object) [
                    'status' => $status,
                    'status_label' => match ($status) {
                        'lunas' => 'Semua Lunas',
                        'pending' => 'Ada Verifikasi',
                        default => 'Belum Lunas',
                    },
                    'total' => $items->sum('total'),
                    'count' => $items->count(),
                ];
            });

        $stats = [
            'total' => Rumah::billingVisibleTo($request->user())->count(),
            'aktif' => Rumah::billingVisibleTo($request->user())->where('status', 'aktif')->count(),
            'tanpa_pj' => Rumah::billingVisibleTo($request->user())->needsBillingContact()->count(),
            'belum_lunas' => Tagihan::visibleTo($request->user())->where('bulan', $bulan)
                ->whereIn('rumah_id', Rumah::billingVisibleTo($request->user())->select('id'))
                ->where('tahun', $tahun)
                ->whereNotNull('rumah_id')
                ->where('status', '!=', 'lunas')
                ->distinct('rumah_id')
                ->count('rumah_id'),
        ];

        return view('admin.rumah.index', compact('rumahs', 'tagihanByRumah', 'stats', 'bulan', 'tahun'));
    }

    public function show(Request $request, Rumah $rumah): View
    {
        abort_unless(Rumah::billingVisibleTo($request->user())->whereKey($rumah->id)->exists(), 404);

        $bulan = (int) $request->query('bulan', now()->month);
        $tahun = (int) $request->query('tahun', now()->year);

        $rumah->load([
            'penanggungJawab',
            'warga' => fn ($query) => $query->with('warga')->orderByDesc('is_penanggung_jawab_rumah')->orderBy('name'),
        ]);

        $tagihans = Tagihan::where('rumah_id', $rumah->id)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->orderBy('billing_group')
            ->get();

        $rumahOptions = Rumah::whereKeyNot($rumah->id)
            ->billingVisibleTo($request->user())
            ->orderBy('kode_rumah')
            ->get();

        return view('admin.rumah.show', compact('rumah', 'tagihans', 'rumahOptions', 'bulan', 'tahun'));
    }

    public function edit(Request $request, Rumah $rumah): View
    {
        abort_unless(Rumah::billingVisibleTo($request->user())->whereKey($rumah->id)->exists(), 404);

        $rumah->load(['warga' => fn ($query) => $query->orderBy('name')]);
        $eligiblePjs = $rumah->warga->filter(fn (User $user) => $user->isAktif() && (int) $user->rt_id === (int) $rumah->rt_id);

        return view('admin.rumah.edit', compact('rumah', 'eligiblePjs'));
    }

    public function update(Request $request, Rumah $rumah): RedirectResponse
    {
        abort_unless($request->user()->isAktif(), 403);
        abort_unless(Rumah::billingVisibleTo($request->user())->whereKey($rumah->id)->exists(), 404);

        $validated = $request->validate([
            'kode_rumah' => ['required', 'string', 'max:50', Rule::unique('rumahs', 'kode_rumah')->ignore($rumah->id)],
            'alamat' => ['nullable', 'string', 'max:500'],
            'rt' => ['nullable', 'regex:/^[0-9]{1,3}$/'],
            'rw' => ['nullable', 'regex:/^[0-9]{1,3}$/'],
            'status' => ['required', 'in:aktif,kosong,nonaktif'],
            'penanggung_jawab_id' => ['nullable', 'integer', 'exists:users,id'],
        ], [
            'rt.regex' => 'RT harus berisi angka saja, maksimal 3 digit.',
            'rw.regex' => 'RW harus berisi angka saja, maksimal 3 digit.',
        ]);

        DB::transaction(function () use ($request, $rumah, $validated) {
            $rumah = Rumah::billingVisibleTo($request->user())->lockForUpdate()->findOrFail($rumah->id);
            $oldPjId = $rumah->penanggung_jawab_id;
            $oldIssue = $rumah->status === 'aktif' ? $rumah->billingIssue() : null;
            $penanggungJawabId = $validated['penanggung_jawab_id'] ?? null;

            if ($validated['status'] !== 'aktif') {
                if (User::where('rumah_id', $rumah->id)->exists()) {
                    throw ValidationException::withMessages([
                        'status' => 'Pindahkan semua warga terlebih dahulu sebelum status rumah dibuat kosong atau nonaktif.',
                    ]);
                }

                $penanggungJawabId = null;
            }

            if ($penanggungJawabId && ! User::whereKey($penanggungJawabId)->where('rumah_id', $rumah->id)
                ->where('rt_id', $rumah->rt_id)->where('status_akun', 'aktif')->lockForUpdate()->first()) {
                throw ValidationException::withMessages([
                    'penanggung_jawab_id' => 'Penanggung jawab harus akun aktif yang tinggal di rumah dan RT ini.',
                ]);
            }

            $rumah->update([
                'kode_rumah' => strtoupper(trim($validated['kode_rumah'])),
                'alamat' => $validated['alamat'] ?? null,
                'rt' => $validated['rt'] ?? null,
                'rw' => $validated['rw'] ?? null,
                'status' => $validated['status'],
                'penanggung_jawab_id' => $penanggungJawabId,
            ]);

            User::where('rumah_id', $rumah->id)->update(['is_penanggung_jawab_rumah' => false]);

            if ($penanggungJawabId) {
                User::whereKey($penanggungJawabId)->update([
                    'is_penanggung_jawab_rumah' => true,
                    'rt_id' => $rumah->rt_id,
                ]);
            }
            if ((int) $oldPjId !== (int) $penanggungJawabId || ($oldIssue && $penanggungJawabId)) {
                AuditLog::create([
                    'user_id' => $request->user()->id, 'auditable_type' => Rumah::class, 'auditable_id' => $rumah->id,
                    'event' => 'pj_iuran_ditetapkan', 'old_values' => ['penanggung_jawab_id' => $oldPjId, 'billing_issue' => $oldIssue],
                    'new_values' => ['penanggung_jawab_id' => $penanggungJawabId],
                    'notes' => 'Penanggung jawab iuran diperbarui pengurus. Tagihan diproses melalui generate periode yang dipilih.',
                    'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(),
                ]);
            }
        }, 3);

        return redirect()->route('admin.rumah.show', $rumah)->with('success', 'Data rumah berhasil diperbarui. Periksa kesiapan PJ dan generate ulang tagihan periode yang belum dibuat.');
    }

    public function moveWarga(Request $request, Rumah $rumah, User $user): RedirectResponse
    {
        if (! Rumah::billingVisibleTo($request->user())->whereKey($rumah->id)->exists()
            || $user->role_name !== 'warga'
            || $user->rumah_id !== $rumah->id
            || ! User::visibleTo($request->user())->whereKey($user->id)->exists()) {
            abort(404);
        }

        $validated = $request->validate([
            'target_rumah_id' => [
                'nullable',
                'integer',
                Rule::exists('rumahs', 'id')
                    ->where('status', 'aktif')
                    ->where(fn ($query) => $request->user()->canAccessAllRts()
                        ? $query
                        : $query->where('rt_id', $request->user()->rt_id)),
            ],
            'make_penanggung_jawab' => ['nullable', 'boolean'],
        ], [
            'target_rumah_id.exists' => 'Warga hanya bisa dipindahkan ke rumah yang berstatus aktif.',
        ]);

        $targetRumahId = $validated['target_rumah_id'] ?? null;

        DB::transaction(function () use ($rumah, $user, $targetRumahId, $request) {
            $targetRumah = $targetRumahId ? Rumah::billingVisibleTo($request->user())->findOrFail($targetRumahId) : null;

            if ($rumah->penanggung_jawab_id === $user->id) {
                $rumah->update(['penanggung_jawab_id' => null]);
            }

            $user->update([
                'rumah_id' => $targetRumahId,
                'rt_id' => $targetRumah?->rt_id ?? $user->rt_id,
                'is_penanggung_jawab_rumah' => false,
            ]);

            if (! User::where('rumah_id', $rumah->id)->exists()) {
                $rumah->update([
                    'status' => 'kosong',
                    'penanggung_jawab_id' => null,
                ]);
            }

            if ($targetRumahId && $request->boolean('make_penanggung_jawab')) {
                User::where('rumah_id', $targetRumahId)
                    ->whereKeyNot($user->id)
                    ->update(['is_penanggung_jawab_rumah' => false]);

                User::whereKey($user->id)->update(['is_penanggung_jawab_rumah' => true]);
                Rumah::whereKey($targetRumahId)->update(['penanggung_jawab_id' => $user->id]);
            }
        });

        return redirect()->route('admin.rumah.show', $rumah)->with('success', 'Warga berhasil dipindahkan.');
    }
}
