<?php

namespace App\Notifications;

use App\Models\SuratKeluar;
use Illuminate\Notifications\Notification;
use InvalidArgumentException;

/**
 * Pemberitahuan atas keputusan yang sudah diambil terhadap sebuah surat.
 * Untuk mengabarkan giliran yang belum dikerjakan, pakai
 * SuratKeluarMenungguTindakan.
 */
class SuratKeluarDiputuskan extends Notification
{
    /**
     * @param  string  $keputusan  'disetujui' atau 'ditolak'.
     */
    public function __construct(
        public SuratKeluar $suratKeluar,
        public string $keputusan,
        public ?string $olehNama = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $nomor = $this->suratKeluar->label_nomor;
        $oleh = $this->olehNama ? ' oleh ' . $this->olehNama : '';

        // Setiap keputusan disebut tegas. Dulu arm terakhir berupa default,
        // sehingga keputusan yang tidak dikenal ikut berbunyi "dikembalikan"
        // dan pengajuan konsep terbaca sebagai penolakan di layar sekretaris.
        [$judul, $pesan] = match ($this->keputusan) {
            'disetujui' => [
                'Surat keluar disetujui',
                'Surat ' . $nomor . ' telah disetujui dan ditandatangani' . $oleh . '.',
            ],
            'ditolak' => [
                'Surat keluar dikembalikan untuk revisi',
                'Surat ' . $nomor . ' dikembalikan' . $oleh . '. Catatan: '
                    . ($this->suratKeluar->catatan_revisi ?: '-'),
            ],
            default => throw new InvalidArgumentException(
                'Keputusan surat keluar tidak dikenal: ' . $this->keputusan
            ),
        };

        return [
            'tipe'            => 'surat_keluar_' . $this->keputusan,
            'surat_keluar_id' => $this->suratKeluar->id,
            'judul'           => $judul,
            'pesan'           => $pesan,
            'url'             => route('surat-keluar.show', $this->suratKeluar->id),
        ];
    }
}
