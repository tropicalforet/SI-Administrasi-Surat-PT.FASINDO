<?php

namespace App\Notifications;

use App\Models\Disposisi;
use Illuminate\Notifications\Notification;

class DisposisiSiapDikonfirmasi extends Notification
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
        $this->disposisi->loadMissing('suratMasuk');

        return [
            'tipe'         => 'disposisi_siap_dikonfirmasi',
            'disposisi_id' => $this->disposisi->id,
            'judul'        => 'Disposisi lanjutan sudah selesai',
            'pesan'        => 'Seluruh disposisi lanjutan untuk surat '
                . ($this->disposisi->suratMasuk->nomor_surat ?? '-')
                . ' sudah selesai. Silakan periksa hasilnya lalu tandai disposisi Anda selesai.',
            'batas_waktu'  => optional($this->disposisi->batas_waktu)->toDateString(),
            'url'          => route('disposisi.edit', $this->disposisi->id),
        ];
    }
}
