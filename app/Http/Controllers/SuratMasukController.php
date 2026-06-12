<?php



namespace App\Http\Controllers;



use App\Models\SuratMasuk;

use Illuminate\Http\Request;



class SuratMasukController extends Controller

{

    public function index()

    {

        $data = SuratMasuk::latest()->get();

        return view('surat_masuk.index', compact('data'));

    }



    public function create()

    {

        return view('surat_masuk.create');

    }



    public function store(Request $request)

    {

        $request->validate([

            'nomor_surat' => 'required',

            'tanggal_surat' => 'required|date',

            'pengirim' => 'required',

            'perihal' => 'required',

            'file' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',

        ]);



        $filePath = null;



        if ($request->hasFile('file')) {

            $filePath = $request->file('file')

                ->store('surat_masuk', 'public');

        }



        SuratMasuk::create([

            'nomor_surat' => $request->nomor_surat,

            'tanggal_surat' => $request->tanggal_surat,

            'pengirim' => $request->pengirim,

            'perihal' => $request->perihal,

            'file' => $filePath,

            'status' => 'baru',

        ]);



        return redirect()->route('surat-masuk.index')

            ->with('success', 'Surat berhasil ditambahkan');

    }



    public function edit(SuratMasuk $surat_masuk)

    {

        return view('surat_masuk.edit', compact('surat_masuk'));

    }



    public function update(Request $request, SuratMasuk $surat_masuk)

    {

        $request->validate([

            'nomor_surat' => 'required',

            'tanggal_surat' => 'required|date',

            'pengirim' => 'required',

            'perihal' => 'required',

            'file' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',

        ]);



        $filePath = $surat_masuk->file;



        if ($request->hasFile('file')) {

            $filePath = $request->file('file')

                ->store('surat_masuk', 'public');

        }



        $surat_masuk->update([

            'nomor_surat' => $request->nomor_surat,

            'tanggal_surat' => $request->tanggal_surat,

            'pengirim' => $request->pengirim,

            'perihal' => $request->perihal,

            'file' => $filePath,

        ]);



        return redirect()->route('surat-masuk.index')

            ->with('success', 'Surat berhasil diupdate');

    }



    public function destroy(SuratMasuk $surat_masuk)

    {

        $surat_masuk->delete();



        return redirect()->route('surat-masuk.index')

            ->with('success', 'Surat berhasil dihapus');

    }

}
