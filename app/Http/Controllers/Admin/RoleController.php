<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Menu;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('menus')->get();
        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $menus = Menu::where('code', '!=', 'admin-dashboard')->get();
        return view('admin.roles.form', compact('menus'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles',
            'menus' => 'nullable|array',
        ]);

        $role = Role::create(['name' => $validated['name']]);
        if (isset($validated['menus'])) {
            $role->menus()->sync($validated['menus']);
        }

        return redirect()->route('admin.roles.index')->with('success', 'Role spesialisasi berhasil ditambahkan.');
    }

    public function edit(Role $role)
    {
        if (strtolower($role->name) === 'admin') {
            return redirect()->back()->with('error', 'Role master Admin terkunci (View Only) dan tidak dapat dimodifikasi!');
        }

        $menus = Menu::where('code', '!=', 'admin-dashboard')->get();
        $roleMenus = $role->menus->pluck('id')->toArray();
        return view('admin.roles.form', compact('role', 'menus', 'roleMenus'));
    }

    public function update(Request $request, Role $role)
    {
        if (strtolower($role->name) === 'admin') {
            return redirect()->back()->with('error', 'Role master Admin terkunci (View Only) dan tidak dapat dimodifikasi!');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'menus' => 'nullable|array',
        ]);

        $role->update(['name' => $validated['name']]);
        $role->menus()->sync($validated['menus'] ?? []);

        return redirect()->route('admin.roles.index')->with('success', 'Pengaturan Role berhasil diperbarui.');
    }

    public function destroy(Role $role)
    {
        if (strtolower($role->name) === 'admin') {
            return redirect()->back()->with('error', 'Role master Admin tidak boleh dihapus!');
        }
        
        $role->delete();
        return redirect()->route('admin.roles.index')->with('success', 'Role spesialisasi berhasil dihapus.');
    }
}
