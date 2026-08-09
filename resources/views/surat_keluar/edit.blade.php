<!DOCTYPE html>

<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Surat Keluar - E-Office</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen font-sans p-6">

<div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-lg">

    <div class="mb-6">

        <a href="{{ route('dashboard') }}"
           class="inline-flex items-center px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium text-gray-700 mb-4">
            ← Dashboard
        </a>

        <h2 class="text-2xl font-bold text-gray-800">
            Edit Surat Keluar
        </h2>

        <p class="text-gray-500 text-sm mt-1">
            Perbarui informasi surat keluar.
        </p>

        @if ($errors->any())

<div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 mt-4">

    <ul class="list-disc pl-5">

        @foreach($errors->all() as $error)

            <li>{{ $error }}</li>

        @endforeach

    </ul>

</div>

@endif

    </div>

    <form action="{{ route('surat-keluar.update', $surat_keluar->id) }}"
          method="POST"
          enctype="multipart/form-data"
          class="space-y-5">

        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Nomor Surat
            </label>

            <input type="text"
                   value="{{ $surat_keluar->nomor_surat }}"
                   readonly
                   class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Tanggal Surat
            </label>

            <input type="date"
                   name="tanggal_surat"
                   value="{{ $surat_keluar->tanggal_surat }}"
                   required
                   class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg">
        </div>

        @include('surat_keluar.partials.unit-verifikasi', [
            'unitVerifikasi' => old('unit_verifikasi', $surat_keluar->unit_verifikasi),
        ])

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Tujuan Surat
            </label>

            <input type="text"
                   name="tujuan"
                   value="{{ $surat_keluar->tujuan }}"
                   required
                   class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Perihal
            </label>

            <input type="text"
                   name="perihal"
                   value="{{ $surat_keluar->perihal }}"
                   required
                   class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                File Saat Ini
            </label>

            @if($surat_keluar->file)
                <a href="{{ asset('storage/'.$surat_keluar->file) }}"
                   target="_blank"
                   class="text-blue-600 hover:text-blue-800 underline text-sm">
                    Lihat File Saat Ini
                </a>
            @else
                <p class="text-gray-500 text-sm">
                    Tidak ada file
                </p>
            @endif
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Ganti File (Opsional)
            </label>

            <input type="file"
                   name="file"
                   class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg">

            <p class="text-xs text-gray-500 mt-1">
                Format yang didukung: PDF, JPG, PNG, DOCX (Maks. 5MB)
            </p>

            <!-- Info Box Placeholders DOCX -->
            <div class="mt-4 p-4 bg-blue-50/50 border border-blue-100 rounded-xl text-xs space-y-2 text-slate-600">
                <h4 class="font-bold text-blue-800 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 01-2 2h0a2 2 0 01-2 2v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                    Tips E-Sign Dokumen Word (DOCX):
                </h4>
                <p class="leading-relaxed">Jika mengunggah draf berformat Word, Anda dapat menuliskan kode-kode penanda (*placeholders*) berikut di dalam berkas Word Anda. Sistem akan otomatis menggantinya dengan tanda tangan asli dan QR Code saat disetujui Dirut:</p>
                <ul class="list-disc ml-4 space-y-1">
                    <li><code class="font-mono bg-white px-1 py-0.5 border border-slate-200 rounded text-blue-600">${nomor_surat}</code> : Nomor surat keluar dinamis yang dibuat sistem.</li>
                    <li><code class="font-mono bg-white px-1 py-0.5 border border-slate-200 rounded text-blue-600">${ttd_dirut}</code> : Lokasi gambar tanda tangan digital.</li>
                    <li><code class="font-mono bg-white px-1 py-0.5 border border-slate-200 rounded text-blue-600">${qr_code}</code> : Lokasi QR Code untuk verifikasi keaslian surat.</li>
                    <li><code class="font-mono bg-white px-1 py-0.5 border border-slate-200 rounded text-blue-600">${nama_dirut}</code> : Nama lengkap penanda tangan (Direktur Utama).</li>
                    <li><code class="font-mono bg-white px-1 py-0.5 border border-slate-200 rounded text-blue-600">${tanggal_ttd}</code> : Tanggal penyematan E-Sign.</li>
                    <li><code class="font-mono bg-white px-1 py-0.5 border border-slate-200 rounded text-blue-600">${hash_verify}</code> : Kode enkripsi hash SHA-256 dokumen.</li>
                </ul>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Status Surat
            </label>

            <select name="status"
                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg">

                <option value="draft"
                    {{ $surat_keluar->status == 'draft' ? 'selected' : '' }}>
                    Draft
                </option>

                <option value="dikirim"
                    {{ $surat_keluar->status == 'dikirim' ? 'selected' : '' }}>
                    Dikirim
                </option>

                <option value="selesai"
                    {{ $surat_keluar->status == 'selesai' ? 'selected' : '' }}>
                    Selesai
                </option>

            </select>
        </div>

        <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-100 mt-2">

            <a href="{{ route('surat-keluar.index') }}"
               class="px-5 py-2.5 text-sm text-gray-600 bg-gray-100 rounded-lg">
                Batal
            </a>

            <button type="submit"
                    class="px-5 py-2.5 text-sm text-white bg-blue-600 rounded-lg">
                Update Data
            </button>

        </div>

    </form>

</div>

</body>
</html>
