<?php

namespace App\Http\Controllers;

use App\Models\Rw;
use App\Models\Umkm;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UmkmController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $rwId = $this->resolveRwId($user);
        $canManage = $user->hasPermission('manage-umkm');
        $status = $request->string('status')->toString();
        $kategori = $request->string('kategori')->toString();

        $query = Umkm::with(['pemilik', 'rt', 'jamOperasional'])
            ->withCount(['produkUmkms' => fn ($query) => $query->where('is_available', true)])
            ->where('rw_id', $rwId)
            ->latest();

        if ($canManage) {
            if (in_array($status, $this->statuses(), true)) {
                $query->where('status', $status);
            }
        } else {
            $query->visible();
        }

        if (in_array($kategori, $this->categories(), true)) {
            $query->where('kategori', $kategori);
        }

        return view('umkm.index', [
            'umkms' => $query->paginate(12)->withQueryString(),
            'status' => $status,
            'kategori' => $kategori,
            'categories' => $this->categories(),
            'canManage' => $canManage,
            'pendingCount' => $canManage
                ? Umkm::where('rw_id', $rwId)->where('status', 'pending')->count()
                : 0,
        ]);
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->hasPermission('daftar-umkm'), 403);

        return view('umkm.create', [
            'categories' => $this->categories(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('daftar-umkm'), 403);

        $validated = $this->validateUmkm($request);
        $jamOperasional = $validated['jam_operasional'];
        $fotoPath = $request->hasFile('foto_usaha')
            ? $request->file('foto_usaha')->store('umkm', 'local')
            : null;

        unset($validated['foto_usaha'], $validated['jam_operasional']);

        try {
            $umkm = DB::transaction(function () use ($validated, $jamOperasional, $fotoPath, $user) {
                $umkm = Umkm::create([
                    ...$validated,
                    'rw_id' => $this->resolveRwId($user),
                    'rt_id' => $user->rt_id,
                    'pemilik_id' => Auth::id(),
                    'foto_usaha' => $fotoPath,
                    'status' => 'pending',
                ]);

                $this->createJamOperasional($umkm, $jamOperasional);

                return $umkm;
            });
        } catch (\Throwable $exception) {
            if ($fotoPath) {
                Storage::disk('local')->delete($fotoPath);
            }

            throw $exception;
        }

        return redirect()->route('umkm.saya')
            ->with('success', "Usaha {$umkm->nama_usaha} berhasil didaftarkan dan menunggu persetujuan pengurus.");
    }

    public function show(Request $request, Umkm $umkm)
    {
        $this->authorizeVisible($request->user(), $umkm);
        $umkm->load([
            'pemilik',
            'rt',
            'rw',
            'diprosesOleh',
            'jamOperasional',
            'produkUmkms' => fn ($query) => $query->where('is_available', true)->latest(),
        ]);

        return view('umkm.show', compact('umkm'));
    }

    public function foto(Request $request, Umkm $umkm)
    {
        $this->authorizeVisible($request->user(), $umkm);
        abort_unless(
            $umkm->foto_usaha && Storage::disk('local')->exists($umkm->foto_usaha),
            404
        );

        return Storage::disk('local')->response($umkm->foto_usaha);
    }

    public function edit(Request $request, Umkm $umkm)
    {
        $this->authorizeEdit($request->user(), $umkm);
        $umkm->load('jamOperasional');

        return view('umkm.edit', [
            'umkm' => $umkm,
            'categories' => $this->categories(),
        ]);
    }

    public function update(Request $request, Umkm $umkm)
    {
        $user = $request->user();
        $this->authorizeEdit($user, $umkm);

        $validated = $this->validateUmkm($request);
        $jamOperasional = $validated['jam_operasional'];
        unset($validated['jam_operasional']);
        $previousStatus = $umkm->status;
        $fotoLama = $umkm->foto_usaha;
        $fotoBaru = null;

        if ($request->hasFile('foto_usaha')) {
            $fotoBaru = $request->file('foto_usaha')->store('umkm', 'local');
            $validated['foto_usaha'] = $fotoBaru;
        } else {
            unset($validated['foto_usaha']);
        }

        if (! $user->hasPermission('manage-umkm') && $previousStatus === 'rejected') {
            $validated['status'] = 'pending';
            $validated['catatan_pengurus'] = null;
            $validated['diproses_oleh'] = null;
            $validated['diproses_at'] = null;
        }

        try {
            DB::transaction(function () use ($umkm, $validated, $jamOperasional) {
                $umkm->update($validated);
                $umkm->jamOperasional()->delete();
                $this->createJamOperasional($umkm, $jamOperasional);
            });
        } catch (\Throwable $exception) {
            if ($fotoBaru) {
                Storage::disk('local')->delete($fotoBaru);
            }

            throw $exception;
        }

        if ($fotoBaru && $fotoLama) {
            Storage::disk('local')->delete($fotoLama);
        }

        return redirect()->route('umkm.show', $umkm)
            ->with('success', $previousStatus === 'rejected' && $umkm->status === 'pending'
                ? 'Data usaha diperbarui dan diajukan kembali untuk persetujuan.'
                : 'Data usaha berhasil diperbarui.');
    }

    public function myUmkm(Request $request)
    {
        abort_unless($request->user()->hasPermission('daftar-umkm'), 403);

        $umkms = Umkm::with(['rt', 'produkUmkms', 'jamOperasional'])
            ->where('pemilik_id', $request->user()->id)
            ->latest()
            ->get();

        return view('umkm.saya', compact('umkms'));
    }

    public function approve(Request $request, Umkm $umkm)
    {
        $this->authorizeManage($request->user(), $umkm);

        $umkm->update([
            'status' => 'approved',
            'catatan_pengurus' => null,
            'diproses_oleh' => $request->user()->id,
            'diproses_at' => now(),
        ]);

        return back()->with('success', 'UMKM berhasil disetujui dan sudah tampil di direktori.');
    }

    public function reject(Request $request, Umkm $umkm)
    {
        $this->authorizeManage($request->user(), $umkm);

        $validated = $request->validate([
            'catatan_pengurus' => ['required', 'string', 'min:5', 'max:255'],
        ]);

        $umkm->update([
            'status' => 'rejected',
            'catatan_pengurus' => $validated['catatan_pengurus'],
            'diproses_oleh' => $request->user()->id,
            'diproses_at' => now(),
        ]);

        return back()->with('success', 'Pendaftaran UMKM berhasil ditolak.');
    }

    public function nonaktifkan(Request $request, Umkm $umkm)
    {
        $user = $request->user();
        $this->authorizeEdit($user, $umkm);

        if ($umkm->status !== 'approved') {
            return back()->with('error', 'Hanya UMKM yang sudah aktif yang dapat dinonaktifkan.');
        }

        $umkm->update(['status' => 'nonaktif']);

        return back()->with('success', 'UMKM berhasil dinonaktifkan.');
    }

    public function aktifkanKembali(Request $request, Umkm $umkm)
    {
        $user = $request->user();
        $this->authorizeSameRw($user, $umkm);
        abort_unless($umkm->pemilik_id === $user->id, 403);

        if ($umkm->status !== 'nonaktif') {
            return back()->with('error', 'UMKM ini tidak sedang berstatus nonaktif.');
        }

        $umkm->update(['status' => 'approved']);

        return back()->with('success', 'UMKM berhasil diaktifkan kembali.');
    }

    private function validateUmkm(Request $request): array
    {
        return $request->validate([
            'nama_usaha' => ['required', 'string', 'max:255'],
            'kategori' => ['required', Rule::in($this->categories())],
            'deskripsi' => ['required', 'string', 'min:20', 'max:1000'],
            'alamat_lokasi' => ['nullable', 'string', 'max:255'],
            'nomor_whatsapp' => ['required', 'string', 'regex:/^[0-9+\-\s]+$/', 'min:10', 'max:15'],
            'jam_operasional' => ['required', 'array', 'size:7'],
            'jam_operasional.*.hari' => ['required', 'integer', 'between:1,7', 'distinct'],
            'jam_operasional.*.is_tutup' => ['required', 'boolean'],
            'jam_operasional.*.jam_buka' => [
                'nullable',
                'required_if:jam_operasional.*.is_tutup,false',
                'date_format:H:i',
            ],
            'jam_operasional.*.jam_tutup' => [
                'nullable',
                'required_if:jam_operasional.*.is_tutup,false',
                'date_format:H:i',
                'after:jam_operasional.*.jam_buka',
            ],
            'foto_usaha' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);
    }

    private function createJamOperasional(Umkm $umkm, array $jamOperasional): void
    {
        foreach ($jamOperasional as $jadwal) {
            $isTutup = filter_var(
                $jadwal['is_tutup'] ?? false,
                FILTER_VALIDATE_BOOLEAN
            );

            $umkm->jamOperasional()->create([
                'hari' => $jadwal['hari'],
                'is_tutup' => $isTutup,
                'jam_buka' => $isTutup ? null : $jadwal['jam_buka'],
                'jam_tutup' => $isTutup ? null : $jadwal['jam_tutup'],
            ]);
        }
    }

    private function authorizeVisible(User $user, Umkm $umkm): void
    {
        $this->authorizeSameRw($user, $umkm);

        if ($umkm->status === 'approved') {
            return;
        }

        abort_unless(
            $umkm->pemilik_id === $user->id || $user->hasPermission('manage-umkm'),
            403
        );
    }

    private function authorizeEdit(User $user, Umkm $umkm): void
    {
        $this->authorizeSameRw($user, $umkm);
        abort_unless(
            $umkm->pemilik_id === $user->id || $user->hasPermission('manage-umkm'),
            403
        );
    }

    private function authorizeManage(User $user, Umkm $umkm): void
    {
        abort_unless($user->hasPermission('manage-umkm'), 403);
        $this->authorizeSameRw($user, $umkm);
    }

    private function authorizeSameRw(User $user, Umkm $umkm): void
    {
        abort_unless($umkm->rw_id === $this->resolveRwId($user), 403);
    }

    private function resolveRwId(User $user): int
    {
        $rwId = $user->rt()->value('rw_id');

        if (! $rwId && ($user->isRwOfficial() || $user->isGlobalOperator())) {
            $rwId = Rw::where('is_active', true)->orderBy('id')->value('id');
        }

        abort_unless($rwId, 403, 'Akun belum terhubung ke wilayah.');

        return (int) $rwId;
    }

    private function categories(): array
    {
        return [
            'makanan_minuman',
            'jasa',
            'kerajinan',
            'sembako',
            'fashion',
            'pertanian',
            'lainnya',
        ];
    }

    private function statuses(): array
    {
        return ['pending', 'approved', 'rejected', 'nonaktif'];
    }
}
