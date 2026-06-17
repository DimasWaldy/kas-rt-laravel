<?php

namespace App\Http\Controllers;

use App\Models\Aset;
use App\Models\PeminjamanAset;
use App\Models\Rw;
use App\Models\User;
use App\Notifications\PeminjamanDiajukan;
use App\Notifications\PeminjamanDisetujui;
use App\Notifications\PeminjamanDitolak;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class PeminjamanAsetController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $scope = $this->resolveScope($request);
        $this->authorizePinjamScope($scope, $user);

        $query = PeminjamanAset::with(['aset.rt', 'aset.rw', 'pemohon', 'processor'])
            ->whereHas('aset', fn ($query) => $query->where('scope', $scope))
            ->latest();

        if ($this->canManageScope($scope, $user)) {
            $rwId = $this->resolveRwId($user);
            $rtId = $scope === 'rt' ? $this->resolveRtId($user) : null;

            $query->whereHas('aset', function ($query) use ($scope, $rwId, $rtId) {
                if ($scope === 'rw') {
                    $query->where('rw_id', $rwId);
                    return;
                }

                $query->where('rt_id', $rtId);
            });
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
            'scope' => $scope,
            'statuses' => $this->statuses(),
            'canManage' => $this->canManageScope($scope, $user),
        ]);
    }

    public function create(Request $request)
    {
        $scope = $this->resolveScope($request);
        $this->authorizePinjamScope($scope, $request->user());

        $asets = Aset::where('scope', $scope)
            ->where('is_active', true)
            ->where('kondisi', '!=', 'rusak_berat')
            ->orderBy('nama')
            ->when(
                $scope === 'rw',
                fn ($query) => $query->where('rw_id', $this->resolveRwId($request->user())),
                fn ($query) => $query->where('rt_id', $this->resolveRtId($request->user()))
            )
            ->get();

        $asetTerpilih = null;
        if ($request->filled('aset_id')) {
            $asetTerpilih = $asets->firstWhere('id', (int) $request->integer('aset_id'));
        }

        return view('peminjaman_aset.create', compact('asets', 'asetTerpilih', 'scope'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'aset_id' => ['required', 'integer', 'exists:asets,id'],
            'scope' => ['nullable', 'in:rt,rw'],
            'tanggal_mulai' => ['required', 'date', 'after_or_equal:today'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'keperluan' => ['required', 'string', 'min:5', 'max:255'],
            'jumlah_dipinjam' => ['required', 'integer', 'min:1'],
            'catatan_pemohon' => ['nullable', 'string', 'max:500'],
        ]);

        $aset = Aset::findOrFail($validated['aset_id']);
        $this->authorizeBorrowAset($aset, $request->user());
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

        $peminjamanAset->load(['aset', 'pemohon']);
        Notification::send(
            $this->notificationRecipientsFor($peminjamanAset),
            new PeminjamanDiajukan($peminjamanAset)
        );

        $indexRoute = $aset->isRwAsset()
            ? 'peminjaman-aset-rw.index'
            : 'peminjaman-aset.index';

        return redirect()->route($indexRoute)
            ->with('success', 'Peminjaman diajukan.');
    }

    public function show(PeminjamanAset $peminjamanAset)
    {
        $peminjamanAset->load(['aset.rt', 'aset.rw', 'pemohon', 'processor']);
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

        $peminjamanAset->load(['aset', 'pemohon']);
        $peminjamanAset->pemohon->notify(new PeminjamanDisetujui($peminjamanAset));

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

        $peminjamanAset->load(['aset', 'pemohon']);
        $peminjamanAset->pemohon->notify(new PeminjamanDitolak($peminjamanAset));

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

        if ($this->canManageScope($peminjamanAset->aset->scope, $user)) {
            $this->authorizeManage($peminjamanAset, $user);
            return;
        }

        abort(403);
    }

    private function notificationRecipientsFor(PeminjamanAset $peminjamanAset)
    {
        $peminjamanAset->loadMissing('aset');

        if ($peminjamanAset->aset->isRwAsset()) {
            return User::whereHas('role.permissions', fn ($query) => $query->where('name', 'manage-aset-rw'))
                ->whereNull('rt_id')
                ->get();
        }

        return User::whereHas('role.permissions', fn ($query) => $query->where('name', 'manage-aset'))
            ->where('rt_id', $peminjamanAset->aset->rt_id)
            ->get();
    }

    private function authorizeManage(PeminjamanAset $peminjamanAset, User $user): void
    {
        if (! $peminjamanAset->relationLoaded('aset')) {
            $peminjamanAset->load('aset');
        }

        $aset = $peminjamanAset->aset;
        abort_unless($this->canManageScope($aset->scope, $user), 403);

        if ($user->isGlobalOperator()) {
            return;
        }

        if ($aset->isRwAsset()) {
            abort_unless($aset->rw_id === $this->resolveRwId($user), 403);
            return;
        }

        abort_unless($aset->rt_id === $this->resolveRtId($user), 403);
    }

    private function authorizeBorrowAset(Aset $aset, User $user): void
    {
        $this->authorizePinjamScope($aset->scope, $user);

        if ($user->isGlobalOperator()) {
            return;
        }

        if ($aset->isRwAsset()) {
            abort_unless($aset->rw_id === $this->resolveRwId($user), 403);
            return;
        }

        abort_unless($aset->rt_id === $this->resolveRtId($user), 403);
    }

    private function canManageScope(string $scope, User $user): bool
    {
        return $scope === 'rw'
            ? $user->hasPermission('manage-aset-rw')
            : $user->hasPermission('manage-aset');
    }

    private function authorizePinjamScope(string $scope, User $user): void
    {
        abort_unless(
            $scope === 'rw'
                ? $user->hasPermission('pinjam-aset-rw')
                : $user->hasPermission('pinjam-aset'),
            403
        );
    }

    private function resolveScope(Request $request): string
    {
        $scope = $request->route('scope') ?: $request->string('scope')->toString();

        return in_array($scope, ['rt', 'rw'], true) ? $scope : 'rt';
    }

    private function resolveRtId(User $user): int
    {
        abort_unless($user->rt_id, 403, 'Akun belum terhubung ke RT.');

        return (int) $user->rt_id;
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
