<?php

namespace App\Notifications;

use App\Models\Skpd;
use Carbon\Carbon;
use Illuminate\Notifications\Notification;

/**
 * Perintah dinas yang sampai kepada pegawai yang ditugaskan.
 *
 * Berbeda dari SkpdDiputuskan, yang mengabarkan hasil atas sesuatu yang
 * diajukan sendiri oleh penerimanya. Pada penugasan, pegawai tidak pernah
 * mengajukan apa pun - kabar ini justru pemberitahuan pertama baginya bahwa
 * ia ditugaskan, sehingga bunyinya perlu menyebut siapa yang menugaskan,
 * ke mana, dan kapan.
 *
 * Dikirim saat Direktur Utama menandatangani, bukan saat penugasan disusun.
 * Sebelum ditandatangani perintahnya belum sah dan dokumennya pun belum
 * terbuka bagi pegawai, sehingga pemberitahuannya akan menunjuk halaman
 * yang menolaknya.
 */
class PenugasanDinasDiterima extends Notification
{
    public function __construct(public Skpd $skpd)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $penugas = $this->skpd->ditugaskanOleh;
        $oleh = $penugas
            ? $penugas->name . ' (' . $penugas->label_jabatan . ')'
            : 'pimpinan';

        return [
            'tipe'     => 'skpd_penugasan_diterima',
            'skpd_id'  => $this->skpd->id,
            'judul'    => 'Anda mendapat penugasan dinas',
            'pesan'    => $oleh . ' menugaskan Anda ke ' . $this->skpd->tujuan_dinas
                . ' pada ' . $this->rentangTanggal() . '. Keperluan: '
                . ($this->skpd->keperluan ?: '-')
                . '. Surat tugas ' . $this->skpd->nomor_skpd
                . ' sudah ditandatangani dan dapat Anda unduh.',
            'url'      => route('skpd.show', $this->skpd->id),
        ];
    }

    /**
     * "3 September 2026" bila sehari, "3 s/d 5 September 2026" bila lebih.
     */
    private function rentangTanggal(): string
    {
        $berangkat = Carbon::parse($this->skpd->tanggal_berangkat)->locale('id');
        $kembali = Carbon::parse($this->skpd->tanggal_kembali)->locale('id');

        if ($berangkat->isSameDay($kembali)) {
            return $berangkat->translatedFormat('d F Y');
        }

        // Bulan dan tahun cukup disebut sekali bila keduanya sama.
        if ($berangkat->isSameMonth($kembali)) {
            return $berangkat->translatedFormat('d') . ' s/d ' . $kembali->translatedFormat('d F Y');
        }

        return $berangkat->translatedFormat('d F Y') . ' s/d ' . $kembali->translatedFormat('d F Y');
    }
}
