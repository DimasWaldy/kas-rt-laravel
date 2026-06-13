<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePengaduanRequest;
use App\Models\Pengaduan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PengaduanController extends Controller
{
    /**
     * Tampilkan daftar pengaduan warga.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Pengaduan::with(['user', 'responder'])->visibleTo($user);

        // Filter tab berdasarkan peran
        $filter = $request->query('filter', 'semua');

        if ($user->canManagePengaduan()) {
            // Admin bisa melihat semua, filter berdasarkan status
            if (in_array($filter, ['pending', 'proses', 'selesai', 'ditolak'])) {
                $query->where('status', $filter);
            }
        } else {
            // Warga melihat semua pengaduan di RT (untuk transparansi), 
            // atau bisa memfilter pengaduan miliknya sendiri.
            if ($filter === 'saya') {
                $query->where('user_id', $user->id);
            } elseif (in_array($filter, ['pending', 'proses', 'selesai', 'ditolak'])) {
                $query->where('status', $filter);
            }
        }

        $pengaduans = $query->latest()->paginate(10)->withQueryString();

        $rawStats = Pengaduan::visibleTo($user)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $stats = [
            'total' => (int) $rawStats->sum(),
            'pending' => (int) $rawStats->get('pending', 0),
            'proses' => (int) $rawStats->get('proses', 0),
            'selesai' => (int) $rawStats->get('selesai', 0),
        ];

        return view('pengaduan.index', compact('pengaduans', 'stats', 'filter'));
    }

    /**
     * Form buat pengaduan baru.
     */
    public function create()
    {
        return view('pengaduan.create');
    }

    /**
     * Simpan pengaduan baru ke database.
     */
    public function store(StorePengaduanRequest $request)
    {
        $validated = $request->validated();

        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->store('pengaduan', 'local');
        }

        Pengaduan::create([
            'user_id' => Auth::id(),
            'judul' => $validated['judul'],
            'kategori' => $validated['kategori'],
            'deskripsi' => $validated['deskripsi'],
            'foto' => $fotoPath,
            'status' => 'pending',
        ]);

        return redirect('/pengaduan')->with('success', 'Aduan/aspirasi Anda berhasil dikirim dan akan segera diproses oleh pengurus RT.');
    }

    /**
     * Tampilkan detail pengaduan.
     */
    public function show(Pengaduan $pengaduan)
    {
        abort_unless($pengaduan->isVisibleTo(Auth::user()), 404);

        $pengaduan->load(['user', 'responder']);
        return view('pengaduan.show', compact('pengaduan'));
    }

    public function foto(Pengaduan $pengaduan)
    {
        abort_unless(Auth::check(), 403);
        abort_unless($pengaduan->isVisibleTo(Auth::user()), 404);

        if (! $pengaduan->foto) {
            abort(404);
        }

        $disk = Storage::disk('local')->exists($pengaduan->foto) ? 'local' : 'public';

        abort_unless(Storage::disk($disk)->exists($pengaduan->foto), 404);

        return response()->file(Storage::disk($disk)->path($pengaduan->foto), [
            'Content-Disposition' => 'inline; filename="' . basename($pengaduan->foto) . '"',
        ]);
    }

    /**
     * Update status pengaduan oleh admin (pending -> proses -> selesai/ditolak) beserta tanggapan.
     */
    public function updateStatus(Request $request, Pengaduan $pengaduan)
    {
        abort_unless($pengaduan->isVisibleTo(Auth::user()), 404);

        $request->validate([
            'status' => 'required|string|in:pending,proses,selesai,ditolak',
            'tanggapan' => 'required|string|min:5',
        ], [
            'status.required' => 'Status wajib ditentukan.',
            'tanggapan.required' => 'Tanggapan/solusi wajib diisi agar warga mendapatkan kepastian.',
            'tanggapan.min' => 'Tanggapan minimal berisi 5 karakter.',
        ]);

        $pengaduan->update([
            'status' => $request->status,
            'tanggapan' => $request->tanggapan,
            'tanggapan_oleh' => Auth::id(),
            'tanggapan_at' => now(),
        ]);

        return redirect()->route('pengaduan.show', $pengaduan)->with('success', 'Status pengaduan berhasil diperbarui dan tanggapan telah dikirim.');
    }

    /**
     * Hapus pengaduan.
     */
    public function destroy(Pengaduan $pengaduan)
    {
        $user = Auth::user();

        abort_unless($pengaduan->isVisibleTo($user), 404);

        // Warga hanya boleh menghapus pengaduan miliknya sendiri yang masih bertatus 'pending'
        if (!$user->canManagePengaduan()) {
            if ($pengaduan->user_id !== $user->id) {
                abort(403, 'Anda tidak memiliki hak untuk menghapus pengaduan ini.');
            }
            if ($pengaduan->status !== 'pending') {
                return redirect()->route('pengaduan.show', $pengaduan)->with('error', 'Pengaduan yang sedang diproses atau sudah selesai tidak dapat dihapus.');
            }
        }

        // Hapus foto dari storage jika ada
        if ($pengaduan->foto) {
            Storage::disk('local')->delete($pengaduan->foto);
            Storage::disk('public')->delete($pengaduan->foto);
        }

        $pengaduan->delete();

        return redirect('/pengaduan')->with('success', 'Pengaduan berhasil dihapus.');
    }
}
