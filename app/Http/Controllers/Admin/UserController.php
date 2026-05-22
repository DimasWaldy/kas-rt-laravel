<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $usersQuery = User::whereRelation('role', 'name', 'warga');

        if ($search = trim($request->input('search'))) {
            $usersQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('no_kk', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('rt', 'like', "%{$search}%")
                    ->orWhere('rw', 'like', "%{$search}%");
            });
        }

        if ($request->filled('filter_head')) {
            if ($request->input('filter_head') === 'kepala') {
                $usersQuery->where('is_kepala_keluarga', true);
            } elseif ($request->input('filter_head') === 'warga') {
                $usersQuery->where('is_kepala_keluarga', false);
            }
        }

        $users = $usersQuery->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.warga.index', compact('users'));
    }

    public function edit(User $user): View
    {
        if ($user->role_name !== 'warga') {
            abort(404);
        }

        return view('admin.warga.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if ($user->role_name !== 'warga') {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'no_kk' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:25'],
            'rt' => ['nullable', 'string', 'max:5'],
            'rw' => ['nullable', 'string', 'max:5'],
            'jumlah_anggota_keluarga' => ['nullable', 'integer', 'min:0'],
            'is_kepala_keluarga' => ['nullable', 'boolean'],
        ]);

        $validated['is_kepala_keluarga'] = $request->boolean('is_kepala_keluarga');

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return redirect()->route('admin.warga.index')->with('success', 'Data warga berhasil diperbarui.');
    }

    public function create(): View
    {
        return view('admin.warga.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'no_kk' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:25'],
            'rt' => ['nullable', 'string', 'max:5'],
            'rw' => ['nullable', 'string', 'max:5'],
            'jumlah_anggota_keluarga' => ['nullable', 'integer', 'min:0'],
            'is_kepala_keluarga' => ['nullable', 'boolean'],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['role_id'] = Role::firstOrCreate(
            ['name' => 'warga'],
            ['description' => 'Warga']
        )->id;
        $validated['is_kepala_keluarga'] = $request->boolean('is_kepala_keluarga');

        User::create($validated);

        return redirect()->route('admin.warga.index')->with('success', 'Warga baru berhasil ditambahkan.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->role_name !== 'warga') {
            abort(404);
        }

        $userName = $user->name;
        $user->delete();

        return redirect()->route('admin.warga.index')->with('success', "Warga '{$userName}' berhasil dihapus.");
    }
}
