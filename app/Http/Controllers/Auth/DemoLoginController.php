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
            'description' => 'Pilih 30 warga demo',
        ],
    ];

    /**
     * 30 Demo Warga accounts (14 teman + 16 warga random).
     *
     * @var array<int, array{name: string, email: string, rt: string, is_friend: bool}>
     */
    public const DEMO_WARGA = [
        ['name' => 'Yazid', 'email' => 'warga.demo@smartrw.test', 'rt' => 'RT 01', 'is_friend' => true],
        ['name' => 'Julius Wisnu Broto', 'email' => 'julius@smartrw.test', 'rt' => 'RT 01', 'is_friend' => true],
        ['name' => 'Shaifullah Shadik', 'email' => 'shaifullah@smartrw.test', 'rt' => 'RT 01', 'is_friend' => true],
        ['name' => 'Fabian', 'email' => 'fabian@smartrw.test', 'rt' => 'RT 01', 'is_friend' => true],
        ['name' => 'Halim Hafis', 'email' => 'halim@smartrw.test', 'rt' => 'RT 01', 'is_friend' => true],
        ['name' => 'Riki Ridwan', 'email' => 'riki@smartrw.test', 'rt' => 'RT 01', 'is_friend' => true],
        ['name' => 'Fadli', 'email' => 'fadli@smartrw.test', 'rt' => 'RT 01', 'is_friend' => true],
        ['name' => 'Reival', 'email' => 'reival@smartrw.test', 'rt' => 'RT 01', 'is_friend' => true],
        ['name' => 'Juan', 'email' => 'juan@smartrw.test', 'rt' => 'RT 01', 'is_friend' => true],
        ['name' => 'Dimas', 'email' => 'dimas@smartrw.test', 'rt' => 'RT 01', 'is_friend' => true],
        ['name' => 'Rafi', 'email' => 'rafi@smartrw.test', 'rt' => 'RT 01', 'is_friend' => true],
        ['name' => 'Fendi', 'email' => 'fendi@smartrw.test', 'rt' => 'RT 02', 'is_friend' => true],
        ['name' => 'Firman', 'email' => 'firman@smartrw.test', 'rt' => 'RT 02', 'is_friend' => true],
        ['name' => 'Toni', 'email' => 'toni@smartrw.test', 'rt' => 'RT 02', 'is_friend' => true],
        // 15-30: 16 Warga random
        ['name' => 'Nadia Maharani', 'email' => 'nadia@smartrw.test', 'rt' => 'RT 02', 'is_friend' => false],
        ['name' => 'Budi Santoso', 'email' => 'budi@smartrw.test', 'rt' => 'RT 02', 'is_friend' => false],
        ['name' => 'Siti Aminah', 'email' => 'siti@smartrw.test', 'rt' => 'RT 02', 'is_friend' => false],
        ['name' => 'Agus Prasetyo', 'email' => 'agus@smartrw.test', 'rt' => 'RT 02', 'is_friend' => false],
        ['name' => 'Dewi Lestari', 'email' => 'dewi@smartrw.test', 'rt' => 'RT 02', 'is_friend' => false],
        ['name' => 'Rudi Hartono', 'email' => 'rudi@smartrw.test', 'rt' => 'RT 02', 'is_friend' => false],
        ['name' => 'Maya Permata', 'email' => 'maya@smartrw.test', 'rt' => 'RT 02', 'is_friend' => false],
        ['name' => 'Hendra Wijaya', 'email' => 'hendra@smartrw.test', 'rt' => 'RT 03', 'is_friend' => false],
        ['name' => 'Nina Kartika', 'email' => 'nina@smartrw.test', 'rt' => 'RT 03', 'is_friend' => false],
        ['name' => 'Fajar Nugroho', 'email' => 'fajar@smartrw.test', 'rt' => 'RT 03', 'is_friend' => false],
        ['name' => 'Lina Marlina', 'email' => 'lina@smartrw.test', 'rt' => 'RT 03', 'is_friend' => false],
        ['name' => 'Dedi Kurniawan', 'email' => 'dedi@smartrw.test', 'rt' => 'RT 03', 'is_friend' => false],
        ['name' => 'Ratna Sari', 'email' => 'ratna@smartrw.test', 'rt' => 'RT 03', 'is_friend' => false],
        ['name' => 'Yusuf Maulana', 'email' => 'yusuf@smartrw.test', 'rt' => 'RT 03', 'is_friend' => false],
        ['name' => 'Indah Pertiwi', 'email' => 'indah@smartrw.test', 'rt' => 'RT 03', 'is_friend' => false],
        ['name' => 'Taufik Hidayat', 'email' => 'taufik@smartrw.test', 'rt' => 'RT 03', 'is_friend' => false],
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

    /**
     * @return array<int, array{name: string, email: string, rt: string, is_friend: bool}>
     */
    public static function wargaAccounts(): array
    {
        return self::DEMO_WARGA;
    }

    public function __invoke(Request $request): RedirectResponse
    {
        if (! self::isEnabled()) {
            abort(404);
        }

        $request->validate([
            'account' => ['required', 'string', 'in:'.implode(',', array_keys(self::ACCOUNTS))],
            'email' => ['nullable', 'string'],
        ]);

        $accountKey = $request->input('account');
        $account = self::ACCOUNTS[$accountKey];

        // If specific email is passed for warga demo, use it
        $targetEmail = ($accountKey === 'warga' && $request->filled('email'))
            ? $request->input('email')
            : $account['email'];

        $user = User::where('email', $targetEmail)->first();

        // Fallback for warga if specific email not found
        if (! $user && $accountKey === 'warga') {
            $user = User::where('email', $account['email'])->first();
        }

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
