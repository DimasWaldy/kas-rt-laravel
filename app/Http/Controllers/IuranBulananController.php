<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\IuranBulanan;
use App\Models\Rumah;
use App\Models\Tagihan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class IuranBulananController extends Controller
{
    public function edit(Request $request, IuranBulanan $iuranBulanan)
    {
        $this->authorizeManage($request);

        return view('iuran_bulanan.edit', compact('iuranBulanan'));
    }

    public function update(Request $request, IuranBulanan $iuranBulanan)
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'nama'       => ['required', 'string', 'max:255'],
            'jumlah'     => ['required', 'integer', 'min:1'],
            'keterangan' => ['nullable', 'string', 'max:500'],
            'is_wajib'   => ['required', 'boolean'],
        ]);

        $oldValues = $iuranBulanan->toArray();
        $iuranBulanan->update($validated);

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => IuranBulanan::class,
            'auditable_id' => $iuranBulanan->id,
            'event' => 'iuran_updated',
            'old_values' => $oldValues,
            'new_values' => $iuranBulanan->fresh()->toArray(),
            'notes' => 'Komponen iuran diupdate: ' . $iuranBulanan->nama . ' untuk periode ' . \Carbon\Carbon::create(null, $iuranBulanan->bulan)->translatedFormat('F') . ' ' . $iuranBulanan->tahun,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Otomatis sinkronisasi tagihan yang belum dibayar di periode terkait
        $syncResult = Tagihan::generate($iuranBulanan->bulan, $iuranBulanan->tahun, Auth::user());

        return redirect()->route('iuran-bulanan.index', [
            'bulan' => $iuranBulanan->bulan,
            'tahun' => $iuranBulanan->tahun,
        ])->with('success', 'Komponen iuran berhasil diperbarui dan tagihan belum dibayar telah disinkronkan.')
          ->with('billing_result', $this->generationSummary($syncResult, (int) $iuranBulanan->bulan, (int) $iuranBulanan->tahun));
    }

    public function destroy(Request $request, IuranBulanan $iuranBulanan)
    {
        $this->authorizeManage($request);

        $bulan = (int) $iuranBulanan->bulan;
        $tahun = (int) $iuranBulanan->tahun;

        // Cek apakah sudah ada tagihan yang lunas atau sedang verifikasi pembayaran
        $hasPaidOrPending = Tagihan::forMonth($bulan, $tahun)
            ->where(function ($q) {
                $q->where('status', 'lunas')
                  ->orWhereIn('status', ['pending_transfer', 'pending_offline'])
                  ->orWhereIn('verification_status', ['menunggu', 'valid']);
            })
            ->exists();

        if ($hasPaidOrPending) {
            return redirect()->route('iuran-bulanan.index', ['bulan' => $bulan, 'tahun' => $tahun])
                ->with('error', 'Komponen iuran tidak dapat dihapus karena sudah ada pembayaran yang lunas atau sedang menunggu verifikasi pada periode ini.');
        }

        $namaKomponen = $iuranBulanan->nama;
        $oldValues = $iuranBulanan->toArray();

        DB::transaction(function () use ($iuranBulanan, $request, $oldValues, $namaKomponen, $bulan, $tahun) {
            $iuranBulanan->delete();

            AuditLog::create([
                'user_id' => Auth::id(),
                'auditable_type' => IuranBulanan::class,
                'auditable_id' => $oldValues['id'] ?? 0,
                'event' => 'iuran_deleted',
                'old_values' => $oldValues,
                'new_values' => [],
                'notes' => 'Komponen iuran dihapus: ' . $namaKomponen . ' periode ' . \Carbon\Carbon::create(null, $bulan)->translatedFormat('F') . ' ' . $tahun,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Sinkronkan ulang tagihan belum dibayar
            Tagihan::generate($bulan, $tahun, Auth::user());
        });

        return redirect()->route('iuran-bulanan.index', ['bulan' => $bulan, 'tahun' => $tahun])
            ->with('success', 'Komponen iuran "' . $namaKomponen . '" berhasil dihapus dan tagihan diperbarui.');
    }

    public function syncTagihan(Request $request)
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'bulan' => ['required', 'integer', 'between:1,12'],
            'tahun' => ['required', 'integer', 'min:2024', 'max:' . (now()->year + 5)],
        ]);

        $bulan = (int) $validated['bulan'];
        $tahun = (int) $validated['tahun'];

        $result = Tagihan::generate($bulan, $tahun, Auth::user());

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => Tagihan::class,
            'auditable_id' => 0, // 0 menandakan aksi massal
            'event' => 'tagihan_synced',
            'old_values' => [],
            'new_values' => $result,
            'notes' => 'Sinkronisasi tagihan manual oleh ' . Auth::user()->name . ' untuk periode ' . \Carbon\Carbon::create(null, $bulan)->translatedFormat('F') . ' ' . $tahun,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('iuran-bulanan.index', ['bulan' => $bulan, 'tahun' => $tahun])
            ->with('success', 'Sinkronisasi tagihan periode ini berhasil dijalankan.')
            ->with('billing_result', $this->generationSummary($result, $bulan, $tahun));
    }

    private function authorizeManage(Request $request): void
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Akses ditolak.');
        }

        $isKetuaRt = in_array($user->role_name, ['ketua_rt', 'rt', 'ketua-rt'], true);

        if (! $user->canManageFinance() && ! $isKetuaRt) {
            abort(403, 'Halaman ini hanya dapat diakses oleh pengelola keuangan atau Ketua RT.');
        }
    }

    public function index(Request $request)
    {
        $this->authorizeManage($request);
        $period = $request->validate([
            'bulan' => ['nullable', 'integer', 'between:1,12'],
            'tahun' => ['nullable', 'integer', 'min:2024', 'max:' . (now()->year + 5)],
        ]);
        $bulan = (int) ($period['bulan'] ?? now()->month);
        $tahun = (int) ($period['tahun'] ?? now()->year);
        $items = IuranBulanan::orderByDesc('tahun')
            ->orderByDesc('bulan')
            ->orderBy('nama')
            ->get();

        $needsPj = Rumah::billingVisibleTo($request->user())->needsBillingContact();
        $needsPjCount = (clone $needsPj)->count();
        $needsPjHouses = $needsPj->with('penanggungJawab')->orderBy('kode_rumah')->limit(10)->get();

        return view('iuran_bulanan.index', compact('items', 'needsPjCount', 'needsPjHouses', 'bulan', 'tahun'));
    }

    public function create(Request $request)
    {
        $this->authorizeManage($request);
        return view('iuran_bulanan.create');
    }

    public function store(Request $request)
    {
        $this->authorizeManage($request);
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:500'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'bulan' => ['required', 'integer', 'between:1,12'],
            'tahun' => ['required', 'integer', 'min:2024', 'max:' . (now()->year + 5)],
            'is_wajib' => ['required', 'boolean'], // 1 untuk Wajib, 0 untuk Opsional
        ], [
            'jumlah.min' => 'Nominal iuran harus lebih dari 0.',
            'jumlah.integer' => 'Nominal iuran harus berupa angka bulat.',
        ]);

        $iuran = IuranBulanan::create($data);

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => IuranBulanan::class,
            'auditable_id' => $iuran->id,
            'event' => 'iuran_created',
            'old_values' => [],
            'new_values' => $iuran->toArray(),
            'notes' => 'Iuran bulanan baru ditambahkan: ' . $iuran->nama . ' untuk periode ' . \Carbon\Carbon::create(null, $iuran->bulan)->translatedFormat('F') . ' ' . $iuran->tahun,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $result = Tagihan::generate($data['bulan'], $data['tahun'], Auth::user());

        return redirect()->route('iuran-bulanan.index', ['bulan' => $data['bulan'], 'tahun' => $data['tahun']])->with('success', 'Iuran bulanan berhasil disimpan. Periksa hasil pembuatan tagihan di bawah.')
            ->with('billing_result', $this->generationSummary($result, (int) $data['bulan'], (int) $data['tahun']));
    }

    /**
     * Menghasilkan tagihan secara massal untuk bulan berjalan.
     * Menggantikan fungsi 'php artisan bills:generate'.
     */
    public function generateMassal(Request $request)
    {
        $this->authorizeManage($request);
        $validated = $request->validate([
            'bulan' => ['nullable', 'integer', 'between:1,12'],
            'tahun' => ['nullable', 'integer', 'min:2024', 'max:' . (now()->year + 5)],
        ]);

        $bulan = (int) ($validated['bulan'] ?? now()->month);
        $tahun = (int) ($validated['tahun'] ?? now()->year);

        $result = DB::transaction(function () use ($bulan, $tahun, $request) {
            // 1. Cek apakah sudah ada komponen iuran di bulan ini
            $hasIuran = IuranBulanan::where('bulan', $bulan)->where('tahun', $tahun)->exists();

            if (!$hasIuran) {
                // Jika kosong, coba salin dari bulan sebelumnya
                $lastMonth = \Carbon\Carbon::create($tahun, $bulan, 1)->subMonth();
                $prevItems = IuranBulanan::where('bulan', $lastMonth->month)->where('tahun', $lastMonth->year)->get();

                if ($prevItems->isNotEmpty()) {
                    foreach ($prevItems as $item) {
                        IuranBulanan::create([
                            'nama' => $item->nama,
                            'keterangan' => $item->keterangan,
                            'jumlah' => $item->jumlah,
                            'bulan' => $bulan,
                            'tahun' => $tahun,
                            'is_wajib' => $item->is_wajib,
                        ]);
                    }
                } else {
                    // Jika bulan lalu juga kosong, buatkan iuran dasar (default)
                    IuranBulanan::create(['nama' => 'Iuran Kebersihan', 'jumlah' => 20000, 'bulan' => $bulan, 'tahun' => $tahun, 'is_wajib' => true]);
                    IuranBulanan::create(['nama' => 'Iuran Keamanan', 'jumlah' => 15000, 'bulan' => $bulan, 'tahun' => $tahun, 'is_wajib' => true]);
                }
            }

            // 2. Trigger generate/update tagihan untuk seluruh Kepala Keluarga
            $result = Tagihan::generate($bulan, $tahun, Auth::user());

            // Tambahkan Audit Log agar terekam siapa admin yang melakukan generate massal via UI
            AuditLog::create([
                'user_id' => Auth::id(),
                'auditable_type' => Tagihan::class,
                'auditable_id' => 0, // 0 menandakan aksi massal
                'event' => 'mass_tagihan_generated',
                'old_values' => [],
                'new_values' => ['bulan' => $bulan, 'tahun' => $tahun, 'result' => $result],
                'notes' => 'Pembangkitan tagihan massal periode ' . \Carbon\Carbon::create(null, $bulan)->translatedFormat('F') . " $tahun dilakukan melalui Dashboard Web.",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $result;
        });

        $namaBulan = \Carbon\Carbon::create(null, $bulan)->translatedFormat('F');
        $flashType = 'success';
        $flashMsg = "Proses tagihan periode {$namaBulan} {$tahun} selesai. Periksa hasil dan rumah yang belum dapat ditagih.";

        if (($result['created'] ?? 0) === 0 && ($result['updated'] ?? 0) === 0) {
            $flashType = 'info';
            $flashMsg = "Tagihan periode {$namaBulan} {$tahun} sudah digenerate sebelumnya. Tidak ada tagihan baru yang dibuat.";
        }

        return redirect()->route('iuran-bulanan.index', ['bulan' => $bulan, 'tahun' => $tahun])
            ->with($flashType, $flashMsg)
            ->with('billing_result', $this->generationSummary($result, $bulan, $tahun));
    }

    private function generationSummary(array $result, int $bulan, int $tahun): array
    {
        return [
            'bulan' => $bulan, 'tahun' => $tahun, 'created' => $result['created'],
            'updated' => $result['updated'], 'skipped' => $result['skipped'],
            'active_houses' => $result['active_houses'], 'has_iuran' => $result['has_iuran'],
            'unbilled_count' => count($result['unbilled_houses']),
        ];
    }
}
