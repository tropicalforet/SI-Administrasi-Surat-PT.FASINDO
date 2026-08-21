<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Disposisi;
use App\Models\SuratMasuk;
use App\Helpers\ActivityHelper;
use App\Notifications\DisposisiDiterima;
use App\Notifications\DisposisiSiapDikonfirmasi;
use App\Notifications\DisposisiMenungguVerifikasi;
use App\Notifications\DisposisiHasilVerifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // <-- Tambahan wajib untuk Transaction
use Illuminate\Support\Facades\Storage; // <-- Dipakai saat mengganti file tindak lanjut

class DisposisiController extends Controller
{
    /**
     * Mendisposisikan surat mensyaratkan hak membacanya lebih dulu.
     *
     * Tanpa penjagaan ini, siapa pun yang punya izin disposisi dapat membuka
     * surat yang bukan urusannya hanya dengan menebak nomor id - halaman
     * formnya sendiri sudah menampilkan pengirim dan perihal surat.
     */
    private function pastikanBolehMendisposisikan(SuratMasuk $suratMasuk): void
    {
        if (!$suratMasuk->bolehDibacaOleh(auth()->user())) {
            abort(403, 'Akses ditolak. Surat ini bukan kewenangan Anda.');
        }
    }

    public function create(SuratMasuk $suratMasuk)
    {
        $this->pastikanBolehMendisposisikan($suratMasuk);

        $users = $this->tujuanDisposisi(auth()->user());

        if ($users->isEmpty() && !in_array(strtolower(auth()->user()->role), ['dirut', 'sekretaris'])) {
            return back()->with('error', $this->alasanTujuanKosong(auth()->user()));
        }

        return view('disposisi.create', compact('suratMasuk', 'users'));
    }

    /**
     * Daftar orang yang boleh dituju seorang pengguna saat mendisposisikan.
     *
     * Dirut dan sekretaris berada di pimpinan sehingga menjangkau lintas
     * direktorat. Direktur dan manager hanya menjangkau bawahannya dalam
     * satu unit kerja, agar disposisi tidak menyeberang garis komando.
     */
    private function tujuanDisposisi(User $user)
    {
        return match (strtolower($user->role)) {
            'dirut'      => User::whereIn('role', ['direktur1', 'direktur2', 'sekretaris'])
                                ->orderBy('role')->orderBy('name')->get(),
            'sekretaris' => User::whereIn('role', ['dirut', 'direktur1', 'direktur2'])
                                ->orderBy('role')->orderBy('name')->get(),
            default      => $user->bawahanSeunit(),
        };
    }

    /**
     * Pastikan tujuan disposisi memang berada dalam jangkauan pengirim.
     * Tanpa ini, daftar tujuan di formulir hanya pembatas tampilan dan masih
     * bisa dilewati dengan mengirim id pengguna lain lewat POST.
     */
    private function aturanTujuanSah(): callable
    {
        $diizinkan = $this->tujuanDisposisi(auth()->user())->pluck('id');

        return function ($attribute, $value, $fail) use ($diizinkan) {
            if (!$diizinkan->contains((int) $value)) {
                $fail('Pegawai yang dipilih berada di luar jangkauan disposisi Anda.');
            }
        };
    }

    /**
     * Pesan yang menjelaskan mengapa tidak ada tujuan yang tersedia, supaya
     * pengguna tahu apa yang harus dibereskan alih-alih melihat daftar kosong.
     */
    private function alasanTujuanKosong(User $user): string
    {
        if (empty($user->rolesBawahan())) {
            return 'Anda tidak memiliki akses untuk membuat disposisi.';
        }

        if (!$user->unit) {
            return 'Unit kerja akun Anda belum ditetapkan. Hubungi administrator agar disposisi dapat diteruskan.';
        }

        return 'Belum ada pegawai di unit ' . $user->label_unit . ' yang dapat dituju. Hubungi administrator untuk menetapkan unit kerja pegawai.';
    }

