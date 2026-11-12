<?php

namespace App\Http\Controllers;

use App\Models\Kegiatan;
use App\Models\RfidCard;
use App\Models\RfidSession;
use App\Support\RfidAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KegiatanRfidController extends Controller
{
    public function show(Request $request, Kegiatan $kegiatan)
    {
        abort_unless(RfidAccess::canManage($request->user(), $kegiatan), 403);

        $search = $request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? '';
        $users = RfidAccess::participants($kegiatan)
            ->when($search !== '', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
            ->select('users.id', 'users.name')
            ->selectSub(RfidCard::select('id')->whereColumn('user_id', 'users.id'), 'card_id')
            ->orderBy('name')->orderBy('id')->paginate(20)->withQueryString();

        return response()->view('kegiatan.rfid', compact('kegiatan', 'users', 'search'))
            ->header('Cache-Control', 'no-store');
    }

    public function open(Request $request, Kegiatan $kegiatan)
    {
        $token = '';
        $this->mutate($request, $kegiatan, function (Kegiatan $locked) use ($request, &$token) {
            $this->assertNotFinished($locked);
            $token = RfidAccess::openSession('kegiatan', 'kegiatan:'.$locked->id, $request->user(), [
                'kegiatan_id' => $locked->id,
                'rw_id' => null,
                'rt_id' => null,
            ]);
        });

        // One-time display: the database retains only the hash, never the bearer token.
        return redirect()->route('kegiatan.rfid.show', $kegiatan)
            ->with('rfid_token', $token)->with('success', 'Sesi dibuka selama 6 jam. Token lama tidak berlaku.');
    }

    public function close(Request $request, Kegiatan $kegiatan)
    {
        $this->mutate($request, $kegiatan, fn (Kegiatan $locked) => RfidAccess::closeSession('kegiatan:'.$locked->id));

        return back()->with('success', 'Sesi ditutup. Alat tidak dapat mencatat tap lagi.');
    }

    public function enroll(Request $request, Kegiatan $kegiatan)
    {
        $data = $request->validate(['user_id' => ['required', 'integer']]);
        $this->mutate($request, $kegiatan, function (Kegiatan $locked) use ($data) {
            $this->assertNotFinished($locked);
            RfidAccess::participants($locked)->whereKey($data['user_id'])->firstOrFail();
            RfidAccess::startEnrollment('kegiatan:'.$locked->id, (int) $data['user_id']);
        });

        return back()->with('success', 'Mode pendaftaran aktif 2 menit. Periksa identitas warga, lalu tap kartunya satu kali.');
    }

    public function cancelEnrollment(Request $request, Kegiatan $kegiatan)
    {
        $this->mutate($request, $kegiatan, fn (Kegiatan $locked) => RfidAccess::cancelEnrollment('kegiatan:'.$locked->id));

        return back()->with('success', 'Mode pendaftaran dibatalkan.');
    }

    public function revoke(Request $request, Kegiatan $kegiatan)
    {
        $data = $request->validate(['user_id' => ['required', 'integer']]);
        $this->mutate($request, $kegiatan, function (Kegiatan $locked) use ($data) {
            $user = RfidAccess::participants($locked)->whereKey($data['user_id'])->lockForUpdate()->firstOrFail();
            RfidAccess::revokeCard($user->id);
        });

        return back()->with('success', 'Kartu dicabut untuk seluruh kegiatan. Riwayat hadir tetap tersimpan.');
    }

    public function status(Request $request, Kegiatan $kegiatan)
    {
        abort_unless(RfidAccess::canManage($request->user(), $kegiatan), 403);
        $session = RfidSession::where('kegiatan_id', $kegiatan->id)->first();
        $present = $kegiatan->hadirs()->whereNotNull('rfid_hadir_at');

        return response()->json([
            'open' => $session && $session->expires_at->isFuture()
                && ! in_array($kegiatan->effective_status, ['selesai', 'dibatalkan'], true),
            'expires_at' => $session?->expires_at?->format('d/m/Y H:i:s'),
            'last_seen_at' => $session?->last_seen_at?->format('d/m/Y H:i:s'),
            'enrollment' => $session?->enrollment_user_id ? [
                'name' => RfidAccess::participants($kegiatan)->whereKey($session->enrollment_user_id)->value('name') ?? 'Warga tidak tersedia',
                'expired' => ! $session->enrollment_expires_at?->isFuture(),
            ] : null,
            'count' => (clone $present)->count(),
            'attendees' => $present->with('user:id,name')->orderByDesc('rfid_hadir_at')->limit(100)->get()
                ->map(fn ($hadir) => [
                    'id' => $hadir->id, 'name' => $hadir->user->name,
                    'time' => $hadir->rfid_hadir_at->format('d/m/Y H:i:s'),
                ]),
        ])->header('Cache-Control', 'no-store');
    }

    private function mutate(Request $request, Kegiatan $kegiatan, callable $callback): void
    {
        DB::transaction(function () use ($request, $kegiatan, $callback) {
            $locked = Kegiatan::whereKey($kegiatan->id)->lockForUpdate()->firstOrFail();
            abort_unless(RfidAccess::canManage($request->user(), $locked), 403);
            $callback($locked);
        });
    }

    private function assertNotFinished(Kegiatan $kegiatan): void
    {
        if (in_array($kegiatan->effective_status, ['selesai', 'dibatalkan'], true)) {
            throw ValidationException::withMessages(['rfid' => 'Kegiatan selesai atau dibatalkan tidak menerima sesi RFID.']);
        }
    }
}
