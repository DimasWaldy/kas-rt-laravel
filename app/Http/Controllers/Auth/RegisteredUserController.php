<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'no_kk' => ['required', 'digits:16'],
            'is_kepala_keluarga' => ['nullable', 'boolean'],
            'jumlah_anggota_keluarga' => ['required', 'integer', 'min:1', 'max:20'],
            'phone' => ['required', 'regex:/^[0-9]{10,13}$/'],
            'rt' => ['required', 'string', 'max:10'],
            'rw' => ['required', 'string', 'max:10'],
        ], [
            'no_kk.digits' => 'Nomor KK harus berisi 16 digit angka.',
            'phone.regex' => 'Nomor HP harus berisi angka saja, minimal 10 digit dan maksimal 13 digit.',
        ]);

        $role = Role::firstOrCreate(
            ['name' => 'warga'],
            ['description' => 'Warga']
        );

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $role->id,
            'no_kk' => $request->no_kk,
            'is_kepala_keluarga' => $request->boolean('is_kepala_keluarga'),
            'jumlah_anggota_keluarga' => $request->jumlah_anggota_keluarga,
            'phone' => $request->phone,
            'rt' => $request->rt,
            'rw' => $request->rw,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
