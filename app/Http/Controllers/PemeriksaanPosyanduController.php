<?php

namespace App\Http\Controllers;

use App\Models\Balita;
use App\Models\PemeriksaanPosyandu;
use App\Models\Rw;
use App\Models\User;
use App\Services\Posyandu\WeightForAgeCalculator;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PemeriksaanPosyanduController extends Controller
{
    public function __construct(private readonly WeightForAgeCalculator $calculator)
    {
    }

    public function store(Request $request, Balita $balita)
    {
        $this->authorizeRecord($request->user(), $balita);
        abort_unless($balita->is_active, 422, 'Balita nonaktif tidak dapat diperiksa.');
        $validated = $this->validatePemeriksaan($request, $balita);
        $result = $this->calculate($balita, $validated);

        $pemeriksaan = $balita->pemeriksaans()->create([
            ...$validated,
            ...$result->toArray(),
            'petugas_id' => $request->user()->id,
            'vitamin_a' => $request->boolean('vitamin_a'),
        ]);

        return redirect()->route('posyandu.show', $balita)
            ->with('success', "Pemeriksaan berhasil dicatat dengan Z-score {$pemeriksaan->z_score_bb_u}.");
    }

    public function update(Request $request, PemeriksaanPosyandu $pemeriksaan)
    {
        $pemeriksaan->loadMissing('balita');
        $this->authorizeRecord($request->user(), $pemeriksaan->balita);
        abort_unless($pemeriksaan->balita->is_active, 422, 'Balita nonaktif tidak dapat diperiksa.');
        $validated = $this->validatePemeriksaan($request, $pemeriksaan->balita, $pemeriksaan);
        $result = $this->calculate($pemeriksaan->balita, $validated);

        $pemeriksaan->update([
            ...$validated,
            ...$result->toArray(),
            'petugas_id' => $request->user()->id,
            'vitamin_a' => $request->boolean('vitamin_a'),
        ]);

        return back()->with('success', 'Pemeriksaan berhasil diperbarui dan Z-score dihitung ulang.');
    }

    public function destroy(Request $request, PemeriksaanPosyandu $pemeriksaan)
    {
        $pemeriksaan->loadMissing('balita');
        $this->authorizeRecord($request->user(), $pemeriksaan->balita);
        $pemeriksaan->delete();

        return back()->with('success', 'Pemeriksaan berhasil dihapus.');
    }

    private function validatePemeriksaan(
        Request $request,
        Balita $balita,
        ?PemeriksaanPosyandu $pemeriksaan = null,
    ): array {
        return $request->validate([
            'tanggal_pemeriksaan' => [
                'required',
                'date',
                'after_or_equal:'.$balita->tanggal_lahir->toDateString(),
                'before_or_equal:today',
                Rule::unique('pemeriksaan_posyandus', 'tanggal_pemeriksaan')
                    ->where('balita_id', $balita->id)
                    ->ignore($pemeriksaan?->id),
            ],
            'berat_kg' => ['required', 'numeric', 'between:0.5,40'],
            'panjang_tinggi_cm' => ['nullable', 'numeric', 'between:30,130'],
            'metode_ukur_tinggi' => [
                'nullable',
                'required_with:panjang_tinggi_cm',
                Rule::in(['terlentang', 'berdiri']),
            ],
            'lingkar_kepala_cm' => ['nullable', 'numeric', 'between:20,70'],
            'lingkar_lengan_cm' => ['nullable', 'numeric', 'between:5,40'],
            'vitamin_a' => ['nullable', 'boolean'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function calculate(Balita $balita, array $validated)
    {
        try {
            return $this->calculator->calculate(
                $balita->jenis_kelamin,
                $balita->tanggal_lahir,
                CarbonImmutable::parse($validated['tanggal_pemeriksaan']),
                (float) $validated['berat_kg'],
            );
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([
                'tanggal_pemeriksaan' => $exception->getMessage(),
            ]);
        }
    }

    private function authorizeRecord(User $user, Balita $balita): void
    {
        abort_unless($user->hasPermission('record-posyandu'), 403);
        abort_unless($balita->rw_id === $this->resolveRwId($user), 403);

        if (! $this->canAccessAllRts($user)) {
            abort_unless($balita->rt_id === $user->rt_id, 403);
        }
    }

    private function resolveRwId(User $user): int
    {
        $rwId = $user->rt()->value('rw_id');

        if (! $rwId && $this->canAccessAllRts($user)) {
            $rwId = Rw::where('is_active', true)->orderBy('id')->value('id');
        }

        abort_unless($rwId, 403, 'Akun belum terhubung ke wilayah.');

        return (int) $rwId;
    }

    private function canAccessAllRts(User $user): bool
    {
        return $user->canAccessAllRts()
            || $user->isGlobalOperator()
            || $user->role_name === 'petugas_posyandu';
    }
}
