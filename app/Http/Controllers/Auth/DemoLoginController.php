<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemoLoginController extends Controller
{
    /**
     * Demo accounts available for quick access.
     *
     * @var array<string, array{label: string, email: string, icon: string, color: string, description: string}>
     */
    private const ACCOUNTS = [
        'superadmin' => [
            'label' => 'Super Admin',
            'email' => 'superadmin@smartrw.test',
            'role' => 'super_admin',
            'icon' => 'fa-shield-halved',
            'color' => 'rose',
            'description' => 'Akses penuh ke seluruh sistem',
        ],
        'admin' => [
            'label' => 'Admin RT',
            'email' => 'admin@smartrw.test',
            'role' => 'admin',
            'icon' => 'fa-user-gear',
            'color' => 'violet',
            'description' => 'Pengelola utama RT',
        ],
        'ketua_rt' => [
            'label' => 'Ketua RT',
            'email' => 'ketua.rt@smartrw.test',
            'role' => 'ketua_rt',
            'icon' => 'fa-user-tie',
            'color' => 'blue',
            'description' => 'Approve surat, pengaduan, aset',
        ],
        'sekretaris' => [
            'label' => 'Sekretaris RT',
            'email' => 'sekretaris.rt@smartrw.test',
            'role' => 'sekretaris_rt',
            'icon' => 'fa-file-signature',
            'color' => 'cyan',
            'description' => 'Administrasi warga & surat',
        ],
        'bendahara' => [
            'label' => 'Bendahara RT',
            'email' => 'bendahara.rt@smartrw.test',
            'role' => 'bendahara_rt',
            'icon' => 'fa-coins',
            'color' => 'amber',
            'description' => 'Kelola keuangan & iuran',
        ],
        'warga' => [
            'label' => 'Warga',
            'email' => 'warga.demo@smartrw.test',
            'role' => 'warga',
            'icon' => 'fa-house-user',
            'color' => 'emerald',
            'description' => 'Bayar tagihan, lapor, ajukan surat',
        ],
    ];

    public static function isEnabled(): bool
    {
        return config('app.demo_mode', false);
    }

    /**
     * @return array<string, array{label: string, email: string, icon: string, color: string, description: string}>
     */
    public static function accounts(): array
    {
        return self::ACCOUNTS;
    }

    public function __invoke(Request $request): RedirectResponse
    {
        if (! self::isEnabled()) {
            abort(404);
        }

        $request->validate([
            'account' => ['required', 'string', 'in:'.implode(',', array_keys(self::ACCOUNTS))],
        ]);

        $account = self::ACCOUNTS[$request->input('account')];

        $user = User::where('email', $account['email'])->first();

        if (! $user && isset($account['role'])) {
            $user = User::whereHas('role', function ($query) use ($account) {
                $roles = [$account['role']];
                if ($account['role'] === 'sekretaris_rt') {
                    $roles[] = 'sekretaris';
                }
                if ($account['role'] === 'bendahara_rt') {
                    $roles[] = 'bendahara';
                }
                $query->whereIn('name', $roles);
            })->where('status_akun', 'aktif')->first();
        }

        if (! $user) {
            return back()->with('error', 'Akun demo belum tersedia. Jalankan seeder terlebih dahulu.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
