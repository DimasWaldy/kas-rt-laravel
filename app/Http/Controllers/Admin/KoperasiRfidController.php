<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KoperasiMember;
use App\Models\RfidCard;
use App\Models\RfidSession;
use App\Support\RfidAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KoperasiRfidController extends Controller
{
    public function show(Request $request)
    {
        $actor = $request->user();
        abort_unless(RfidAccess::canManageKoperasi($actor), 403);

        $search = $request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? '';
        $users = RfidAccess::koperasiParticipantsForActor($actor)
            ->when($search !== '', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
            ->select('users.id', 'users.name')
            ->selectSub(RfidCard::select('id')->whereColumn('user_id', 'users.id'), 'card_id')
            ->selectSub(RfidCard::select('koperasi_verified_at')->whereColumn('user_id', 'users.id'), 'koperasi_verified_at')
            ->selectSub(KoperasiMember::select('status')->whereColumn('user_id', 'users.id'), 'koperasi_status')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return response()->view('koperasi.admin.rfid', compact('users', 'search'))
            ->header('Cache-Control', 'no-store');
    }

    public function open(Request $request)
    {
        $token = '';
        $this->mutate($request, function (array $scope) use ($request, &$token) {
            $token = RfidAccess::openSession('koperasi', $scope['scope_key'], $request->user(), [
                'kegiatan_id' => null,
                'rw_id' => $scope['rw_id'],
                'rt_id' => $scope['rt_id'],
            ]);
        });

        return redirect()->route('admin.koperasi.rfid.show')
            ->with('rfid_token', $token)
            ->with('success', 'Sesi RFID koperasi dibuka selama 6 jam. Token lama tidak berlaku.');
    }

    public function close(Request $request)
    {
        $this->mutate($request, fn (array $scope) => RfidAccess::closeSession($scope['scope_key']));

        return back()->with('success', 'Sesi RFID koperasi ditutup. Alat tidak dapat mencatat tap lagi.');
    }

    public function enroll(Request $request)
    {
        $data = $request->validate(['user_id' => ['required', 'integer']]);
        $this->mutate($request, function (array $scope) use ($request, $data) {
            RfidAccess::koperasiParticipantsForActor($request->user())->whereKey($data['user_id'])->firstOrFail();
            RfidAccess::startEnrollment($scope['scope_key'], (int) $data['user_id']);
        });

        return back()->with('success', 'Mode tap kartu koperasi aktif 2 menit. Pastikan identitas warga benar, lalu tap kartunya satu kali.');
    }

    public function cancelEnrollment(Request $request)
    {
        $this->mutate($request, fn (array $scope) => RfidAccess::cancelEnrollment($scope['scope_key']));

        return back()->with('success', 'Mode pendaftaran kartu dibatalkan.');
    }

    public function revoke(Request $request)
    {
        $data = $request->validate(['user_id' => ['required', 'integer']]);
        $this->mutate($request, function () use ($request, $data) {
            $user = RfidAccess::koperasiParticipantsForActor($request->user())
                ->whereKey($data['user_id'])
                ->lockForUpdate()
                ->firstOrFail();
            RfidAccess::revokeCard($user->id);
        });

        return back()->with('success', 'Kartu warga dicabut. Jika belum mendaftar koperasi, warga wajib tap kartu baru.');
    }

    public function status(Request $request)
    {
        $actor = $request->user();
        abort_unless(RfidAccess::canManageKoperasi($actor), 403);

        $scope = RfidAccess::resolveKoperasiScope($actor);
        $session = RfidSession::where('scope_key', $scope['scope_key'])->first();
        $scopedUserIds = RfidAccess::koperasiParticipantsForActor($actor)->select('users.id');
        $verified = RfidCard::query()
            ->whereIn('user_id', $scopedUserIds)
            ->whereNotNull('koperasi_verified_at');

        return response()->json([
            'open' => $session && $session->expires_at->isFuture(),
            'expires_at' => $session?->expires_at?->format('d/m/Y H:i:s'),
            'last_seen_at' => $session?->last_seen_at?->format('d/m/Y H:i:s'),
            'enrollment' => $session?->enrollment_user_id ? [
                'name' => RfidAccess::koperasiParticipantsForActor($actor)->whereKey($session->enrollment_user_id)->value('name') ?? 'Warga tidak tersedia',
                'expired' => ! $session->enrollment_expires_at?->isFuture(),
            ] : null,
            'count' => (clone $verified)->count(),
            'attendees' => $verified->with('user:id,name')->orderByDesc('koperasi_verified_at')->limit(100)->get()
                ->map(fn ($card) => [
                    'id' => $card->id,
                    'name' => $card->user?->name ?? 'Warga',
                    'time' => $card->koperasi_verified_at->format('d/m/Y H:i:s'),
                ]),
        ])->header('Cache-Control', 'no-store');
    }

    private function mutate(Request $request, callable $callback): void
    {
        DB::transaction(function () use ($request, $callback) {
            $actor = $request->user();
            abort_unless(RfidAccess::canManageKoperasi($actor), 403);
            $scope = RfidAccess::resolveKoperasiScope($actor);
            $callback($scope);
        });
    }
}
