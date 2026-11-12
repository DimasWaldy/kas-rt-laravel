<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RfidCard;
use App\Models\RfidSession;
use App\Support\RfidAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WargaRfidController extends Controller
{
    public function show(Request $request)
    {
        $actor = $request->user();
        abort_unless(RfidAccess::canManageWargaKtp($actor), 403);

        $search = $request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? '';
        $users = RfidAccess::wargaKtpParticipantsForActor($actor)
            ->with(['rt:id,name', 'rfidCard.verifiedRt:id,name'])
            ->when($search !== '', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
            ->select('users.id', 'users.name', 'users.rt_id')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return response()->view('admin.warga.rfid', compact('users', 'search'))
            ->header('Cache-Control', 'no-store');
    }

    public function open(Request $request)
    {
        $token = '';
        $this->mutate($request, function (array $scope) use ($request, &$token) {
            $token = RfidAccess::openSession('warga', $scope['scope_key'], $request->user(), [
                'kegiatan_id' => null,
                'rw_id' => $scope['rw_id'],
                'rt_id' => $scope['rt_id'],
            ]);
        });

        return redirect()->route('admin.warga.rfid.show')
            ->with('rfid_token', $token)
            ->with('success', 'Sesi verifikasi KTP RFID dibuka selama 6 jam. Token lama tidak berlaku.');
    }

    public function close(Request $request)
    {
        $this->mutate($request, fn (array $scope) => RfidAccess::closeSession($scope['scope_key']));

        return back()->with('success', 'Sesi verifikasi KTP RFID ditutup.');
    }

    public function enroll(Request $request)
    {
        $data = $request->validate(['user_id' => ['required', 'integer']]);
        $this->mutate($request, function (array $scope) use ($request, $data) {
            RfidAccess::wargaKtpParticipantsForActor($request->user())->whereKey($data['user_id'])->firstOrFail();
            RfidAccess::startEnrollment($scope['scope_key'], (int) $data['user_id']);
        });

        return back()->with('success', 'Mode verifikasi KTP aktif 2 menit. Minta warga tap KTP ke reader ESP32 satu kali.');
    }

    public function cancelEnrollment(Request $request)
    {
        $this->mutate($request, fn (array $scope) => RfidAccess::cancelEnrollment($scope['scope_key']));

        return back()->with('success', 'Mode tap verifikasi KTP dibatalkan.');
    }

    public function revoke(Request $request)
    {
        $data = $request->validate(['user_id' => ['required', 'integer']]);
        $this->mutate($request, function () use ($request, $data) {
            $user = RfidAccess::wargaKtpParticipantsForActor($request->user())
                ->whereKey($data['user_id'])
                ->lockForUpdate()
                ->firstOrFail();
            RfidAccess::revokeCard($user->id);
        });

        return back()->with('success', 'KTP / kartu RFID warga dicabut dari RT dan sistem.');
    }

    public function status(Request $request)
    {
        $actor = $request->user();
        abort_unless(RfidAccess::canManageWargaKtp($actor), 403);

        $scope = RfidAccess::resolveWargaKtpScope($actor);
        $session = RfidSession::where('scope_key', $scope['scope_key'])->first();
        $scopedUserIds = RfidAccess::wargaKtpParticipantsForActor($actor)->select('users.id');
        $verified = RfidCard::query()
            ->whereIn('user_id', $scopedUserIds)
            ->whereNotNull('ktp_verified_at');

        return response()->json([
            'open' => $session && $session->expires_at->isFuture(),
            'expires_at' => $session?->expires_at?->format('d/m/Y H:i:s'),
            'last_seen_at' => $session?->last_seen_at?->format('d/m/Y H:i:s'),
            'enrollment' => $session?->enrollment_user_id ? [
                'name' => RfidAccess::wargaKtpParticipantsForActor($actor)->whereKey($session->enrollment_user_id)->value('name') ?? 'Warga tidak tersedia',
                'expired' => ! $session->enrollment_expires_at?->isFuture(),
            ] : null,
            'count' => (clone $verified)->count(),
            'attendees' => $verified->with(['user:id,name', 'verifiedRt:id,name'])->orderByDesc('ktp_verified_at')->limit(100)->get()
                ->map(fn ($card) => [
                    'id' => $card->id,
                    'name' => ($card->user?->name ?? 'Warga').($card->verifiedRt?->name ? ' ('.$card->verifiedRt->name.')' : ''),
                    'time' => $card->ktp_verified_at->format('d/m/Y H:i:s'),
                ]),
        ])->header('Cache-Control', 'no-store');
    }

    private function mutate(Request $request, callable $callback): void
    {
        DB::transaction(function () use ($request, $callback) {
            $actor = $request->user();
            abort_unless(RfidAccess::canManageWargaKtp($actor), 403);
            $scope = RfidAccess::resolveWargaKtpScope($actor);
            $callback($scope);
        });
    }
}
