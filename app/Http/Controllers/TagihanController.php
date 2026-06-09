<?php

namespace App\Http\Controllers;

use App\Http\Requests\PayTagihanRequest;
use App\Http\Requests\StoreTagihanRequest;
use App\Models\Tagihan;
use App\Models\IuranBulanan;
use App\Models\KasMasuk;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Notifications\PaymentReceived;
use Illuminate\Support\Facades\Notification;

class TagihanController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $bulan = now()->month;
        $tahun = now()->year;

        $iuranItems = IuranBulanan::forMonth($bulan, $tahun)->get();
        // Generate otomatis di sini dihapus karena sudah dipindah ke saat Admin input Iuran Bulanan

        $headUser = null;
        $rumah = $user->rumah;
        $showHeadNotice = false;
        $canPayTagihan = true;

        if ($rumah) {
            $headUser = $rumah->penanggungJawab;
            $showHeadNotice = ! $user->is_penanggung_jawab_rumah;
            $canPayTagihan = $user->is_penanggung_jawab_rumah;
            $tagihan = Tagihan::where('rumah_id', $rumah->id)
                ->orderByDesc('tahun')
                ->orderByDesc('bulan')
                ->get();
        } elseif ($user->is_kepala_keluarga) {
            $headUser = $user;
            $tagihan = Tagihan::where('user_id', $user->id)
                ->orderByDesc('tahun')
                ->orderByDesc('bulan')
                ->get();
        } else {
            $headUser = User::where('no_kk', $user->no_kk)
                ->where('is_kepala_keluarga', true)
                ->first();

            if ($headUser) {
                $showHeadNotice = true;
                $tagihan = Tagihan::where('user_id', $headUser->id)
                    ->orderByDesc('tahun')
                    ->orderByDesc('bulan')
                    ->get();
            } else {
                $tagihan = collect();
            }
        }

        // Optimasi: Ambil semua iuran bulanan yang relevan untuk breakdown tanpa N+1 query
        $periodes = $tagihan->map(fn($t) => $t->bulan . '-' . $t->tahun)->unique();
        $allIuranItems = IuranBulanan::whereIn(DB::raw("CONCAT(bulan, '-', tahun)"), $periodes)->get();

        $tagihan->each(function ($item) use ($allIuranItems) {
            $item->details = $allIuranItems->filter(function ($iuran) use ($item) {
                return $iuran->bulan == $item->bulan
                    && $iuran->tahun == $item->tahun
                    && Tagihan::billingGroupForIuran($iuran) === $item->billing_group;
            })->values();
        });

        return view('tagihan.index', compact('tagihan', 'iuranItems', 'bulan', 'tahun', 'headUser', 'showHeadNotice', 'rumah', 'canPayTagihan'));
    }

    public function pay(PayTagihanRequest $request)
    {
        $validated = $request->validated();
        $paymentMethod = $validated['payment_method'];

        $user = Auth::user();

        if ($user->rumah_id && ! $user->is_penanggung_jawab_rumah) {
            return redirect()->route('tagihan.index')->with('error', 'Hanya penanggung jawab rumah yang dapat membayar tagihan iuran.');
        }

        if (! $user->rumah_id && ! $user->is_kepala_keluarga) {
            return redirect()->route('tagihan.index')->with('error', 'Hanya kepala keluarga yang dapat membayar tagihan KK.');
        }

        return DB::transaction(function () use ($request, $validated, $paymentMethod, $user) {
            $tagihan = Tagihan::where('id', $validated['tagihan_id'])
                ->when($user->rumah_id, fn($query) => $query->where('rumah_id', $user->rumah_id))
                ->when(! $user->rumah_id, fn($query) => $query->where('user_id', $user->id))
                ->lockForUpdate()
                ->firstOrFail();

            // Pastikan nominal tagihan valid setelah row terkunci.
            if ($tagihan->total <= 0) {
                return redirect()->back()->with('error', 'Tagihan ini tidak memiliki nominal pembayaran.');
            }

            // Validasi ulang setelah lock untuk mencegah dua request memproses tagihan yang sama.
            if (! in_array($tagihan->status, ['belum_bayar', 'failed'], true)) {
                return redirect()->back()->with('error', 'Tagihan ini sudah dibayar atau sedang dalam proses verifikasi.');
            }

            $oldValues = $tagihan->getOriginal();

            if ($paymentMethod === 'transfer') {
                $path = $request->file('bukti')->store('tagihan-bukti', 'local');
                $tagihan->bukti = $path;
                $tagihan->status = 'pending_transfer';
                $tagihan->payment_method = 'transfer';
                $tagihan->note = $validated['note'] ?? null;
            } else {
                $tagihan->bukti = null;
                $tagihan->status = 'pending_offline';
                $tagihan->payment_method = 'offline';
                $tagihan->note = $validated['note'] ?? null;
            }

            $tagihan->verification_status = 'menunggu';
            $tagihan->verification_note = null;
            $tagihan->rejection_reason = null;
            $tagihan->rejected_at = null;
            $tagihan->rejected_by = null;
            $tagihan->verified_by = null;
            $tagihan->verified_at = null;
            $tagihan->transaction_number ??= Tagihan::nextTransactionNumber();
            $tagihan->saveQuietly();
            $tagihan->recordAuditWithNote(
                'payment_submitted',
                $oldValues,
                'Pembayaran diajukan via ' . $tagihan->payment_method
            );

            // Kirim Notifikasi ke Admin
            $admins = User::whereRelation('role', 'name', 'admin')->get();
            Notification::send($admins, new PaymentReceived($tagihan));

            return redirect()->route('tagihan.index')->with('success', 'Pembayaran tagihan telah dikirim. Tunggu konfirmasi RT.');
        });
    }

    public function bukti(Tagihan $tagihan)
    {
        abort_unless($this->canViewBukti($tagihan), 403);

        if (! $tagihan->bukti) {
            abort(404);
        }

        $disk = Storage::disk('local')->exists($tagihan->bukti) ? 'local' : 'public';

        abort_unless(Storage::disk($disk)->exists($tagihan->bukti), 404);

        return response()->file(Storage::disk($disk)->path($tagihan->bukti), [
            'Content-Disposition' => 'inline; filename="' . basename($tagihan->bukti) . '"',
        ]);
    }

    public function adminIndex(Request $request)
    {
        Auth::user()->unreadNotifications->markAsRead();

        $filterBulan = $request->filled('bulan') ? (int) $request->query('bulan') : null;
        $filterTahun = $request->filled('tahun') ? (int) $request->query('tahun') : null;

        $tagihans = Tagihan::with(['user', 'rumah', 'verifier', 'rejecter'])
            ->when($filterBulan, fn ($query) => $query->where('bulan', $filterBulan))
            ->when($filterTahun, fn ($query) => $query->where('tahun', $filterTahun))
            ->orderByDesc('tahun')
            ->orderByDesc('bulan')
            ->paginate(20);

        $users = User::whereRelation('role', 'name', 'warga')
            ->where('is_kepala_keluarga', true)
            ->orderBy('name')
            ->get();
        $bulanList = [];
        for ($i = 1; $i <= 12; $i++) {
            $bulanList[$i] = \Carbon\Carbon::create(null, $i)->translatedFormat('F');
        }
        $tahunList = range(now()->year - 2, now()->year + 1);

        return view('tagihan.admin', compact('tagihans', 'users', 'bulanList', 'tahunList', 'filterBulan', 'filterTahun'));
    }

    public function confirm(Request $request)
    {
        $request->validate([
            'tagihan_id' => ['required', 'integer'],
            'status' => ['required', 'in:lunas,belum_bayar,ditolak'],
            'verification_note' => ['nullable', 'string', 'max:500'],
            'rejection_reason' => ['required_if:status,ditolak', 'nullable', 'string', 'min:5', 'max:500'],
        ], [
            'rejection_reason.required_if' => 'Alasan penolakan wajib diisi saat bukti pembayaran ditolak.',
            'rejection_reason.min' => 'Alasan penolakan minimal 5 karakter.',
        ]);

        $tagihan = Tagihan::findOrFail($request->tagihan_id);

        $oldValues = $tagihan->getOriginal();

        DB::transaction(function () use ($tagihan, $request, $oldValues) {
            if ($request->status === 'lunas') {
                $tagihan->status = 'lunas';
                $tagihan->verification_status = 'valid';
                $tagihan->verification_note = $request->verification_note;
                $tagihan->rejection_reason = null;
                $tagihan->rejected_at = null;
                $tagihan->rejected_by = null;
                $tagihan->verified_by = Auth::id();
                $tagihan->verified_at = now();
                $tagihan->paid_at = now();
                $tagihan->transaction_number ??= Tagihan::nextTransactionNumber();
            } elseif ($request->status === 'ditolak') {
                $tagihan->status = 'failed';
                $tagihan->verification_status = 'ditolak';
                $tagihan->verification_note = $request->verification_note;
                $tagihan->rejection_reason = $request->rejection_reason;
                $tagihan->rejected_at = now();
                $tagihan->rejected_by = Auth::id();
                $tagihan->verified_by = Auth::id();
                $tagihan->verified_at = now();
                $tagihan->paid_at = null;
            } else {
                $tagihan->status = 'belum_bayar';
                $tagihan->payment_method = 'none';
                $tagihan->bukti = null;
                $tagihan->note = null;
                $tagihan->verification_status = 'belum_dikirim';
                $tagihan->transaction_number = null;
                $tagihan->verification_note = null;
                $tagihan->rejection_reason = null;
                $tagihan->rejected_at = null;
                $tagihan->rejected_by = null;
                $tagihan->verified_by = null;
                $tagihan->verified_at = null;
                $tagihan->paid_at = null;
            }

            $tagihan->saveQuietly();
            $tagihan->recordAuditWithNote(
                'tagihan_status_updated',
                $oldValues,
                'Status tagihan diubah menjadi ' . $tagihan->status . ' dengan status bukti ' . $tagihan->verification_status
            );

            // Jika lunas, baru buat entri di KasMasuk
            if ($request->status === 'lunas') {
                KasMasuk::updateOrCreate(
                    ['tagihan_id' => $tagihan->id],
                    [
                        'user_id' => $tagihan->user_id,
                        'keterangan' => "Pembayaran " . $tagihan->display_title . " " . \Carbon\Carbon::create(null, $tagihan->bulan)->translatedFormat('F') . " " . $tagihan->tahun,
                        'jumlah' => $tagihan->total,
                        'tanggal' => now(),
                        'bukti' => $tagihan->bukti,
                    ]
                );
            } else {
                KasMasuk::where('tagihan_id', $tagihan->id)->delete();
            }
        });

        Cache::forget('admin.dashboard.stats');
        Cache::forget('admin.dashboard.stats.v2');
        Cache::forget('admin.dashboard.stats.v3');
        Cache::forget('dashboard.stats.user.' . $tagihan->user_id);

        return redirect()->route('tagihan.admin')->with('success', 'Status tagihan berhasil diperbarui.');
    }

    public function create(): View
    {
        $users = User::whereRelation('role', 'name', 'warga')->where('is_kepala_keluarga', true)->orderBy('name')->get();
        $bulanList = [];
        for ($i = 1; $i <= 12; $i++) {
            $bulanList[$i] = \Carbon\Carbon::create(null, $i)->translatedFormat('F');
        }
        $tahunList = range(now()->year - 2, now()->year + 1);

        return view('tagihan.create', compact('users', 'bulanList', 'tahunList'));
    }

    public function store(StoreTagihanRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::findOrFail($validated['user_id']);
        if ($user->role?->name !== 'warga' || !$user->is_kepala_keluarga) {
            return redirect()->back()->with('error', 'User harus kepala keluarga.');
        }

        $existing = Tagihan::where('bulan', $validated['bulan'])
            ->where('tahun', $validated['tahun'])
            ->where('billing_group', Tagihan::BILLING_GROUP_MANUAL)
            ->where(function ($query) use ($user) {
                if ($user->rumah_id) {
                    $query->where('rumah_id', $user->rumah_id)
                        ->orWhere('user_id', $user->id);
                } else {
                    $query->where('user_id', $user->id);
                }
            })
            ->first();

        if ($existing) {
            return redirect()->back()->with('error', 'Tagihan manual untuk warga, bulan, dan tahun ini sudah ada.');
        }

        $tagihan = new Tagihan([
            'user_id' => $user->id,
            'rumah_id' => $user->rumah_id,
            'bulan' => $validated['bulan'],
            'tahun' => $validated['tahun'],
            'billing_group' => Tagihan::BILLING_GROUP_MANUAL,
            'judul' => 'Tagihan Manual',
            'total' => $validated['total'],
            'status' => 'belum_bayar',
            'note' => $validated['note'] ?? null,
        ]);

        $tagihan->saveQuietly();
        $tagihan->recordAuditWithNote(
            'tagihan_created',
            [],
            'Tagihan baru dibuat untuk ' . $user->name
        );

        return redirect()->route('tagihan.admin')->with('success', 'Tagihan berhasil dibuat.');
    }

    public function edit(Tagihan $tagihan): View
    {
        $users = User::whereRelation('role', 'name', 'warga')->where('is_kepala_keluarga', true)->orderBy('name')->get();
        $bulanList = [];
        for ($i = 1; $i <= 12; $i++) {
            $bulanList[$i] = \Carbon\Carbon::create(null, $i)->translatedFormat('F');
        }
        $tahunList = range(now()->year - 2, now()->year + 1);

        return view('tagihan.edit', compact('tagihan', 'users', 'bulanList', 'tahunList'));
    }

    public function update(Request $request, Tagihan $tagihan): RedirectResponse
    {
        $request->validate([
            'total' => ['required', 'integer', 'min:1000'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $oldValues = $tagihan->getOriginal();

        $tagihan->fill([
            'total' => $request->total,
            'note' => $request->note,
        ]);

        $tagihan->saveQuietly();
        $tagihan->recordAuditWithNote(
            'tagihan_updated',
            $oldValues,
            'Tagihan diperbarui oleh admin'
        );

        return redirect()->route('tagihan.admin')->with('success', 'Tagihan berhasil diperbarui.');
    }

    public function destroy(Tagihan $tagihan): RedirectResponse
    {
        if ($tagihan->status === 'lunas') {
            return redirect()->back()->with('error', 'Tagihan yang sudah lunas tidak dapat dihapus.');
        }

        $tagihName = $tagihan->user->name . ' (' . $tagihan->bulan . '/' . $tagihan->tahun . ')';

        $oldValues = $tagihan->getAttributes();
        $tagihan->deleteQuietly();
        $tagihan->recordAuditWithNote(
            'tagihan_deleted',
            $oldValues,
            'Tagihan dihapus untuk ' . $tagihName
        );

        return redirect()->route('tagihan.admin')->with('success', "Tagihan '$tagihName' berhasil dihapus.");
    }

    private function canViewBukti(Tagihan $tagihan): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if ($user->canManageFinance()) {
            return true;
        }

        if ($tagihan->user_id === $user->id) {
            return true;
        }

        if ($tagihan->rumah_id && $user->rumah_id === $tagihan->rumah_id) {
            return true;
        }

        return false;
    }
}
