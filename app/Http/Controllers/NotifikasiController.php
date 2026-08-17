<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    public function index()
    {
        // Yang belum dibaca selalu di atas, supaya tidak tenggelam di antara
        // notifikasi lama. reorder() wajib: relasi notifications() bawaan
        // Laravel sudah menyisipkan latest(), yang kalau dibiarkan membuat
        // urutan waktu menang dan aturan ini cuma jadi pemecah seri.
        // CASE WHEN dipakai agar jalan di MySQL maupun SQLite.
        $notifikasi = auth()->user()
            ->notifications()
            ->reorder()
            ->orderByRaw('CASE WHEN read_at IS NULL THEN 0 ELSE 1 END')
            ->orderByDesc('created_at')
            ->paginate(15);

        $belumDibaca = auth()->user()->unreadNotifications()->count();

        return view('notifikasi.index', compact('notifikasi', 'belumDibaca'));
    }

    /**
     * Tandai satu notifikasi sebagai dibaca lalu arahkan ke dokumen terkait.
     */
    public function baca(string $id)
    {
        $notifikasi = auth()->user()->notifications()->findOrFail($id);

        $notifikasi->markAsRead();

        $tujuan = $notifikasi->data['url'] ?? null;

        // Notifikasi bertahan lebih lama daripada modul yang melahirkannya.
        // Peninggalan modul Surat Tugas, misalnya, masih menyimpan tautan ke
        // rute yang sudah dilepas - tanpa penjagaan ini tombol Buka berujung
        // halaman 404 yang tidak menjelaskan apa pun.
        if (!$this->tujuanMasihAda($tujuan)) {
            return redirect()->route('notifikasi.index')
                ->with('error', 'Dokumen yang dirujuk notifikasi ini sudah tidak tersedia.');
        }

        return redirect($tujuan);
    }

    private function tujuanMasihAda(?string $url): bool
    {
        if (!$url) {
            return false;
        }

        try {
            app('router')->getRoutes()->match(Request::create($url, 'GET'));

            return true;
        } catch (\Throwable $e) {
            return false;
        }
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
