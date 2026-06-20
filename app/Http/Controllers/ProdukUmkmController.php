<?php

namespace App\Http\Controllers;

use App\Models\ProdukUmkm;
use App\Models\Rw;
use App\Models\Umkm;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProdukUmkmController extends Controller
{
    public function store(Request $request, Umkm $umkm)
    {
        $this->authorizeOwner($request->user(), $umkm);

        if ($umkm->status !== 'approved') {
            return back()->with('error', 'Produk hanya dapat ditambahkan setelah UMKM disetujui.');
        }

        $validated = $this->validateProduk($request);
        $fotoPath = $request->hasFile('foto')
            ? $request->file('foto')->store('umkm/produk', 'local')
            : null;

        unset($validated['foto']);

        $umkm->produkUmkms()->create([
            ...$validated,
            'foto' => $fotoPath,
            'is_available' => true,
        ]);

        return back()->with('success', 'Produk UMKM berhasil ditambahkan.');
    }

    public function update(Request $request, ProdukUmkm $produk)
    {
        $produk->loadMissing('umkm');
        $this->authorizeOwner($request->user(), $produk->umkm);

        $validated = $this->validateProduk($request);

        if ($request->hasFile('foto')) {
            if ($produk->foto) {
                Storage::disk('local')->delete($produk->foto);
            }

            $validated['foto'] = $request->file('foto')->store('umkm/produk', 'local');
        } else {
            unset($validated['foto']);
        }

        $produk->update($validated);

        return back()->with('success', 'Produk UMKM berhasil diperbarui.');
    }

    public function destroy(Request $request, ProdukUmkm $produk)
    {
        $produk->loadMissing('umkm');
        $this->authorizeOwner($request->user(), $produk->umkm);

        if ($produk->foto) {
            Storage::disk('local')->delete($produk->foto);
        }

        $produk->delete();

        return back()->with('success', 'Produk UMKM berhasil dihapus.');
    }

    public function toggleAvailability(Request $request, ProdukUmkm $produk)
    {
        $produk->loadMissing('umkm');
        $this->authorizeOwner($request->user(), $produk->umkm);

        $produk->update([
            'is_available' => ! $produk->is_available,
        ]);

        return back()->with('success', $produk->is_available
            ? 'Produk ditandai tersedia.'
            : 'Produk ditandai tidak tersedia.');
    }

    public function foto(Request $request, ProdukUmkm $produk)
    {
        $produk->loadMissing('umkm');
        $this->authorizeVisible($request->user(), $produk->umkm);
        abort_unless(
            $produk->foto && Storage::disk('local')->exists($produk->foto),
            404
        );

        return Storage::disk('local')->response($produk->foto);
    }

    private function validateProduk(Request $request): array
    {
        return $request->validate([
            'nama_produk' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:500'],
            'harga' => ['nullable', 'integer', 'min:0'],
            'satuan_harga' => ['nullable', 'string', 'max:50'],
            'foto' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);
    }

    private function authorizeOwner(User $user, Umkm $umkm): void
    {
        $this->authorizeSameRw($user, $umkm);
        abort_unless($umkm->pemilik_id === $user->id, 403);
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
}
