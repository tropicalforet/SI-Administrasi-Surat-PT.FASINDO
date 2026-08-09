<?php

namespace App\Notifications;

use App\Models\Disposisi;
use Illuminate\Notifications\Notification;

class DisposisiMendekatiTenggat extends Notification
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
            'tipe'         => 'disposisi_mendekati_tenggat',
            'disposisi_id' => $this->disposisi->id,
            'judul'        => 'Tenggat disposisi sudah dekat',
            'pesan'        => 'Disposisi surat '
                . ($this->disposisi->suratMasuk->nomor_surat ?? '-')
                . ' jatuh tempo pada '
                . optional($this->disposisi->batas_waktu)->translatedFormat('d F Y') . '.',
            'batas_waktu'  => optional($this->disposisi->batas_waktu)->toDateString(),
            'url'          => route('disposisi.edit', $this->disposisi->id),
        ];
    }
}
