<!DOCTYPE html>

<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Surat Masuk - E-Office</title>
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
            Tambah Surat Masuk
        </h2>

        <p class="text-gray-500 text-sm mt-1">
            Masukkan detail informasi surat masuk yang baru.
        </p>

    </div>

    <form action="{{ route('surat-masuk.store') }}"
          method="POST"
          enctype="multipart/form-data"
          class="space-y-5">

        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Nomor Surat
            </label>

            <input type="text"
                   name="nomor_surat"
                   required
                   class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Tanggal Surat
            </label>

            <input type="date"
                   name="tanggal_surat"
                   required
                   class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Pengirim
            </label>

            <input type="text"
                   name="pengirim"
                   required
                   class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Perihal
            </label>

            <input type="text"
                   name="perihal"
                   required
                   class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Upload File
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
                Simpan Data
            </button>

        </div>

    </form>

</div>


</body>
</html>
