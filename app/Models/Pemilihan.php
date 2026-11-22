<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Pemilihan extends Model
{
    use HasFactory;

    protected $fillable = [
        'rt_id',
        'rw_id',
        'title',
        'slug',
        'scope_type',
        'description',
        'start_date',
        'end_date',
        'status',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function rt()
    {
        return $this->belongsTo(Rt::class);
    }

    public function rw()
    {
        return $this->belongsTo(Rw::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function kandidats()
    {
        return $this->hasMany(PemilihanKandidat::class)->orderBy('nomor_urut');
    }

    public function voters()
    {
        return $this->hasMany(PemilihanVoter::class);
    }

    public function suaras()
    {
        return $this->hasMany(PemilihanSuara::class);
    }

    public function isOngoing(): bool
    {
        return $this->status === 'aktif' && now()->between($this->start_date, $this->end_date);
    }

    public function isFinished(): bool
    {
        return $this->status === 'selesai' || ($this->status === 'aktif' && now()->isAfter($this->end_date));
    }

    public function isUpcoming(): bool
    {
        return $this->status === 'aktif' && now()->isBefore($this->start_date);
    }

    public function hasUserVoted(?int $userId): bool
    {
        if (! $userId) {
            return false;
        }

        return $this->voters()->where('user_id', $userId)->exists();
    }

    /**
     * Check if a user is eligible to vote in this election.
     *
     * @return array{eligible: bool, reason: string|null, needs_rfid: bool}
     */
    public function checkUserEligibility(User $user): array
    {
        // 1. Check scope RT / RW
        if ($this->scope_type === 'rt' && $this->rt_id !== null) {
            if ($user->rt_id !== $this->rt_id) {
                return [
                    'eligible' => false,
                    'reason' => 'Anda tidak terdaftar di wilayah RT pemilihan ini.',
                    'needs_rfid' => false,
                ];
            }
        } elseif ($this->scope_type === 'rw' && $this->rw_id !== null) {
            $userRwId = $user->rt?->rw_id;
            if ($userRwId !== $this->rw_id) {
                return [
                    'eligible' => false,
                    'reason' => 'Anda tidak terdaftar di wilayah RW pemilihan ini.',
                    'needs_rfid' => false,
                ];
            }
        }

        // 2. Check e-KTP physical verification
        $hasVerifiedKtp = RfidCard::where('user_id', $user->id)
            ->where(fn ($query) => $query->whereNotNull('ktp_verified_at')->orWhereNotNull('koperasi_verified_at'))
            ->exists();

        if (! $hasVerifiedKtp) {
            return [
                'eligible' => false,
                'reason' => 'Hak suara terkunci: Anda wajib melakukan verifikasi fisik e-KTP melalui tap NFC smartphone atau alat reader RT terlebih dahulu.',
                'needs_rfid' => true,
            ];
        }

        // 3. Check election status
        if (! $this->isOngoing()) {
            if ($this->isUpcoming()) {
                return [
                    'eligible' => false,
                    'reason' => 'Bilik suara belum dibuka. Pemilihan dimulai pada '.$this->start_date->translatedFormat('d M Y H:i').' WIB.',
                    'needs_rfid' => false,
                ];
            }

            return [
                'eligible' => false,
                'reason' => 'Bilik suara telah ditutup. Periode pemilihan telah berakhir.',
                'needs_rfid' => false,
            ];
        }

        // 4. Check whether already voted
        if ($this->hasUserVoted($user->id)) {
            return [
                'eligible' => false,
                'reason' => 'Anda sudah menggunakan hak suara Anda pada pemilihan ini.',
                'needs_rfid' => false,
            ];
        }

        return [
            'eligible' => true,
            'reason' => null,
            'needs_rfid' => false,
        ];
    }

    /**
     * Compute quick count results.
     */
    public function quickCountResults(): Collection
    {
        $totalSuara = $this->suaras()->count();
        $voteCounts = $this->suaras()
            ->selectRaw('kandidat_id, count(*) as total')
            ->groupBy('kandidat_id')
            ->pluck('total', 'kandidat_id');

        $maxVotes = $voteCounts->max() ?? 0;

        return $this->kandidats->map(function (PemilihanKandidat $kandidat) use ($totalSuara, $voteCounts, $maxVotes) {
            $suara = (int) ($voteCounts[$kandidat->id] ?? 0);
            $persentase = $totalSuara > 0 ? round(($suara / $totalSuara) * 100, 1) : 0;
            $isLeading = $suara > 0 && $suara === $maxVotes;

            return [
                'id' => $kandidat->id,
                'nomor_urut' => $kandidat->nomor_urut,
                'nama_calon' => $kandidat->nama_calon,
                'nama_wakil' => $kandidat->nama_wakil,
                'slogan' => $kandidat->slogan,
                'visi' => $kandidat->visi,
                'misi' => $kandidat->misi,
                'foto_path' => $kandidat->foto_path,
                'total_suara' => $suara,
                'persentase' => $persentase,
                'is_leading' => $isLeading,
            ];
        });
    }
}
