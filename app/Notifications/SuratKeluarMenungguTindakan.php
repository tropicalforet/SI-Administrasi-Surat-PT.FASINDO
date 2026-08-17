<?php

namespace App\Notifications;

use App\Models\SuratKeluar;
use Illuminate\Notifications\Notification;
use InvalidArgumentException;

/**
 * Pemberitahuan bahwa sebuah surat keluar menunggu tindakan penerimanya.
 *
 * Berbeda dengan SuratKeluarDiputuskan yang mengabarkan keputusan yang sudah
 * diambil, kelas ini mengabarkan giliran: ada yang harus dikerjakan penerima.
 */
class SuratKeluarMenungguTindakan extends Notification
{
    /**
     * @param  string  $tindakan  'penomoran' bagi Sekretaris, atau
     *                            'persetujuan' bagi Direktur Utama.
     */
    public function __construct(
        public SuratKeluar $suratKeluar,
        public string $tindakan,
        public ?string $olehNama = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $oleh = $this->olehNama ? ' oleh ' . $this->olehNama : '';

        [$judul, $pesan] = match ($this->tindakan) {
            // Pada tahap ini surat belum bernomor, jadi yang dikenali penerima
            // adalah perihal dan tujuannya - bukan nomornya.
            'penomoran' => [
                'Surat keluar menunggu penomoran Anda',
                'Konsep "' . $this->suratKeluar->perihal . '" kepada '
                    . $this->suratKeluar->tujuan . ' diajukan' . $oleh
                    . ' dan menunggu penomoran serta pemeriksaan format.',
            ],
            'persetujuan' => [
                'Surat keluar menunggu persetujuan Anda',
                'Surat ' . $this->suratKeluar->label_nomor . ' kepada '
                    . $this->suratKeluar->tujuan
                    . ' sudah dinomori' . $oleh . ' dan menunggu persetujuan Anda.',
            ],
            default => throw new InvalidArgumentException(
                'Tindakan surat keluar tidak dikenal: ' . $this->tindakan
            ),
        };

        return [
            'tipe'            => 'surat_keluar_perlu_' . $this->tindakan,
            'surat_keluar_id' => $this->suratKeluar->id,
            'judul'           => $judul,
            'pesan'           => $pesan,
            'url'             => route('surat-keluar.show', $this->suratKeluar->id),
        ];
    }
}
