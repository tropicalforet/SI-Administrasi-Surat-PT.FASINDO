<?php

namespace App\Helpers;

use App\Models\ActivityLog;

class ActivityHelper
{
    public static function log($aktivitas, $deskripsi)
    {
        ActivityLog::create([

            'user_id' => auth()->id(),

            'aktivitas' => $aktivitas,

            'deskripsi' => $deskripsi

        ]);
    }
}