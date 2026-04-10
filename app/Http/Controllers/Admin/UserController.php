<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('role')->get();
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();
        return view('admin.users.form', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'role_id' => ['nullable', 'exists:roles,id'],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        User::create($validated);

        return redirect()->route('admin.users.index')->with('success', 'Registrasi pengguna baru berhasil.');
    }

    public function edit(User $user)
    {
        if ($user->email === 'admin@email.com' && auth()->user()->email !== 'admin@email.com') {
            abort(403, 'Akses Ditolak: Hanya Super Admin yang berhak merubah profilnya sendiri.');
        }

        $roles = Role::all();
        return view('admin.users.form', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        if ($user->email === 'admin@email.com' && auth()->user()->email !== 'admin@email.com') {
            abort(403, 'Akses Ditolak: Hanya Super Admin yang berhak merubah profilnya sendiri.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role_id' => ['nullable', 'exists:roles,id'],
        ]);

        if ($user->email === 'admin@email.com' && $validated['email'] !== 'admin@email.com') {
            return redirect()->back()->with('error', 'Tindakan Terlarang: Akun master Super Admin ("admin@email.com") tidak diizinkan mengubah email.');
        }

        if ($user->email === 'admin@email.com' && $request->role_id != $user->role_id) {
            return redirect()->back()->with('error', 'Tindakan Terlarang: Anda tidak bisa menurunkan derajat/jabatan role Super Admin.');
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role_id = $validated['role_id'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('admin.users.index')->with('success', 'Profil pengguna tersebut berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->email === 'admin@email.com') {
            return redirect()->back()->with('error', 'Tindakan Terlarang: Sistem memproteksi penghapusan entitas tunggal Super Admin.');
        }

        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'Pengguna telah berhasil dikeluarkan dari sistem.');
    }
}
