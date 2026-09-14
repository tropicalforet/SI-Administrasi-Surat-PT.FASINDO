<?php

namespace App\Notifications;

use App\Models\Disposisi;
use Illuminate\Notifications\Notification;

/**
 * Dikirim kepada pemberi disposisi ketika penerimanya menyatakan pekerjaannya
 * rampung. Tanpa kabar ini, giliran berpindah tanpa ada yang tahu, dan
 * disposisi menggantung di tahap verifikasi sampai kebetulan dibuka.
 */
class DisposisiMenungguVerifikasi extends Notification
{
    public function __construct(public Disposisi $disposisi)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $penerima = $this->disposisi->label_penerima;
        $perihal = $this->disposisi->suratMasuk?->perihal ?: 'surat masuk';

        return [
            'tipe'         => 'disposisi_menunggu_verifikasi',
            'disposisi_id' => $this->disposisi->id,
            'judul'        => 'Tindak lanjut menunggu verifikasi Anda',
            'pesan'        => $penerima . ' menyatakan tindak lanjut atas "' . $perihal
                . '" telah rampung. Periksa hasilnya, lalu setujui atau kembalikan bila belum sesuai.',
            'url'          => route('disposisi.monitoring.show', $this->disposisi->id),
        ];
    }
}
