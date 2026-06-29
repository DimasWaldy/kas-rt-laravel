<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Rumah;
use App\Models\User;
use App\Models\Warga;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        $rumahs = Rumah::query()
            ->where('status', 'aktif')
            ->orderBy('kode_rumah')
            ->get(['id', 'kode_rumah', 'alamat']);

        return view('auth.register', compact('rumahs'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => ['required', 'regex:/^[0-9]{10,13}$/'],
            'rumah_id' => ['required_without:rumah_baru_alamat', 'nullable', 'exists:rumahs,id'],
            'rumah_baru_alamat' => ['required_without:rumah_id', 'nullable', 'string', 'max:255'],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'status_dalam_kk' => ['required', 'in:kepala_keluarga,anggota'],
            'nik' => ['nullable', 'digits:16', 'unique:wargas,nik'],
            'dokumen_kk' => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:2048'],
            'dokumen_ktp' => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:2048'],
        ], [
            'phone.regex' => 'Nomor HP harus berisi angka saja, minimal 10 digit dan maksimal 13 digit.',
            'rumah_id.required_without' => 'Pilih rumah atau isi alamat rumah yang belum terdaftar.',
            'rumah_baru_alamat.required_without' => 'Isi alamat rumah atau pilih rumah yang sudah terdaftar.',
            'nik.digits' => 'NIK harus berisi 16 digit angka.',
            'dokumen_kk.mimes' => 'Dokumen KK harus berupa JPEG, PNG, JPG, atau PDF.',
            'dokumen_ktp.mimes' => 'Dokumen KTP harus berupa JPEG, PNG, JPG, atau PDF.',
        ]);

        $rumah = filled($validated['rumah_id'] ?? null)
            ? Rumah::findOrFail($validated['rumah_id'])
            : null;

        $dokumenKkPath = null;
        $dokumenKtpPath = null;

        try {
            $dokumenKkPath = $request->file('dokumen_kk')?->store('verifikasi-warga', 'local');
            $dokumenKtpPath = $request->file('dokumen_ktp')?->store('verifikasi-warga', 'local');

            $user = DB::transaction(function () use (
                $validated,
                $rumah,
                $dokumenKkPath,
                $dokumenKtpPath
            ) {
                $role = Role::firstOrCreate(
                    ['name' => 'warga'],
                    ['description' => 'Warga']
                );

                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'phone' => $validated['phone'],
                    'role_id' => $role->id,
                    'rumah_id' => $rumah?->id,
                    'rt_id' => $rumah?->rt_id,
                    'status_akun' => 'pending_verifikasi',
                ]);

                Warga::create([
                    'user_id' => $user->id,
                    'kartu_keluarga_id' => null,
                    'nik' => $validated['nik'] ?? null,
                    'nama_lengkap' => $validated['nama_lengkap'],
                    'status_dalam_kk' => $validated['status_dalam_kk'],
                    'status_verifikasi' => 'pending',
                    'metode_verifikasi' => $dokumenKkPath || $dokumenKtpPath
                        ? 'dokumen'
                        : null,
                    'dokumen_kk' => $dokumenKkPath,
                    'dokumen_ktp' => $dokumenKtpPath,
                    'rumah_diajukan' => $rumah
                        ? null
                        : $validated['rumah_baru_alamat'],
                    'rumah_diajukan_id' => $rumah?->id,
                ]);

                return $user;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete(array_filter([
                $dokumenKkPath,
                $dokumenKtpPath,
            ]));

            throw $exception;
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('verifikasi.menunggu');
    }
}
