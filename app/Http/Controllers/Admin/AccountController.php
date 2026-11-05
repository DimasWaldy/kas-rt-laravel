<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountController extends Controller
{
    private const RT_ROLES = ['ketua_rt', 'bendahara_rt', 'sekretaris_rt'];

    private const RW_ROLES = ['ketua_rw', 'sekretaris_rw', 'bendahara_rw', 'petugas_bank_sampah', 'petugas_posyandu'];

    private const ALLOWED_ROLES = ['admin', ...self::RT_ROLES, ...self::RW_ROLES];

    public function create(Request $request): View
    {
        $this->authorizeAccountManager($request);

        $roles = Role::whereIn('name', self::ALLOWED_ROLES)->orderBy('name')->get();
        $rts = Rt::where('is_active', true)->whereHas('rw', fn ($query) => $query->where('is_active', true))
            ->with('rw:id,name')->orderBy('rw_id')->orderBy('name')->get();
        $rws = Rw::where('is_active', true)->orderBy('name')->get();

        return view('admin.accounts.create', compact('roles', 'rts', 'rws'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAccountManager($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'regex:/^[0-9]{10,13}$/'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => ['required', Rule::in(self::ALLOWED_ROLES)],
            'rt_id' => ['required_if:role,'.implode(',', self::RT_ROLES), 'nullable', 'integer', Rule::exists('rts', 'id')->where('is_active', true)],
            'rw_id' => ['required_if:role,'.implode(',', self::RW_ROLES), 'nullable', 'integer', Rule::exists('rws', 'id')->where('is_active', true)],
        ]);

        $role = Role::where('name', $validated['role'])->firstOrFail();
        $isRtRole = in_array($role->name, self::RT_ROLES, true);
        $isRwRole = in_array($role->name, self::RW_ROLES, true);
        if (! $isRtRole && filled($validated['rt_id'] ?? null)) {
            return back()->withErrors(['rt_id' => 'Role ini tidak boleh memiliki RT penempatan.'])->withInput($request->except('password', 'password_confirmation'));
        }
        if (! $isRwRole && filled($validated['rw_id'] ?? null)) {
            return back()->withErrors(['rw_id' => 'Role ini tidak boleh memiliki RW penempatan.'])->withInput($request->except('password', 'password_confirmation'));
        }

        $rt = $isRtRole ? Rt::with('rw')->findOrFail($validated['rt_id']) : null;
        if ($rt && (! $rt->is_active || ! $rt->rw?->is_active)) {
            return back()->withErrors(['rt_id' => 'Pilih RT aktif dalam RW aktif.'])->withInput($request->except('password', 'password_confirmation'));
        }
        $rw = $isRwRole ? Rw::where('is_active', true)->findOrFail($validated['rw_id']) : null;

        DB::transaction(function () use ($request, $validated, $role, $rt, $rw) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => $validated['password'],
                'role_id' => $role->id,
                'rt_id' => $rt?->id,
                'rw_id' => $rw?->id,
                'status_akun' => 'aktif',
            ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'auditable_type' => User::class,
                'auditable_id' => $user->id,
                'event' => 'akun_dibuat',
                'old_values' => [],
                'new_values' => ['role' => $role->name, 'rt_id' => $rt?->id, 'rw_id' => $rw?->id],
                'notes' => 'Akun dibuat oleh operator.',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return redirect()->route('admin.accounts.create')->with('success', 'Akun berhasil dibuat. Pemilik akun dapat mengganti password melalui Edit Profil.');
    }

    private function authorizeAccountManager(Request $request): void
    {
        abort_unless($request->user()?->isAktif()
            && $request->user()->isGlobalOperator()
            && $request->user()->hasPermission('admin-only'), 403);
    }
}
