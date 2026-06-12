<!DOCTYPE html>

<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Surat Masuk - E-Office</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen font-sans p-6">

```
<div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-lg">

    <div class="mb-6">

        <a href="{{ route('dashboard') }}"
           class="inline-flex items-center px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium text-gray-700 mb-4">
            ← Dashboard
        </a>

        <h2 class="text-2xl font-bold text-gray-800">
            Edit Surat Masuk
        </h2>

        <p class="text-gray-500 text-sm mt-1">
            Perbarui informasi surat masuk.
        </p>

    </div>

    <form action="{{ route('surat-masuk.update', $surat_masuk->id) }}"
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
                   name="nomor_surat"
                   value="{{ $surat_masuk->nomor_surat }}"
                   required
                   class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Tanggal Surat
            </label>

            <input type="date"
                   name="tanggal_surat"
                   value="{{ $surat_masuk->tanggal_surat }}"
                   required
                   class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Pengirim
            </label>

            <input type="text"
                   name="pengirim"
                   value="{{ $surat_masuk->pengirim }}"
                   required
                   class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Perihal
            </label>

            <input type="text"
                   name="perihal"
                   value="{{ $surat_masuk->perihal }}"
                   required
                   class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                File Saat Ini
            </label>

            @if($surat_masuk->file)
                <a href="{{ asset('storage/'.$surat_masuk->file) }}"
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
                Format: PDF, JPG, PNG (Max 2MB)
            </p>
        </div>

        <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-100 mt-2">

            <a href="{{ route('surat-masuk.index') }}"
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
```

</body>
</html>
