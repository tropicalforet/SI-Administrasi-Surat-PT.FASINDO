<?php

namespace App\Http\Controllers;

use App\Models\Skpd;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class SkpdController extends Controller
{
    public function index()
    {
        $data = Skpd::latest()->get();

        return view('skpd.index', compact('data'));
    }

    public function create()
    {
        return view('skpd.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_pegawai' => 'required',
            'nip' => 'required',
            'tujuan_dinas' => 'required',
            'keperluan' => 'required',
            'tanggal_berangkat' => 'required|date',
            'tanggal_kembali' => 'required|date',
            'biaya_transport' => 'required|numeric',
            'biaya_penginapan' => 'required|numeric',
            'biaya_konsumsi_per_hari' => 'required|numeric',
            'file' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $last = Skpd::latest()->first();

        if ($last) {
            $nomorUrut = $last->id + 1;
        } else {
            $nomorUrut = 1;
        }

        $nomor_skpd =
            'SKPD-' .
            str_pad($nomorUrut, 3, '0', STR_PAD_LEFT) .
            '/' .
            date('m') .
            '/' .
            date('Y');

        $tanggalBerangkat = Carbon::parse($request->tanggal_berangkat);
        $tanggalKembali = Carbon::parse($request->tanggal_kembali);

        $durasi = $tanggalBerangkat->diffInDays($tanggalKembali);

        $totalBiaya =
            $request->biaya_transport +
            $request->biaya_penginapan +
            ($request->biaya_konsumsi_per_hari * $durasi);

        $filePath = null;

        if ($request->hasFile('file')) {
            $filePath = $request->file('file')
                ->store('skpd', 'public');
        }

        Skpd::create([
            'nomor_skpd' => $nomor_skpd,
            'nama_pegawai' => $request->nama_pegawai,
            'nip' => $request->nip,
            'tujuan_dinas' => $request->tujuan_dinas,
            'keperluan' => $request->keperluan,
            'tanggal_berangkat' => $request->tanggal_berangkat,
            'tanggal_kembali' => $request->tanggal_kembali,
            'durasi_hari' => $durasi,
            'biaya_transport' => $request->biaya_transport,
            'biaya_penginapan' => $request->biaya_penginapan,
            'biaya_konsumsi_per_hari' => $request->biaya_konsumsi_per_hari,
            'total_biaya' => $totalBiaya,
            'file' => $filePath,
            'status' => 'draft',
        ]);

        return redirect()
            ->route('skpd.index')
            ->with('success', 'SKPD berhasil ditambahkan');
    }

    public function show(Skpd $skpd)
    {
         return view('skpd.show', compact('skpd'));
    }

    public function edit(Skpd $skpd)
    {
        return view('skpd.edit', compact('skpd'));
    }

    public function update(Request $request, Skpd $skpd)
    {
        $request->validate([
            'nama_pegawai' => 'required',
            'nip' => 'required',
            'tujuan_dinas' => 'required',
            'keperluan' => 'required',
            'tanggal_berangkat' => 'required|date',
            'tanggal_kembali' => 'required|date',
            'biaya_transport' => 'required|numeric',
            'biaya_penginapan' => 'required|numeric',
            'biaya_konsumsi_per_hari' => 'required|numeric',
            'status' => 'required',
            'file' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $tanggalBerangkat = Carbon::parse($request->tanggal_berangkat);
        $tanggalKembali = Carbon::parse($request->tanggal_kembali);

        $durasi = $tanggalBerangkat->diffInDays($tanggalKembali);

        $totalBiaya =
            $request->biaya_transport +
            $request->biaya_penginapan +
            ($request->biaya_konsumsi_per_hari * $durasi);

        $filePath = $skpd->file;

        if ($request->hasFile('file')) {
            $filePath = $request->file('file')
                ->store('skpd', 'public');
        }

        $skpd->update([
            'nama_pegawai' => $request->nama_pegawai,
            'nip' => $request->nip,
            'tujuan_dinas' => $request->tujuan_dinas,
            'keperluan' => $request->keperluan,
            'tanggal_berangkat' => $request->tanggal_berangkat,
            'tanggal_kembali' => $request->tanggal_kembali,
            'durasi_hari' => $durasi,
            'biaya_transport' => $request->biaya_transport,
            'biaya_penginapan' => $request->biaya_penginapan,
            'biaya_konsumsi_per_hari' => $request->biaya_konsumsi_per_hari,
            'total_biaya' => $totalBiaya,
            'status' => $request->status,
            'file' => $filePath,
        ]);

        return redirect()
            ->route('skpd.index')
            ->with('success', 'SKPD berhasil diupdate');
    }
    public function downloadPdf(Skpd $skpd)
{
$pdf = Pdf::loadView('skpd.pdf', compact('skpd'));

return $pdf->download(
    str_replace('/', '-', $skpd->nomor_skpd) . '.pdf'
);

}

    public function destroy(Skpd $skpd)
    {
        $skpd->delete();

        return redirect()
            ->route('skpd.index')
            ->with('success', 'SKPD berhasil dihapus');
    }
}
