<?php

namespace App\Http\Controllers;

use App\Models\Aset;
use App\Models\PeminjamanAset;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PeminjamanAsetController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = PeminjamanAset::with(['aset.rt', 'pemohon', 'processor'])
            ->latest();

        if ($user->hasPermission('manage-aset')) {
            $rtId = $this->resolveRtId($user);
            $query->whereHas('aset', fn ($query) => $query->where('rt_id', $rtId));
        } else {
            $query->where('pemohon_id', $user->id);
        }

        $status = $request->string('status')->toString();
        if (in_array($status, $this->statuses(), true)) {
            $query->where('status', $status);
        }

        return view('peminjaman_aset.index', [
            'peminjamans' => $query->paginate(15)->withQueryString(),
            'status' => $status,
            'statuses' => $this->statuses(),
            'canManage' => $user->hasPermission('manage-aset'),
        ]);
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->hasPermission('pinjam-aset'), 403);

        $rtId = $this->resolveRtId($request->user());
        $asets = Aset::where('rt_id', $rtId)
            ->where('is_active', true)
            ->where('kondisi', '!=', 'rusak_berat')
            ->orderBy('nama')
            ->get();

        $asetTerpilih = null;
        if ($request->filled('aset_id')) {
            $asetTerpilih = $asets->firstWhere('id', (int) $request->integer('aset_id'));
        }

        return view('peminjaman_aset.create', compact('asets', 'asetTerpilih'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasPermission('pinjam-aset'), 403);

        $validated = $request->validate([
            'aset_id' => ['required', 'integer', 'exists:asets,id'],
            'tanggal_mulai' => ['required', 'date', 'after_or_equal:today'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'keperluan' => ['required', 'string', 'min:5', 'max:255'],
            'jumlah_dipinjam' => ['required', 'integer', 'min:1'],
            'catatan_pemohon' => ['nullable', 'string', 'max:500'],
        ]);

        $aset = Aset::findOrFail($validated['aset_id']);
        $this->authorizeSameRt($aset, $request->user());
        $this->ensureAsetCanBeBorrowed($aset);
        $this->ensureAvailable($aset, $validated);

        $peminjamanAset = PeminjamanAset::create([
            'aset_id' => $aset->id,
            'pemohon_id' => Auth::id(),
            'tanggal_mulai' => $validated['tanggal_mulai'],
            'tanggal_selesai' => $validated['tanggal_selesai'],
            'keperluan' => $validated['keperluan'],
            'jumlah_dipinjam' => $validated['jumlah_dipinjam'],
            'catatan_pemohon' => $validated['catatan_pemohon'] ?? null,
            'status' => 'diajukan',
        ]);

        return redirect()->route('peminjaman-aset.show', $peminjamanAset)
            ->with('success', 'Pengajuan peminjaman aset berhasil dikirim ke pengurus RT.');
    }

    public function show(PeminjamanAset $peminjamanAset)
    {
        $peminjamanAset->load(['aset.rt', 'pemohon', 'processor']);
        $this->authorizeView($peminjamanAset, Auth::user());

        return view('peminjaman_aset.show', compact('peminjamanAset'));
    }

    public function setujui(Request $request, PeminjamanAset $peminjamanAset)
    {
        $this->authorizeManage($peminjamanAset, $request->user());
        abort_unless($peminjamanAset->status === 'diajukan', 403);

        $validated = $request->validate([
            'catatan_pengurus' => ['nullable', 'string', 'max:500'],
        ]);

        $peminjamanAset->load('aset');
        $this->ensureAsetCanBeBorrowed($peminjamanAset->aset);
        $this->ensureAvailable($peminjamanAset->aset, [
            'tanggal_mulai' => $peminjamanAset->tanggal_mulai,
            'tanggal_selesai' => $peminjamanAset->tanggal_selesai,
            'jumlah_dipinjam' => $peminjamanAset->jumlah_dipinjam,
        ], $peminjamanAset->id);

        $peminjamanAset->update([
            'status' => 'disetujui',
            'diproses_oleh' => Auth::id(),
            'tanggal_diproses' => now(),
            'catatan_pengurus' => $validated['catatan_pengurus'] ?? null,
        ]);

        return back()->with('success', 'Peminjaman aset berhasil disetujui.');
    }

    public function tolak(Request $request, PeminjamanAset $peminjamanAset)
    {
        $this->authorizeManage($peminjamanAset, $request->user());
        abort_unless($peminjamanAset->status === 'diajukan', 403);

        $validated = $request->validate([
            'catatan_pengurus' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $peminjamanAset->update([
            'status' => 'ditolak',
            'diproses_oleh' => Auth::id(),
            'tanggal_diproses' => now(),
            'catatan_pengurus' => $validated['catatan_pengurus'],
        ]);

        return back()->with('success', 'Peminjaman aset berhasil ditolak.');
    }

    public function konfirmasiDipinjam(PeminjamanAset $peminjamanAset)
    {
        $this->authorizeManage($peminjamanAset, Auth::user());
        abort_unless($peminjamanAset->status === 'disetujui', 403);

        $peminjamanAset->update([
            'status' => 'dipinjam',
            'tanggal_dipinjam' => now(),
        ]);

        return back()->with('success', 'Aset berhasil dikonfirmasi sudah dipinjam.');
    }

    public function konfirmasiKembali(PeminjamanAset $peminjamanAset)
    {
        $this->authorizeManage($peminjamanAset, Auth::user());
        abort_unless($peminjamanAset->status === 'dipinjam', 403);

        $peminjamanAset->update([
            'status' => 'dikembalikan',
            'tanggal_dikembalikan' => now(),
        ]);

        return back()->with('success', 'Aset berhasil dikonfirmasi sudah dikembalikan.');
    }

    private function authorizeView(PeminjamanAset $peminjamanAset, User $user): void
    {
        if ($peminjamanAset->pemohon_id === $user->id) {
            return;
        }

        if ($user->hasPermission('manage-aset')) {
            $this->authorizeManage($peminjamanAset, $user);
            return;
        }

        abort(403);
    }

    private function authorizeManage(PeminjamanAset $peminjamanAset, User $user): void
    {
        abort_unless($user->hasPermission('manage-aset'), 403);

        if (! $peminjamanAset->relationLoaded('aset')) {
            $peminjamanAset->load('aset');
        }

        $this->authorizeSameRt($peminjamanAset->aset, $user);
    }

    private function authorizeSameRt(Aset $aset, User $user): void
    {
        abort_unless($aset->rt_id === $this->resolveRtId($user), 403);
    }

    private function resolveRtId(User $user): int
    {
        abort_unless($user->rt_id, 403, 'Akun belum terhubung ke RT.');

        return (int) $user->rt_id;
    }

    private function ensureAsetCanBeBorrowed(Aset $aset): void
    {
        if (! $aset->is_active) {
            throw ValidationException::withMessages([
                'aset_id' => 'Aset ini sedang tidak aktif dan tidak bisa dipinjam.',
            ]);
        }

        if ($aset->kondisi === 'rusak_berat') {
            throw ValidationException::withMessages([
                'aset_id' => 'Aset rusak berat tidak bisa dipinjam.',
            ]);
        }
    }

    private function ensureAvailable(Aset $aset, array $data, ?int $excludeId = null): void
    {
        if ((int) $data['jumlah_dipinjam'] > $aset->jumlah_tersedia) {
            throw ValidationException::withMessages([
                'jumlah_dipinjam' => 'Jumlah yang dipinjam melebihi stok aset yang tersedia.',
            ]);
        }

        $tanggalMulai = $data['tanggal_mulai'] instanceof Carbon
            ? $data['tanggal_mulai']
            : Carbon::parse($data['tanggal_mulai']);
        $tanggalSelesai = $data['tanggal_selesai'] instanceof Carbon
            ? $data['tanggal_selesai']
            : Carbon::parse($data['tanggal_selesai']);

        if (! $aset->isAvailableOn($tanggalMulai, $tanggalSelesai, $excludeId)) {
            throw ValidationException::withMessages([
                'tanggal_mulai' => 'Aset sudah dipinjam pada rentang tanggal tersebut.',
            ]);
        }
    }

    private function statuses(): array
    {
        return [
            'diajukan',
            'disetujui',
            'dipinjam',
            'dikembalikan',
            'ditolak',
        ];
    }
}
