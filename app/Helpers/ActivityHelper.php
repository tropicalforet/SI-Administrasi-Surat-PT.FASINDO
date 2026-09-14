<?php

namespace App\Helpers;

use App\Models\ActivityLog;

class ActivityHelper
{
    public static function log($aktivitas, $deskripsi)
    {
        ActivityLog::create([

            'user_id' => auth()->id(),

            // Nama direkam saat kejadian agar riwayat tetap menyebut pelakunya
            // walaupun penggunanya kelak dihapus.
            'nama_pengguna' => auth()->user()?->name,

            'aktivitas' => $aktivitas,

            'deskripsi' => $deskripsi

        ]);
    }
}