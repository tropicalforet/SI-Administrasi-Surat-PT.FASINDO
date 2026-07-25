<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
{
    // Mengizinkan 'admin', 'administrator', 'superadmin', 'dirut', dan 'sekretaris'
    $allowedRoles = ['admin', 'administrator', 'superadmin', 'dirut', 'sekretaris'];
    
    abort_unless(in_array(strtolower(auth()->user()->role), $allowedRoles), 403, 'Akses ditolak.');

        // Gunakan Eager Loading untuk relasi user
        $query = ActivityLog::with('user');

        // Filter Pencarian
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('aktivitas', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        // Filter User Spesifik
        if ($request->filled('user')) {
            $query->where('user_id', $request->user);
        }

        // Filter Range Tanggal
        if ($request->filled('tanggal_awal')) {
            $query->whereDate('created_at', '>=', $request->tanggal_awal);
        }

        if ($request->filled('tanggal_akhir')) {
            $query->whereDate('created_at', '<=', $request->tanggal_akhir);
        }

        // Eksekusi Query dengan Pagination
        $logs = $query->latest()->paginate(10)->withQueryString();

        // 2. OPTIMASI MEMORI: Hanya Select 'id' dan 'name' untuk kebutuhan dropdown HTML
        $users = User::select('id', 'name')->orderBy('name')->get();

        return view('activity.index', compact('logs', 'users'));
    }

    public function clear()
    {
        $role = strtolower(auth()->user()->role);
        
        abort_unless(in_array($role, ['admin', 'administrator', 'superadmin']), 403, 'Akses ditolak. Hanya administrator yang dapat menghapus log aktivitas.');

        // Hapus semua log
        ActivityLog::truncate();

        return redirect()->route('activity.index')->with('success', 'Semua riwayat log aktivitas berhasil dihapus secara permanen.');
    }
}