    public function store(Request $request)
    {
        $request->validate([
            'surat_masuk_id' => 'required|exists:surat_masuks,id',
            'kepada_user_id' => 'required|array|min:1',
            'kepada_user_id.*' => ['exists:users,id', $this->aturanTujuanSah()],
            'instruksi'      => 'required|string',
            'batas_waktu'    => 'nullable|date|after_or_equal:today',
        ]);

        // Form boleh saja tidak pernah ditampilkan, tetapi POST langsung tetap
        // harus ditolak - di sinilah disposisi benar-benar tercipta.
        $this->pastikanBolehMendisposisikan(
            SuratMasuk::findOrFail($request->surat_masuk_id)
        );

        // Gunakan DB Transaction agar penyimpanan disposisi dan log berjalan bersamaan (aman)
        DB::transaction(function () use ($request) {
            foreach ($request->kepada_user_id as $userId) {
                $disposisi = Disposisi::create([
                    'surat_masuk_id'    => $request->surat_masuk_id,
                    'dari_user_id'      => auth()->id(),
                    'kepada_user_id'    => $userId,
                    'instruksi'         => $request->instruksi,
                    'status'            => 'menunggu',
                    'tanggal_disposisi' => now(),
                    'batas_waktu'       => $request->batas_waktu,
                ]);

                $disposisi->kepadaUser->notify(new DisposisiDiterima($disposisi));

                ActivityHelper::log(
                    'Membuat Disposisi',
                    'Mendisposisikan surat ' . ($disposisi->suratMasuk?->nomor_surat ?? '-') . ' kepada ' . $disposisi->kepadaUser->name
                );
            }

            SuratMasuk::find($request->surat_masuk_id)?->segarkanStatus();
        });

        return redirect()
            ->route('surat-masuk.index')
            ->with('success', 'Disposisi berhasil dikirim');
    }

    public function index()
    {
        $data = Disposisi::with([
                'suratMasuk',
                'dariUser',
                'kepadaUser',
                'children.kepadaUser',
                'children.dariUser'
            ])
            ->where('kepada_user_id', auth()->id())
            ->latest()
            ->paginate(10); // <-- Ubah get() menjadi paginate(10) untuk efisiensi

        return view('disposisi.index', compact('data'));
    }

    public function edit(Disposisi $disposisi)
    {
        // Pengecekan keamanan: Hanya yang dituju yang boleh edit
        if ($disposisi->kepada_user_id != auth()->id()) {
            abort(403, 'Akses ditolak. Ini bukan disposisi Anda.');
        }

        $timeline = Disposisi::with(['dariUser', 'kepadaUser', 'suratMasuk'])
            ->where('surat_masuk_id', $disposisi->surat_masuk_id)
            ->orderBy('tanggal_disposisi', 'asc')
            ->get();

        return view('disposisi.edit', compact('disposisi', 'timeline'));
    }

