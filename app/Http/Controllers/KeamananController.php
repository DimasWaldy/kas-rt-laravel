<?php

namespace App\Http\Controllers;

use App\Models\JadwalShiftSatpam;
use App\Models\LogPatroli;
use App\Models\Rw;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class KeamananController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasPermission('manage-fasilitas'), 403);

        $rwId = $this->resolveRwId($request->user());
        $tanggalMulai = $request->date('tanggal_mulai') ?: now()->startOfWeek();
        $tanggalSelesai = $request->date('tanggal_selesai') ?: now()->endOfWeek();

        $shifts = JadwalShiftSatpam::with(['pencatat', 'logPatrolis.pencatat'])
            ->where('rw_id', $rwId)
            ->whereBetween('tanggal', [$tanggalMulai->toDateString(), $tanggalSelesai->toDateString()])
            ->orderBy('tanggal')
            ->orderBy('jam_mulai')
            ->get();

        $logs = LogPatroli::with(['jadwalShift', 'pencatat'])
            ->whereHas('jadwalShift', fn($query) => $query->where('rw_id', $rwId))
            ->whereBetween('waktu_patroli', [$tanggalMulai->startOfDay(), $tanggalSelesai->endOfDay()])
            ->latest('waktu_patroli')
            ->get();

        return view('keamanan.index', [
            'shifts' => $shifts,
            'logs' => $logs,
            'tanggalMulai' => $tanggalMulai,
            'tanggalSelesai' => $tanggalSelesai,
        ]);
    }

    public function createShift(Request $request)
    {
        abort_unless($request->user()->hasPermission('manage-fasilitas'), 403);

        return view('keamanan.shift.create');
    }

    public function storeShift(Request $request)
    {
        abort_unless($request->user()->hasPermission('manage-fasilitas'), 403);

        $validated = $request->validate([
            'nama_satpam' => ['required', 'string', 'max:255'],
            'kontak_satpam' => ['nullable', 'string', 'max:20'],
            'shift' => ['required', Rule::in(['pagi', 'siang', 'malam'])],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i'],
            'tanggal' => ['required', 'date'],
        ]);

        $shift = JadwalShiftSatpam::create([
            ...$validated,
            'rw_id' => $this->resolveRwId($request->user()),
            'dicatat_oleh' => Auth::id(),
        ]);

        return redirect()->route('keamanan.shift.show', $shift)
            ->with('success', 'Jadwal shift satpam berhasil ditambahkan.');
    }

    public function showShift(Request $request, JadwalShiftSatpam $shift)
    {
        abort_unless($request->user()->hasPermission('manage-fasilitas'), 403);
        $this->authorizeShift($request->user(), $shift);

        $shift->load(['rw', 'pencatat', 'logPatrolis.pencatat']);

        return view('keamanan.shift.show', compact('shift'));
    }

    public function storePatroli(Request $request, JadwalShiftSatpam $shift)
    {
        abort_unless($request->user()->hasPermission('manage-fasilitas'), 403);
        $this->authorizeShift($request->user(), $shift);

        $validated = $request->validate([
            'waktu_patroli' => ['required', 'date'],
            'catatan' => ['nullable', 'string', 'max:500'],
            'ada_kejadian' => ['nullable', 'boolean'],
        ]);

        LogPatroli::create([
            ...$validated,
            'jadwal_shift_id' => $shift->id,
            'ada_kejadian' => $request->boolean('ada_kejadian'),
            'dicatat_oleh' => Auth::id(),
        ]);

        return back()->with('success', 'Log patroli berhasil dicatat.');
    }

    private function authorizeShift(User $user, JadwalShiftSatpam $shift): void
    {
        if ($user->isGlobalOperator()) {
            return;
        }

        abort_unless($shift->rw_id === $this->resolveRwId($user), 403);
    }

    private function resolveRwId(User $user): int
    {
        $rwId = $user->rt()->value('rw_id');

        if (! $rwId && ($user->isRwOfficial() || $user->isGlobalOperator())) {
            $rwId = Rw::where('is_active', true)->orderBy('id')->value('id');
        }

        abort_unless($rwId, 403, 'Akun belum terhubung ke RW.');

        return (int) $rwId;
    }
}
