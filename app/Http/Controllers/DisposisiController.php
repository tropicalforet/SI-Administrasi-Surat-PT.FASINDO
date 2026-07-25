<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Disposisi;
use App\Models\SuratMasuk;
use App\Helpers\ActivityHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // <-- Tambahan wajib untuk Transaction

class DisposisiController extends Controller
{
    public function create(SuratMasuk $suratMasuk)
    {
        $role = auth()->user()->role;
        if ($role == 'dirut') {
            $users = User::whereIn('role', ['direktur1', 'direktur2', 'sekretaris'])->get();
        } elseif ($role == 'sekretaris') {
            $users = User::whereIn('role', ['dirut', 'direktur1', 'direktur2'])->get();
        } elseif ($role == 'direktur1' || $role == 'direktur2') {
            $users = User::where('role', 'staff')->get();
        } else {
            abort(403, 'Anda tidak memiliki akses untuk membuat disposisi.');
        }

        return view('disposisi.create', compact('suratMasuk', 'users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'surat_masuk_id' => 'required|exists:surat_masuks,id',
            'kepada_user_id' => 'required|exists:users,id',
            'instruksi'      => 'required|string',
            'batas_waktu'    => 'nullable|date|after_or_equal:today',
        ]);

        // Gunakan DB Transaction agar penyimpanan disposisi dan log berjalan bersamaan (aman)
        DB::transaction(function () use ($request) {
            $disposisi = Disposisi::create([
                'surat_masuk_id'    => $request->surat_masuk_id,
                'dari_user_id'      => auth()->id(),
                'kepada_user_id'    => $request->kepada_user_id,
                'instruksi'         => $request->instruksi,
                'status'            => 'menunggu',
                'tanggal_disposisi' => now(),
                'batas_waktu'       => $request->batas_waktu,
            ]);

            ActivityHelper::log(
                'Membuat Disposisi',
                'Mendisposisikan surat ' . $disposisi->suratMasuk->nomor_surat . ' kepada ' . $disposisi->kepadaUser->name
            );
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
        if ($disposisi->kepada_user_id != auth()->id()) {
            abort(403);
        }

        $request->validate([
            'status'                => 'required|in:menunggu,diproses,selesai',
            'catatan_tindak_lanjut' => 'nullable|string',
            'file_tindak_lanjut'    => 'nullable|mimes:pdf,jpg,jpeg,png,doc,docx,zip,xls,xlsx|max:5120',
        ]);

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
            ]);

            ActivityHelper::log(
                'Update Disposisi',
                auth()->user()->name . ' mengubah status disposisi surat ' . $disposisi->suratMasuk->nomor_surat . ' menjadi ' . ucfirst($request->status)
            );
        });

        return redirect()
            ->route('disposisi.saya')
            ->with('success', 'Tindak lanjut berhasil disimpan.');
    }

    public function monitoring()
    {
        if (auth()->user()->role != 'dirut' && auth()->user()->role != 'sekretaris') {
            abort(403, 'Akses khusus Direktur Utama dan Sekretaris.');
        }

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
        if (auth()->user()->role != 'dirut' && auth()->user()->role != 'sekretaris') {
            abort(403);
        }

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

        $role = auth()->user()->role;
        if ($role == 'dirut') {
            $users = User::whereIn('role', ['direktur1', 'direktur2', 'sekretaris'])->get();
        } elseif ($role == 'sekretaris') {
            $users = User::whereIn('role', ['dirut', 'direktur1', 'direktur2'])->get();
        } elseif ($role == 'direktur1' || $role == 'direktur2') {
            $users = User::where('role', 'staff')->get();
        } else {
            $users = User::where('role', 'staff')->get(); // Default fallback
        }

        return view('disposisi.continue', compact('disposisi', 'users'));
    }

    public function continueStore(Request $request)
    {
        $request->validate([
            'parent_disposisi_id' => 'required|exists:disposisis,id',
            'surat_masuk_id'      => 'required|exists:surat_masuks,id',
            'kepada_user_id'      => 'required|exists:users,id',
            'instruksi'           => 'required|string',
            'batas_waktu'         => 'nullable|date|after_or_equal:today',
        ]);

        DB::transaction(function () use ($request) {
            $child = Disposisi::create([
                'parent_disposisi_id' => $request->parent_disposisi_id,
                'surat_masuk_id'      => $request->surat_masuk_id,
                'dari_user_id'        => auth()->id(),
                'kepada_user_id'      => $request->kepada_user_id,
                'instruksi'           => $request->instruksi,
                'status'              => 'menunggu',
                'tanggal_disposisi'   => now(),
                'batas_waktu'         => $request->batas_waktu,
            ]);

            ActivityHelper::log(
                'Teruskan Disposisi',
                auth()->user()->name . ' meneruskan disposisi surat ' . $child->suratMasuk->nomor_surat . ' kepada ' . $child->kepadaUser->name
            );
        });

        return redirect()
            ->route('disposisi.saya')
            ->with('success', 'Disposisi berhasil diteruskan.');
    }

    public function destroy(Disposisi $disposisi)
    {
        abort_unless(in_array(strtolower(auth()->user()->role ?? ''), ['dirut', 'direktur1', 'direktur2', 'sekretaris', 'staff']), 403, 'Akses ditolak. Anda tidak memiliki izin untuk menghapus data disposisi.');

        ActivityHelper::log('Hapus Disposisi', 'Menghapus disposisi ID ' . $disposisi->id);
        
        $disposisi->delete();

        return back()->with('success', 'Disposisi berhasil dihapus.');
    }
}