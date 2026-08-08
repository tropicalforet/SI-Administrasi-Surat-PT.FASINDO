<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Permission;
use App\Helpers\ActivityHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Role yang boleh disimpan. Mencegah role sembarangan masuk lewat
     * manipulasi form, karena role menentukan hak akses di seluruh sistem.
     */
    private const ALLOWED_ROLES = [
        'admin',
        'administrator',
        'superadmin',
        'sekretaris',
        'dirut',
        'direktur1',
        'direktur2',
        'staff',
    ];

    /**
     * Tampilkan daftar pengguna.
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Pencarian nama atau email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Gunakan pagination untuk efisiensi memori
        $data = $query->latest()->paginate(10)->withQueryString();

        return view('users.index', compact('data'));
    }

    /**
     * Tampilkan form tambah pengguna.
     */
    public function create()
    {
        $permissions = Permission::orderBy('group')->get()->groupBy('group');
        return view('users.create', compact('permissions'));
    }

    /**
     * Simpan pengguna baru ke database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role'     => ['required', 'in:' . implode(',', self::ALLOWED_ROLES)],
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => $validated['role'],
        ]);

        // Sync permissions (jika ada)
        if ($request->has('permissions')) {
            $user->permissions()->sync($request->input('permissions', []));
        }

        ActivityHelper::log('Tambah User', 'Menambahkan pengguna baru: ' . $user->name . ' (' . $user->role . ')');

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    /**
     * Tampilkan form edit pengguna.
     */
    public function edit(User $user)
    {
        $permissions = Permission::orderBy('group')->get()->groupBy('group');
        $userPermissions = $user->permissions()->pluck('permissions.id')->toArray();
        return view('users.edit', compact('user', 'permissions', 'userPermissions'));
    }

    /**
     * Simpan pembaruan data pengguna.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role'  => ['required', 'in:' . implode(',', self::ALLOWED_ROLES)],
        ]);

        // PROTEKSI: Mencegah admin mengubah role akunnya sendiri, karena
        // menurunkan role sendiri akan menghilangkan akses ke halaman ini
        // dan tidak ada cara memulihkannya dari dalam aplikasi.
        if ($user->id === auth()->id() && $validated['role'] !== $user->role) {
            return redirect()->route('users.index')->with('error', 'Aksi ditolak: Anda tidak dapat mengubah role akun Anda sendiri.');
        }

        $data = [
            'name'  => $validated['name'],
            'email' => $validated['email'],
            'role'  => $validated['role'],
        ];

        // Password hanya diubah jika field tidak kosong
        if ($request->filled('password')) {
            $request->validate(['password' => 'min:6']);
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        // Sync permissions
        $user->permissions()->sync($request->input('permissions', []));

        ActivityHelper::log('Edit User', 'Memperbarui data pengguna: ' . $user->name);

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui.');
    }

    /**
     * Hapus pengguna dari database.
     */
    public function destroy(User $user)
    {
        // PROTEKSI: Mencegah admin menghapus akunnya sendiri
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', 'Aksi ditolak: Anda tidak dapat menghapus akun Anda sendiri.');
        }

        ActivityHelper::log('Hapus User', 'Menghapus pengguna: ' . $user->name);
        
        $user->delete();

        return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
    }
}