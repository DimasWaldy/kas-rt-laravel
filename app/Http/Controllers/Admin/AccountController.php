<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\Rt;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController extends Controller
{
    private const RT_ROLES = ['ketua_rt', 'bendahara_rt', 'sekretaris_rt', 'bendahara', 'sekretaris'];

    private const RW_ROLES = ['ketua_rw', 'sekretaris_rw', 'bendahara_rw', 'petugas_bank_sampah', 'petugas_posyandu'];

    private const ALLOWED_ROLES = ['admin', ...self::RT_ROLES, ...self::RW_ROLES];

    public function create(Request $request): View
    {
        $this->authorizeSuperAdmin($request);

        $roles = Role::whereIn('name', self::ALLOWED_ROLES)->orderBy('name')->get();
        $rts = Rt::where('is_active', true)->whereHas('rw', fn ($query) => $query->where('is_active', true))
            ->with('rw:id,name')->orderBy('rw_id')->orderBy('name')->get();

        return view('admin.accounts.create', compact('roles', 'rts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'regex:/^[0-9]{10,13}$/'],
            'role' => ['required', Rule::in(self::ALLOWED_ROLES)],
            'rt_id' => ['required_unless:role,admin', 'nullable', 'integer', Rule::exists('rts', 'id')->where('is_active', true)],
        ]);

        $role = Role::where('name', $validated['role'])->firstOrFail();
        if ($role->name === 'admin' && filled($validated['rt_id'] ?? null)) {
            return back()->withErrors(['rt_id' => 'Admin memiliki akses global; jangan pilih RT.'])->withInput();
        }

        $rt = null;
        if ($role->name !== 'admin') {
            $rt = Rt::with('rw')->findOrFail($validated['rt_id']);
            if (! $rt->is_active || ! $rt->rw?->is_active) {
                return back()->withErrors(['rt_id' => 'Pilih RT aktif dalam RW aktif.'])->withInput();
            }
        }

        $user = DB::transaction(function () use ($request, $validated, $role, $rt) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Str::random(64),
                'role_id' => $role->id,
                'rt_id' => $rt?->id,
                'status_akun' => 'aktif',
            ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'auditable_type' => User::class,
                'auditable_id' => $user->id,
                'event' => 'akun_dibuat',
                'old_values' => [],
                'new_values' => ['role' => $role->name, 'rt_id' => $rt?->id],
                'notes' => 'Akun dibuat oleh super admin.',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $user;
        });

        try {
            $status = Password::sendResetLink(['email' => $user->email]);
        } catch (\Throwable $exception) {
            report($exception);
            $status = null;
        }

        return redirect()->route('admin.accounts.create')->with(
            $status === Password::RESET_LINK_SENT ? 'success' : 'warning',
            $status === Password::RESET_LINK_SENT
                ? 'Akun berhasil dibuat. Tautan pengaturan password telah dikirim ke email pemilik akun.'
                : 'Akun berhasil dibuat, tetapi tautan pengaturan password belum terkirim. Periksa layanan email lalu gunakan Lupa Password.'
        );
    }

    private function authorizeSuperAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAktif()
            && $request->user()->role_name === 'super_admin'
            && $request->user()->hasPermission('admin-only'), 403);
    }
}
