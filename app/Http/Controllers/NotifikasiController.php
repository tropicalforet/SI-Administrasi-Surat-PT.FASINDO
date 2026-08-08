<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    public function index()
    {
        $notifikasi = auth()->user()
            ->notifications()
            ->paginate(15);

        return view('notifikasi.index', compact('notifikasi'));
    }

    /**
     * Tandai satu notifikasi sebagai dibaca lalu arahkan ke dokumen terkait.
     */
    public function baca(string $id)
    {
        $notifikasi = auth()->user()->notifications()->findOrFail($id);

        $notifikasi->markAsRead();

        return redirect($notifikasi->data['url'] ?? route('notifikasi.index'));
    }

    public function bacaSemua()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }

    public function destroy(string $id)
    {
        auth()->user()->notifications()->findOrFail($id)->delete();

        return back()->with('success', 'Notifikasi dihapus.');
    }
}
