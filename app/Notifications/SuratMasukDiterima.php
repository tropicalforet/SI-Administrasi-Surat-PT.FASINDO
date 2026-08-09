<?php

namespace App\Notifications;

use App\Models\SuratMasuk;
use Illuminate\Notifications\Notification;

class SuratMasukDiterima extends Notification
{
    public function __construct(public SuratMasuk $suratMasuk)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $ditujukanKeRole = !is_null($this->suratMasuk->penerima_role);

        return [
            'tipe'            => 'surat_masuk_diterima',
            'surat_masuk_id'  => $this->suratMasuk->id,
            'judul'           => 'Surat masuk baru untuk Anda',
            'pesan'           => 'Surat ' . $this->suratMasuk->nomor_surat
                . ' dari ' . $this->suratMasuk->pengirim
                . ($ditujukanKeRole
                    ? ' ditujukan kepada ' . $this->suratMasuk->label_penerima . '.'
                    : ' ditujukan kepada Anda.'),
            // Mengantar langsung ke suratnya, bukan ke daftar - penerima tidak
            // perlu mencari sendiri surat mana yang dimaksud notifikasi.
            'url'             => route('surat-masuk.show', $this->suratMasuk->id),
        ];
    }
}
