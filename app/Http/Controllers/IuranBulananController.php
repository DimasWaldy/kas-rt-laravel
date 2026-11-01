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
    public function index(Request $request)
    {
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

    public function create()
    {
        return view('iuran_bulanan.create');
    }

    public function store(Request $request)
    {
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

        return redirect()->route('iuran-bulanan.index', ['bulan' => $bulan, 'tahun' => $tahun])
            ->with('success', "Proses tagihan periode " . \Carbon\Carbon::create(null, $bulan)->translatedFormat('F') . " $tahun selesai. Periksa hasil dan rumah yang belum dapat ditagih.")
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
