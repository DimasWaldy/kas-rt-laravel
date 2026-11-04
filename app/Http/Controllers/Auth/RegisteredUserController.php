<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\Warga;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
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
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => ['required', 'regex:/^[0-9]{10,13}$/'],
            'rumah_id' => ['prohibited'],
            'rt_id' => ['prohibited'],
        ], [
            'email.unique' => 'Email sudah terdaftar. Masuk dengan akun yang sama untuk melihat status atau memperbaiki pendaftaran. Jika lupa password, gunakan Lupa Password.',
            'phone.regex' => 'Nomor HP harus berisi angka saja, minimal 10 digit dan maksimal 13 digit.',
            'rumah_id.prohibited' => 'Domisili diisi di dalam aplikasi setelah akun diverifikasi.',
            'rt_id.prohibited' => 'RT ditetapkan oleh pengurus saat verifikasi akun.',
        ]);

        $user = DB::transaction(function () use ($validated) {
            $role = Role::firstOrCreate(
                ['name' => 'warga'],
                ['description' => 'Warga']
            );

            $user = new User([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'],
                'role_id' => $role->id,
                'status_akun' => 'pending_verifikasi',
            ]);
            $user->onboarding_domisili = true;
            $user->save();

            Warga::create([
                'user_id' => $user->id,
                'nama_lengkap' => $validated['name'],
                'status_verifikasi' => 'pending',
            ]);

            return $user;
        });

        try {
            event(new Registered($user));
        } catch (\Throwable $e) {
            report($e);
        }

        session()->flash('status', 'Pendaftaran berhasil! Masuk untuk memantau status verifikasi pengurus. Setelah disetujui, lanjutkan pengajuan domisili. Tidak perlu verifikasi email.');

        return redirect()->route('login');
    }
}
