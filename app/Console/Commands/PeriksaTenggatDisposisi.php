<?php

namespace App\Console\Commands;

use App\Models\Disposisi;
use App\Notifications\DisposisiMendekatiTenggat;
use App\Notifications\DisposisiTerlambat;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PeriksaTenggatDisposisi extends Command
{
    protected $signature = 'disposisi:periksa-tenggat';

    protected $description = 'Kirim pengingat tenggat disposisi dan eskalasikan yang sudah terlambat';

    public function handle(): int
    {
        $hariIni = Carbon::today();

        $jumlahPengingat = $this->kirimPengingat($hariIni);
        $jumlahEskalasi = $this->kirimEskalasi($hariIni);

        $this->info("Pengingat terkirim : {$jumlahPengingat}");
        $this->info("Eskalasi terkirim  : {$jumlahEskalasi}");

        return self::SUCCESS;
    }

    /**
     * Ingatkan pelaksana untuk disposisi yang jatuh tempo hari ini atau besok.
     */
    private function kirimPengingat(Carbon $hariIni): int
    {
        $daftar = Disposisi::with(['suratMasuk', 'kepadaUser'])
            ->whereNotNull('batas_waktu')
            ->whereNull('diingatkan_pada')
            ->where('status', '!=', 'selesai')
            ->whereDate('batas_waktu', '>=', $hariIni)
            ->whereDate('batas_waktu', '<=', $hariIni->copy()->addDay())
            ->get();

        foreach ($daftar as $disposisi) {
            if ($disposisi->kepadaUser) {
                $disposisi->kepadaUser->notify(new DisposisiMendekatiTenggat($disposisi));
            }

            $disposisi->forceFill(['diingatkan_pada' => now()])->save();
        }

        return $daftar->count();
    }

    /**
     * Disposisi yang lewat tenggat diberitahukan ke pelaksana sekaligus
     * dieskalasikan ke pemberi disposisi.
     */
    private function kirimEskalasi(Carbon $hariIni): int
    {
        $daftar = Disposisi::with(['suratMasuk', 'kepadaUser', 'dariUser'])
            ->whereNotNull('batas_waktu')
            ->whereNull('dieskalasi_pada')
            ->where('status', '!=', 'selesai')
            ->whereDate('batas_waktu', '<', $hariIni)
            ->get();

        foreach ($daftar as $disposisi) {
            if ($disposisi->kepadaUser) {
                $disposisi->kepadaUser->notify(new DisposisiTerlambat($disposisi));
            }

            // Eskalasi: pemberi disposisi ikut diberi tahu, kecuali ia
            // mendisposisikan kepada dirinya sendiri.
            if ($disposisi->dariUser && $disposisi->dari_user_id !== $disposisi->kepada_user_id) {
                $disposisi->dariUser->notify(new DisposisiTerlambat($disposisi, true));
            }

            $disposisi->forceFill(['dieskalasi_pada' => now()])->save();
        }

        return $daftar->count();
    }
}
