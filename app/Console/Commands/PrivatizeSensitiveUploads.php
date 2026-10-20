<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class PrivatizeSensitiveUploads extends Command
{
    protected $signature = 'storage:privatize-sensitive-uploads {--apply : Pindahkan setelah salinan privat terverifikasi}';

    protected $description = 'Audit/pindahkan upload sensitif lama dari disk public ke local tanpa mengubah database';

    public const DIRECTORIES = [
        'koperasi', 'pengaduan', 'tagihan-bukti', 'kaskeluar-bukti',
        'bank-sampah/setoran-bukti', 'surat', 'pengaduan-fasilitas',
    ];

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk('local');
        $apply = (bool) $this->option('apply');
        $count = 0;
        $failed = 0;

        if ($apply && realpath($public->path('')) === realpath($private->path(''))) {
            $this->error('Disk public dan local tidak boleh menggunakan lokasi yang sama.');
            return self::FAILURE;
        }

        $this->info($apply ? 'Mode pemindahan terverifikasi.' : 'DRY RUN: tidak ada file yang diubah.');
        foreach (self::DIRECTORIES as $directory) {
            foreach ($public->allFiles($directory) as $path) {
                $count++;
                if (! $apply) {
                    continue;
                }

                try {
                    $sourceHash = $this->checksum($public, $path);
                    if (! $private->exists($path)) {
                        $stream = $public->readStream($path);
                        if (! is_resource($stream)) {
                            throw new RuntimeException('File sumber tidak dapat dibaca.');
                        }
                        try {
                            if (! $private->put($path, $stream, ['visibility' => 'private'])) {
                                throw new RuntimeException('Salinan privat gagal ditulis.');
                            }
                        } finally {
                            fclose($stream);
                        }
                    }

                    if (! hash_equals($sourceHash, $this->checksum($private, $path))
                        || ! hash_equals($sourceHash, $this->checksum($public, $path))) {
                        throw new RuntimeException('Isi berbeda; kedua file dipertahankan untuk diperiksa.');
                    }

                    if (! $public->delete($path)) {
                        throw new RuntimeException('Salinan publik belum berhasil dilepas.');
                    }
                } catch (Throwable $exception) {
                    $failed++;
                    $this->error("Gagal: {$path}. {$exception->getMessage()}");
                }
            }
        }

        $this->info("File sensitif publik ditemukan: {$count}.");
        if ($apply) {
            $moved = $count - $failed;
            $this->info("Dipindahkan terverifikasi: {$moved}. Gagal: {$failed}.");
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function checksum(FilesystemAdapter $disk, string $path): string
    {
        $stream = $disk->readStream($path);
        if (! is_resource($stream)) {
            throw new RuntimeException('File tidak dapat dibaca untuk verifikasi.');
        }

        try {
            $hash = hash_init('sha256');
            if (hash_update_stream($hash, $stream) === false) {
                throw new RuntimeException('Verifikasi checksum gagal.');
            }
            return hash_final($hash);
        } finally {
            fclose($stream);
        }
    }
}
