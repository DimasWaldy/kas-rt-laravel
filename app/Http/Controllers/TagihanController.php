<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Tagihan;
use App\Models\IuranBulanan;
use App\Models\KasMasuk;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
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

    public function pay(Request $request)
    {
        $paymentMethod = $request->input('payment_method');

        $request->validate([
            'tagihan_id' => ['required', 'integer'],
            'payment_method' => ['required', 'in:transfer,offline'],
            'bukti' => [
                $paymentMethod === 'transfer' ? 'required' : 'nullable',
                'file',
                'mimes:jpeg,png,jpg,pdf',
                'max:3072'
            ],
            'note' => [
                $paymentMethod === 'offline' ? 'required' : 'nullable',
                'string',
                'min:5',
                'max:255'
            ],
        ], [
            'tagihan_id.required' => 'ID tagihan wajib ditentukan.',
            'payment_method.required' => 'Metode pembayaran wajib dipilih.',
            'payment_method.in' => 'Metode pembayaran harus berupa transfer atau offline.',
            'bukti.required' => 'Bukti pembayaran wajib diunggah untuk metode transfer.',
            'bukti.file' => 'Berkas bukti pembayaran tidak valid.',
            'bukti.mimes' => 'Format bukti pembayaran harus berupa gambar (jpeg, png, jpg) atau PDF.',
            'bukti.max' => 'Ukuran berkas bukti pembayaran maksimal adalah 3 MB (3072 KB).',
            'note.required' => 'Catatan wajib diisi untuk pembayaran offline (misal: diserahkan ke siapa & tanggal).',
            'note.min' => 'Catatan pembayaran offline minimal harus terdiri dari 5 karakter.',
            'note.max' => 'Catatan pembayaran maksimal berisi 255 karakter.',
        ]);

        $user = Auth::user();

        if ($user->rumah_id && ! $user->is_penanggung_jawab_rumah) {
            return redirect()->route('tagihan.index')->with('error', 'Hanya penanggung jawab rumah yang dapat membayar tagihan iuran.');
        }

        if (! $user->rumah_id && ! $user->is_kepala_keluarga) {
            return redirect()->route('tagihan.index')->with('error', 'Hanya kepala keluarga yang dapat membayar tagihan KK.');
        }

        $tagihan = Tagihan::where('id', $request->tagihan_id)
            ->when($user->rumah_id, fn($query) => $query->where('rumah_id', $user->rumah_id))
            ->when(! $user->rumah_id, fn($query) => $query->where('user_id', Auth::id()))
            ->firstOrFail();

        // Pastikan nominal tagihan valid
        if ($tagihan->total <= 0) {
            return redirect()->back()->with('error', 'Tagihan ini tidak memiliki nominal pembayaran.');
        }

        // Validasi: Jangan izinkan bayar jika sudah lunas atau sedang pending
        if (in_array($tagihan->status, ['lunas', 'pending_transfer', 'pending_offline'])) {
            return redirect()->back()->with('error', 'Tagihan ini sudah dibayar atau sedang dalam proses verifikasi.');
        }

        $oldValues = $tagihan->getOriginal();

        if ($paymentMethod === 'transfer') {
            $path = $request->file('bukti')->store('uploads', 'public');
            $tagihan->bukti = $path;
            $tagihan->status = 'pending_transfer';
            $tagihan->payment_method = 'transfer';
            $tagihan->note = $request->note;
        } else {
            $tagihan->bukti = null;
            $tagihan->status = 'pending_offline';
            $tagihan->payment_method = 'offline';
            $tagihan->note = $request->note;
        }

        $tagihan->verification_status = 'menunggu';
        $tagihan->verification_note = null;
        $tagihan->rejection_reason = null;
        $tagihan->verified_by = null;
        $tagihan->verified_at = null;
        $tagihan->transaction_number ??= Tagihan::nextTransactionNumber();
        $tagihan->save();

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => Tagihan::class,
            'auditable_id' => $tagihan->id,
            'event' => 'payment_submitted',
            'old_values' => $oldValues,
            'new_values' => $tagihan->getAttributes(),
            'notes' => 'Pembayaran diajukan via ' . $tagihan->payment_method,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Kirim Notifikasi ke Admin
        $admins = User::whereRelation('role', 'name', 'admin')->get();
        Notification::send($admins, new PaymentReceived($tagihan));

        return redirect()->route('tagihan.index')->with('success', 'Pembayaran tagihan telah dikirim. Tunggu konfirmasi RT.');
    }

    public function adminIndex()
    {
        if (!Auth::user()->canManageFinance()) {
            abort(403);
        }

        Auth::user()->unreadNotifications->markAsRead();

        $tagihans = Tagihan::with(['user', 'rumah', 'verifier'])
            ->orderByDesc('tahun')
            ->orderByDesc('bulan')
            ->get();

        return view('tagihan.admin', compact('tagihans'));
    }

    public function confirm(Request $request)
    {
        if (!Auth::user()->canManageFinance()) {
            abort(403);
        }

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
                $tagihan->verified_by = Auth::id();
                $tagihan->verified_at = now();
                $tagihan->paid_at = now();
                $tagihan->transaction_number ??= Tagihan::nextTransactionNumber();
            } elseif ($request->status === 'ditolak') {
                $tagihan->status = 'belum_bayar';
                $tagihan->verification_status = 'ditolak';
                $tagihan->verification_note = $request->verification_note;
                $tagihan->rejection_reason = $request->rejection_reason;
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
                $tagihan->verified_by = null;
                $tagihan->verified_at = null;
                $tagihan->paid_at = null;
            }

            $tagihan->save();

            AuditLog::create([
                'user_id' => Auth::id(),
                'auditable_type' => Tagihan::class,
                'auditable_id' => $tagihan->id,
                'event' => 'tagihan_status_updated',
                'old_values' => $oldValues,
                'new_values' => $tagihan->getAttributes(),
                'notes' => 'Status tagihan diubah menjadi ' . $tagihan->status . ' dengan status bukti ' . $tagihan->verification_status,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

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

        return redirect()->route('tagihan.admin')->with('success', 'Status tagihan berhasil diperbarui.');
    }

    public function create(): View
    {
        if (!Auth::user()->canManageFinance()) {
            abort(403);
        }

        $users = User::whereRelation('role', 'name', 'warga')->where('is_kepala_keluarga', true)->orderBy('name')->get();
        $bulanList = [];
        for ($i = 1; $i <= 12; $i++) {
            $bulanList[$i] = \Carbon\Carbon::create(null, $i)->translatedFormat('F');
        }
        $tahunList = range(now()->year - 2, now()->year + 1);

        return view('tagihan.create', compact('users', 'bulanList', 'tahunList'));
    }

    public function store(Request $request): RedirectResponse
    {
        if (!Auth::user()->canManageFinance()) {
            abort(403);
        }

        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'bulan' => ['required', 'integer', 'min:1', 'max:12'],
            'tahun' => ['required', 'integer', 'min:2020', 'max:' . (now()->year + 5)],
            'total' => ['required', 'integer', 'min:1000'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        // Check if tagihan sudah ada
        $existing = Tagihan::where('user_id', $request->user_id)
            ->where('bulan', $request->bulan)
            ->where('tahun', $request->tahun)
            ->first();

        if ($existing) {
            return redirect()->back()->with('error', 'Tagihan untuk bulan dan tahun ini sudah ada.');
        }

        $user = User::findOrFail($request->user_id);
        if ($user->role?->name !== 'warga' || !$user->is_kepala_keluarga) {
            return redirect()->back()->with('error', 'User harus kepala keluarga.');
        }

        $tagihan = Tagihan::create([
            'user_id' => $request->user_id,
            'bulan' => $request->bulan,
            'tahun' => $request->tahun,
            'billing_group' => 'manual_' . now()->timestamp,
            'judul' => 'Tagihan Manual',
            'total' => $request->total,
            'status' => 'belum_bayar',
            'note' => $request->note,
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => Tagihan::class,
            'auditable_id' => $tagihan->id,
            'event' => 'tagihan_created',
            'old_values' => [],
            'new_values' => $tagihan->getAttributes(),
            'notes' => 'Tagihan baru dibuat untuk ' . $user->name,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('tagihan.admin')->with('success', 'Tagihan berhasil dibuat.');
    }

    public function edit(Tagihan $tagihan): View
    {
        if (!Auth::user()->canManageFinance()) {
            abort(403);
        }

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
        if (!Auth::user()->canManageFinance()) {
            abort(403);
        }

        $request->validate([
            'total' => ['required', 'integer', 'min:1000'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $oldValues = $tagihan->getOriginal();

        $tagihan->update([
            'total' => $request->total,
            'note' => $request->note,
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => Tagihan::class,
            'auditable_id' => $tagihan->id,
            'event' => 'tagihan_updated',
            'old_values' => $oldValues,
            'new_values' => $tagihan->getAttributes(),
            'notes' => 'Tagihan diperbarui oleh admin',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('tagihan.admin')->with('success', 'Tagihan berhasil diperbarui.');
    }

    public function destroy(Tagihan $tagihan): RedirectResponse
    {
        if (! Auth::user()->canManageFinance()) {
            abort(403);
        }

        if ($tagihan->status === 'lunas') {
            return redirect()->back()->with('error', 'Tagihan yang sudah lunas tidak dapat dihapus.');
        }

        $tagihName = $tagihan->user->name . ' (' . $tagihan->bulan . '/' . $tagihan->tahun . ')';

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => Tagihan::class,
            'auditable_id' => $tagihan->id,
            'event' => 'tagihan_deleted',
            'old_values' => $tagihan->getAttributes(),
            'new_values' => [],
            'notes' => 'Tagihan dihapus untuk ' . $tagihName,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $tagihan->delete();

        return redirect()->route('tagihan.admin')->with('success', "Tagihan '$tagihName' berhasil dihapus.");
    }
}
