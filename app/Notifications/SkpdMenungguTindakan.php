<?php

namespace App\Notifications;

use App\Models\Skpd;
use Illuminate\Notifications\Notification;
use InvalidArgumentException;

class SkpdMenungguTindakan extends Notification
{
    /**
     * @param  string  $tindakan  'persetujuan_direktur' bagi direktur unit,
     *                            atau 'persetujuan_dirut' bagi Direktur Utama.
     */
    public function __construct(
        public Skpd $skpd,
        public string $tindakan = 'persetujuan_direktur'
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Penerima pemberitahuan ini adalah orang yang menerbitkan penugasannya.
     */
    private function diterbitkanPenerimaPemberitahuan(object $notifiable): bool
    {
        return isset($notifiable->id)
            && (int) $this->skpd->ditugaskan_oleh === (int) $notifiable->id;
    }

    public function toArray(object $notifiable): array
    {
        $pegawai = $this->skpd->user?->name ?? '-';

        /*
         * Tujuan diambil dari kolom tujuan_dinas. Sebelumnya tertulis
         * $skpd->tujuan - kolom yang tidak pernah ada pada tabel skpds -
         * sehingga pemberitahuannya berbunyi "penugasan ke ." tanpa tujuan.
         *
         * Nomor sengaja tidak disebut, karena pada tahap ini dokumennya
         * memang belum bernomor: nomor baru terbit saat Direktur Utama
         * menyetujui.
         */
        [$tipe, $judul, $pesan] = match ($this->tindakan) {
            'persetujuan_direktur' => [
                'skpd_perlu_persetujuan',
                'Usulan perjalanan dinas menunggu persetujuan',
                $pegawai . ' mengusulkan penugasan ke ' . $this->skpd->tujuan_dinas
                    . '. Setujui bila memang diperlukan.',
            ],
            /*
             * Penugasan yang diterbitkan Direktur Utama sendiri tidak lagi
             * memerlukan persetujuannya - keputusan itu sudah diambil saat
             * perintahnya dibuat. Yang tersisa hanyalah menandatangani, dan
             * pemberitahuannya menyebut demikian agar tidak terbaca seolah ia
             * menyetujui perintahnya sendiri.
             */
            'persetujuan_dirut' => $this->diterbitkanPenerimaPemberitahuan($notifiable)
                ? [
                    'skpd_perlu_persetujuan_dirut',
                    'SKPD menunggu tanda tangan Anda',
                    'Penugasan yang Anda terbitkan untuk ' . $pegawai
                        . ' ke ' . $this->skpd->tujuan_dinas
                        . ' siap ditandatangani secara elektronik.',
                ]
                : [
                    'skpd_perlu_persetujuan_dirut',
                    'SKPD menunggu persetujuan Anda',
                    'Pengajuan perjalanan dinas untuk ' . $pegawai
                        . ' ke ' . $this->skpd->tujuan_dinas
                        . ' menunggu persetujuan dan tanda tangan Anda.',
                ],
            default => throw new InvalidArgumentException(
                'Tindakan SKPD tidak dikenal: ' . $this->tindakan
            ),
        };

        return [
            'tipe'    => $tipe,
            'skpd_id' => $this->skpd->id,
            'judul'   => $judul,
            'pesan'   => $pesan,
            'url'     => route('skpd.show', $this->skpd->id),
        ];
    }
}
