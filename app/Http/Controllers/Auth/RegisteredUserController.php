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
use Illuminate\Validation\Rule;
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
        return view('auth.register', [
            'rumahs' => $this->activeRumahs(),
        ]);
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
            'rumah_id' => [
                'required',
                'integer',
                Rule::exists('rumahs', 'id')->where(fn ($query) => $query
                    ->where('status', 'aktif')
                    ->whereNotNull('rt_id')),
            ],
        ], [
            'phone.regex' => 'Nomor HP harus berisi angka saja, minimal 10 digit dan maksimal 13 digit.',
            'rumah_id.required' => 'Domisili/rumah wajib dipilih.',
            'rumah_id.exists' => 'Rumah/domisili yang dipilih tidak tersedia atau belum terhubung ke RT.',
        ]);

        $user = DB::transaction(function () use ($validated) {
            $role = Role::firstOrCreate(
                ['name' => 'warga'],
                ['description' => 'Warga']
            );

            $rumah = Rumah::query()->findOrFail($validated['rumah_id']);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'],
                'role_id' => $role->id,
                'rumah_id' => $rumah->id,
                'rt_id' => $rumah->rt_id,
                'status_akun' => 'pending_verifikasi',
            ]);

            Warga::create([
                'user_id' => $user->id,
                'nama_lengkap' => $validated['name'],
                'rumah_diajukan_id' => $rumah->id,
                'status_verifikasi' => 'pending',
            ]);

            return $user;
        });

        event(new Registered($user));

        session()->flash('status', 'Pendaftaran berhasil! Akun Anda sedang menunggu aktivasi dari pengurus RT. Anda akan mendapat kabar setelah akun diaktifkan.');

        return redirect()->route('login');
    }

    private function activeRumahs()
    {
        return Rumah::query()
            ->where('status', 'aktif')
            ->orderBy('kode_rumah')
            ->orderBy('alamat')
            ->get(['id', 'kode_rumah', 'alamat', 'rt_id']);
    }
}
