<?php

namespace App\Http\Controllers;

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
        $query = Pengaduan::with(['user', 'responder']);

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

        // Hitung statistik untuk dashboard pengaduan
        $stats = [
            'total' => Pengaduan::count(),
            'pending' => Pengaduan::where('status', 'pending')->count(),
            'proses' => Pengaduan::where('status', 'proses')->count(),
            'selesai' => Pengaduan::where('status', 'selesai')->count(),
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
    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'kategori' => 'required|string|in:Keamanan,Kebersihan,Infrastruktur,Sosial,Lainnya',
            'deskripsi' => 'required|string',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ], [
            'judul.required' => 'Judul pengaduan wajib diisi.',
            'kategori.required' => 'Kategori wajib dipilih.',
            'deskripsi.required' => 'Deskripsi aduan wajib ditulis.',
            'foto.image' => 'Berkas bukti harus berupa gambar.',
            'foto.mimes' => 'Format gambar harus jpeg, png, atau jpg.',
            'foto.max' => 'Ukuran foto maksimal adalah 2MB.',
        ]);

        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->store('pengaduan', 'public');
        }

        Pengaduan::create([
            'user_id' => Auth::id(),
            'judul' => $request->judul,
            'kategori' => $request->kategori,
            'deskripsi' => $request->deskripsi,
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
        $pengaduan->load(['user', 'responder']);
        return view('pengaduan.show', compact('pengaduan'));
    }

    /**
     * Update status pengaduan oleh admin (pending -> proses -> selesai/ditolak) beserta tanggapan.
     */
    public function updateStatus(Request $request, Pengaduan $pengaduan)
    {
        abort_if(!Auth::user()->canManagePengaduan(), 403, 'Akses ditolak. Hanya pengurus RT/Sekretaris yang dapat memberikan tanggapan.');

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
            Storage::disk('public')->delete($pengaduan->foto);
        }

        $pengaduan->delete();

        return redirect('/pengaduan')->with('success', 'Pengaduan berhasil dihapus.');
    }
}
