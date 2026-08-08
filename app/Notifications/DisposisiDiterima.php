<?php

namespace App\Notifications;

use App\Models\Disposisi;
use Illuminate\Notifications\Notification;

class DisposisiDiterima extends Notification
{
    public function __construct(public Disposisi $disposisi)
    {
    }

    /**
     * Kanal database dipakai agar notifikasi langsung terlihat di aplikasi.
     * Kanal mail belum diaktifkan karena MAIL_MAILER masih 'log'.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->disposisi->loadMissing(['suratMasuk', 'dariUser']);

        return [
            'tipe'           => 'disposisi_diterima',
            'disposisi_id'   => $this->disposisi->id,
            'judul'          => 'Disposisi baru untuk Anda',
            'pesan'          => 'Anda menerima disposisi surat '
                . ($this->disposisi->suratMasuk->nomor_surat ?? '-')
                . ' dari ' . ($this->disposisi->dariUser->name ?? '-') . '.',
            'batas_waktu'    => optional($this->disposisi->batas_waktu)->toDateString(),
            'url'            => route('disposisi.edit', $this->disposisi->id),
        ];
    }
}