    public function update(Request $request, Disposisi $disposisi)
    {
        // Penerima tidak lagi dapat menutup sendiri disposisinya. Yang ia
        // lakukan adalah menyatakan pekerjaannya rampung; penutupannya
        // menjadi keputusan pihak yang memberi perintah.
        abort_unless($disposisi->bolehDitindaklanjutiOleh(auth()->user()), 403);

        $request->validate([
            'status'                => 'required|in:' . implode(',', Disposisi::STATUS_PENERIMA),
            'catatan_tindak_lanjut' => 'nullable|string',
            'file_tindak_lanjut'    => 'nullable|mimes:pdf,jpg,jpeg,png,doc,docx,zip,xls,xlsx|max:5120',
        ]);

        // Pekerjaan tidak boleh dinyatakan rampung selama disposisi lanjutannya
        // belum selesai, agar status yang dibaca atasan mencerminkan kenyataan.
        if ($request->status === 'menunggu_verifikasi' && $disposisi->punyaAnakBelumSelesai()) {
            return back()->with('error', 'Disposisi belum dapat diselesaikan karena masih ada disposisi lanjutan yang berjalan.');
        }

        $filePath = $disposisi->file_tindak_lanjut;

        if ($request->hasFile('file_tindak_lanjut')) {
            // Hapus file lama jika ada
            if ($filePath && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
            $filePath = $request->file('file_tindak_lanjut')->store('disposisi_laporan', 'public');
        }

        DB::transaction(function () use ($request, $disposisi, $filePath) {
            $disposisi->update([
                'status'                => $request->status,
                'catatan_tindak_lanjut' => $request->catatan_tindak_lanjut,
                'file_tindak_lanjut'    => $filePath,
                // Catatan pengembalian yang lama dibersihkan begitu pekerjaannya
                // diperbaiki, supaya tidak terbaca seolah masih berlaku.
                'catatan_verifikasi'    => null,
            ]);

            ActivityHelper::log(
                'Update Disposisi',
                auth()->user()->name . ' mengubah status disposisi surat '
                    . ($disposisi->suratMasuk?->nomor_surat ?? '-') . ' menjadi ' . $disposisi->label_status
            );

            if ($disposisi->menungguVerifikasi() && $disposisi->dariUser) {
                $disposisi->dariUser->notify(new DisposisiMenungguVerifikasi($disposisi));
            }

            $this->sinkronkanInduk($disposisi);

            $disposisi->suratMasuk?->segarkanStatus();
        });

        return redirect()
            ->route('disposisi.saya')
            ->with('success', $disposisi->menungguVerifikasi()
                ? 'Tindak lanjut dikirim kepada ' . $disposisi->label_pengirim . ' untuk diverifikasi.'
                : 'Tindak lanjut berhasil disimpan.');
    }

    /**
     * Pemberi disposisi menyatakan hasil kerja penerimanya sudah sesuai.
     * Di sinilah disposisi benar-benar ditutup.
     */
    public function verifikasi(Disposisi $disposisi)
    {
        abort_unless($disposisi->bolehDiverifikasiOleh(auth()->user()), 403);

        if ($disposisi->punyaAnakBelumSelesai()) {
            return back()->with('error', 'Masih ada disposisi lanjutan yang berjalan, sehingga belum dapat ditutup.');
        }

        DB::transaction(function () use ($disposisi) {
            $disposisi->update(['status' => 'selesai']);
            $disposisi->forceFill(['diverifikasi_pada' => now()])->save();

            ActivityHelper::log(
                'Verifikasi Disposisi',
                auth()->user()->name . ' memverifikasi tindak lanjut '
                    . $disposisi->label_penerima . ' dan menutup disposisi'
            );

            if ($disposisi->kepadaUser) {
                $disposisi->kepadaUser->notify(
                    new DisposisiHasilVerifikasi($disposisi, 'diterima', auth()->user()->name)
                );
            }

            $this->sinkronkanInduk($disposisi);

            $disposisi->suratMasuk?->segarkanStatus();
        });

        return back()->with('success', 'Tindak lanjut diverifikasi. Disposisi dinyatakan selesai.');
    }

    /**
     * Pemberi disposisi menilai hasilnya belum sesuai, sehingga pekerjaannya
     * dikembalikan beserta catatan apa yang perlu diperbaiki.
     */
    public function kembalikan(Request $request, Disposisi $disposisi)
    {
        abort_unless($disposisi->bolehDiverifikasiOleh(auth()->user()), 403);

        $request->validate([
            'catatan_verifikasi' => 'required|string',
        ], [
            'catatan_verifikasi.required' => 'Sebutkan apa yang perlu diperbaiki.',
        ]);

        DB::transaction(function () use ($request, $disposisi) {
            $disposisi->update([
                'status'             => 'diproses',
                'catatan_verifikasi' => $request->catatan_verifikasi,
            ]);

            ActivityHelper::log(
                'Kembalikan Disposisi',
                auth()->user()->name . ' mengembalikan tindak lanjut '
                    . $disposisi->label_penerima . ' dengan catatan: ' . $request->catatan_verifikasi
            );

            if ($disposisi->kepadaUser) {
                $disposisi->kepadaUser->notify(
                    new DisposisiHasilVerifikasi($disposisi, 'dikembalikan', auth()->user()->name)
                );
            }

            $disposisi->suratMasuk?->segarkanStatus();
        });

        return back()->with('success', 'Tindak lanjut dikembalikan kepada ' . $disposisi->label_penerima . '.');
    }

    /**
     * Selaraskan disposisi induk dengan perkembangan disposisi lanjutannya.
     *
     * Induk naik ke 'diproses' begitu ada pergerakan di bawahnya, tetapi
     * penutupan menjadi 'selesai' tetap keputusan penerima induk. Ketika
     * seluruh anak sudah selesai, penerima induk diberi tahu satu kali agar
     * rantai tidak menggantung karena lupa dikonfirmasi.
     */
    private function sinkronkanInduk(Disposisi $disposisi): void
    {
        $induk = $disposisi->parent;

        if (!$induk) {
            return;
        }

        if ($induk->status === 'menunggu') {
            $induk->update(['status' => 'diproses']);
        }

        if ($induk->status !== 'selesai'
            && is_null($induk->siap_konfirmasi_pada)
            && $induk->semuaAnakSelesai()) {

            if ($induk->kepadaUser) {
                $induk->kepadaUser->notify(new DisposisiSiapDikonfirmasi($induk));
            }

            $induk->forceFill(['siap_konfirmasi_pada' => now()])->save();
        }
    }

    /**
     * Monitoring memakai daftar yang sama dengan Laporan Disposisi.
     *
     * Sebelumnya administrator diloloskan middleware tetapi ditolak di sini,
     * sehingga menunya terlihat lalu berujung 403 saat diklik. Perbandingannya
     * juga tidak menormalkan huruf besar-kecil seperti bagian lain sistem.
     */
    private function pastikanBolehMemantau(): void
    {
        if (!Disposisi::bolehLihatLaporan(auth()->user())) {
            abort(403, 'Akses khusus Direktur Utama dan Sekretaris.');
        }
    }

    public function monitoring()
    {
        $this->pastikanBolehMemantau();

        $data = Disposisi::with([
                'suratMasuk',
                'dariUser',
                'kepadaUser',
                'children.kepadaUser',
                'children.dariUser'
            ])
            ->whereNull('parent_disposisi_id')
            ->latest()
            ->paginate(10); // <-- Gunakan paginate

        return view('disposisi.monitoring', compact('data'));
    }

    public function showMonitoring(Disposisi $disposisi)
    {
        $this->pastikanBolehMemantau();

        $disposisi->load([
            'suratMasuk',
            'dariUser',
            'kepadaUser',
            'children.dariUser',
            'children.kepadaUser'
        ]);

        return view('disposisi.monitoring-detail', compact('disposisi'));
    }

    public function continue(Disposisi $disposisi)
    {
        // TAMBAHAN KEAMANAN: Hanya orang yang menerima disposisi ini yang boleh meneruskannya
        if ($disposisi->kepada_user_id != auth()->id()) {
            abort(403, 'Akses ditolak. Anda tidak berhak meneruskan disposisi ini.');
        }

        // Aturan tujuan sama dengan saat membuat disposisi baru. Fallback lama
        // yang mengembalikan seluruh pegawai dihapus karena justru membuka
        // penerusan ke luar unit.
        $users = $this->tujuanDisposisi(auth()->user());

        if ($users->isEmpty()) {
            return back()->with('error', $this->alasanTujuanKosong(auth()->user()));
        }

        return view('disposisi.continue', compact('disposisi', 'users'));
    }

    public function continueStore(Request $request)
    {
        $request->validate([
            'parent_disposisi_id' => 'required|exists:disposisis,id',
            'kepada_user_id'      => 'required|array|min:1',
            'kepada_user_id.*'    => ['exists:users,id', $this->aturanTujuanSah()],
            'instruksi'           => 'required|string',
            'batas_waktu'         => 'nullable|date|after_or_equal:today',
        ]);

        $parent = Disposisi::findOrFail($request->parent_disposisi_id);

        // Pengecekan yang sama seperti pada form continue(): hanya penerima
        // disposisi yang berhak meneruskannya.
        if ($parent->kepada_user_id != auth()->id()) {
            abort(403, 'Akses ditolak. Anda tidak berhak meneruskan disposisi ini.');
        }

        DB::transaction(function () use ($request, $parent) {
            foreach ($request->kepada_user_id as $userId) {
                $child = Disposisi::create([
                    'parent_disposisi_id' => $parent->id,
                    // Surat diambil dari disposisi induk, bukan dari input,
                    // agar tidak bisa dipasangkan ke surat lain.
                    'surat_masuk_id'      => $parent->surat_masuk_id,
                    'dari_user_id'        => auth()->id(),
                    'kepada_user_id'      => $userId,
                    'instruksi'           => $request->instruksi,
                    'status'              => 'menunggu',
                    'tanggal_disposisi'   => now(),
                    'batas_waktu'         => $request->batas_waktu,
                ]);

                $child->kepadaUser->notify(new DisposisiDiterima($child));

                ActivityHelper::log(
                    'Teruskan Disposisi',
                    auth()->user()->name . ' meneruskan disposisi surat ' . ($child->suratMasuk?->nomor_surat ?? '-') . ' kepada ' . $child->kepadaUser->name
                );
            }

            // Induk kini sedang dikerjakan pihak lain, bukan lagi menunggu.
            if ($parent->status === 'menunggu') {
                $parent->update(['status' => 'diproses']);
            }

            $parent->suratMasuk?->segarkanStatus();
        });

        return redirect()
            ->route('disposisi.saya')
            ->with('success', 'Disposisi berhasil diteruskan.');
    }

    public function destroy(Disposisi $disposisi)
    {
        // Daftar role sebelumnya mencakup hampir semua orang sehingga bukan
        // pembatasan yang berarti. Yang berhak membatalkan disposisi adalah
        // pihak yang menerbitkannya, atau administrator.
        $isAdmin = in_array(strtolower(auth()->user()->role ?? ''), ['admin', 'administrator', 'superadmin']);

        if (!$isAdmin && $disposisi->dari_user_id !== auth()->id()) {
            abort(403, 'Akses ditolak. Hanya pemberi disposisi yang dapat menghapusnya.');
        }

        ActivityHelper::log('Hapus Disposisi', 'Menghapus disposisi ID ' . $disposisi->id);

        $suratMasuk = $disposisi->suratMasuk;

        $disposisi->delete();

        // Surat bisa kembali berstatus 'baru' bila disposisi terakhirnya dibatalkan.
        $suratMasuk?->segarkanStatus();

        return back()->with('success', 'Disposisi berhasil dihapus.');
    }
}