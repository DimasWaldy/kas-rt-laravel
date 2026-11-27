<?php

namespace App\Http\Controllers;

use App\Models\RfidCard;
use App\Models\RfidSession;
use App\Models\User;
use App\Support\RfidAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GerbangRfidController extends Controller
{
    public function show(Request $request)
    {
        $actor = $request->user();
        abort_unless(RfidAccess::canManageGerbang($actor), 403);

        $scope = RfidAccess::resolveGerbangScope($actor);
        $session = RfidSession::where('scope_key', $scope['scope_key'])->first();
        $verifiedKtpCount = RfidCard::query()
            ->whereNotNull('ktp_verified_at')
            ->whereIn('user_id', RfidAccess::scopedUsersByAttributes($scope['rt_id'], $scope['rw_id'])->select('users.id'))
            ->count();

        return response()->view('keamanan.gerbang', [
            'session' => $session,
            'verifiedKtpCount' => $verifiedKtpCount,
            'stats' => $this->todayStats($actor),
        ])->header('Cache-Control', 'no-store');
    }

    public function open(Request $request)
    {
        $data = $request->validate([
            'gate_mode' => ['nullable', 'string', Rule::in(['auto', 'masuk', 'keluar'])],
        ]);
        $mode = $data['gate_mode'] ?? 'auto';
        $token = '';

        $this->mutate($request, function (array $scope) use ($request, $mode, &$token) {
            $token = RfidAccess::openSession(
                'gerbang',
                $scope['scope_key'],
                $request->user(),
                [
                    'kegiatan_id' => null,
                    'rw_id' => $scope['rw_id'],
                    'rt_id' => $scope['rt_id'],
                    'gate_mode' => $mode,
                ],
                now()->addDays(7),
            );
        });

        return redirect()->route('keamanan.gerbang.show')
            ->with('rfid_token', $token)
            ->with('success', 'Mode Gerbang RFID aktif selama 7 hari. Warga dengan e-KTP terdaftar bisa langsung tap untuk buka palang servo.');
    }

    public function setMode(Request $request)
    {
        $data = $request->validate([
            'gate_mode' => ['required', 'string', Rule::in(['auto', 'masuk', 'keluar'])],
        ]);

        $this->mutate($request, function (array $scope) use ($request, $data) {
            $session = RfidSession::where('scope_key', $scope['scope_key'])
                ->where('expires_at', '>', now())
                ->first();

            if ($session) {
                $session->update(['gate_mode' => $data['gate_mode']]);

                return;
            }

            RfidAccess::openSession(
                'gerbang',
                $scope['scope_key'],
                $request->user(),
                [
                    'kegiatan_id' => null,
                    'rw_id' => $scope['rw_id'],
                    'rt_id' => $scope['rt_id'],
                    'gate_mode' => $data['gate_mode'],
                ],
                now()->addDays(7),
            );
        });

        $labels = [
            'auto' => 'Otomatis Masuk ↔ Keluar',
            'masuk' => 'Khusus Gerbang Masuk',
            'keluar' => 'Khusus Gerbang Keluar',
        ];

        return back()->with('success', 'Mode gerbang diubah ke: '.$labels[$data['gate_mode']].'.');
    }

    public function close(Request $request)
    {
        $this->mutate($request, fn (array $scope) => RfidAccess::closeSession($scope['scope_key']));

        return back()->with('success', 'Mode Gerbang RFID dinonaktifkan.');
    }

    public function status(Request $request)
    {
        $actor = $request->user();
        abort_unless(RfidAccess::canManageGerbang($actor), 403);

        $scope = RfidAccess::resolveGerbangScope($actor);
        $session = RfidSession::where('scope_key', $scope['scope_key'])->first();
        $snapshot = RfidAccess::deviceStatusSnapshot(
            rtId: $scope['rt_id'],
            sessionId: $session?->id,
            allowGlobalFallback: $actor->isGlobalOperator(),
            includeDeviceMeta: true,
        );

        $logs = RfidAccess::gerbangLogsForActor($actor)
            ->with(['user:id,name', 'rt:id,name'])
            ->orderByDesc('tapped_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'user_name' => $log->user?->name ?? 'Kartu Tidak Dikenal',
                'rt_name' => $log->rt?->name,
                'uid_masked' => $log->uid_masked ?? '••••',
                'arah' => $log->arah,
                'status' => $log->status,
                'device_id' => $log->device_id,
                'keterangan' => $log->keterangan,
                'time' => $log->tapped_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i:s'),
            ]);

        return response()->json([
            'open' => $session && $session->expires_at->isFuture(),
            'gate_mode' => $session?->gate_mode ?? 'auto',
            'expires_at' => $session?->expires_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i:s'),
            'last_seen_at' => $session?->last_seen_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i:s'),
            'device_online' => $snapshot['device_online'],
            'last_seen_sec' => $snapshot['last_seen_sec'],
            'last_device' => $snapshot['last_device'],
            'last_scan' => $snapshot['last_scan'],
            'stats' => $this->todayStats($actor),
            'logs' => $logs,
        ])->header('Cache-Control', 'no-store');
    }

    /**
     * @return array{masuk_today: int, keluar_today: int, ditolak_today: int}
     */
    private function todayStats(User $actor): array
    {
        $base = RfidAccess::gerbangLogsForActor($actor)
            ->where('tapped_at', '>=', now()->timezone('Asia/Jakarta')->startOfDay()->utc());

        return [
            'masuk_today' => (clone $base)->where('status', 'diizinkan')->where('arah', 'masuk')->count(),
            'keluar_today' => (clone $base)->where('status', 'diizinkan')->where('arah', 'keluar')->count(),
            'ditolak_today' => (clone $base)->where('status', 'ditolak')->count(),
        ];
    }

    private function mutate(Request $request, callable $callback): void
    {
        DB::transaction(function () use ($request, $callback) {
            $actor = $request->user();
            abort_unless(RfidAccess::canManageGerbang($actor), 403);
            $scope = RfidAccess::resolveGerbangScope($actor);
            $callback($scope);
        });
    }
}
