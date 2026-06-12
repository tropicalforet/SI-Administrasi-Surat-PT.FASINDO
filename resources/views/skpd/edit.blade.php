<!DOCTYPE html>

<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit SKPD - E-Office</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen font-sans p-6">

<div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-3xl">

<div class="mb-6">

    <h2 class="text-2xl font-bold text-gray-800">
        Edit SKPD
    </h2>

    <p class="text-gray-500 text-sm mt-1">
        Perbarui data Surat Keterangan Perjalanan Dinas
    </p>

</div>

<form action="{{ route('skpd.update', $skpd->id) }}"
      method="POST"
      enctype="multipart/form-data"
      class="space-y-5">

    @csrf
    @method('PUT')

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            Nomor SKPD
        </label>

        <input type="text"
               value="{{ $skpd->nomor_skpd }}"
               readonly
               class="w-full px-4 py-2.5 bg-gray-100 border border-gray-300 rounded-lg">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            Nama Pegawai
        </label>

        <input type="text"
               name="nama_pegawai"
               value="{{ $skpd->nama_pegawai }}"
               required
               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            NIP
        </label>

        <input type="text"
               name="nip"
               value="{{ $skpd->nip }}"
               required
               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            Tujuan Dinas
        </label>

        <input type="text"
               name="tujuan_dinas"
               value="{{ $skpd->tujuan_dinas }}"
               required
               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            Keperluan
        </label>

        <textarea name="keperluan"
                  rows="3"
                  required
                  class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg">{{ $skpd->keperluan }}</textarea>
    </div>

    <div class="grid grid-cols-2 gap-4">

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Tanggal Berangkat
            </label>

            <input type="date"
                   name="tanggal_berangkat"
                   value="{{ $skpd->tanggal_berangkat }}"
                   required
                   class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Tanggal Kembali
            </label>

            <input type="date"
                   name="tanggal_kembali"
                   value="{{ $skpd->tanggal_kembali }}"
                   required
                   class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg">
        </div>

    </div>

    <hr>

    <h3 class="font-bold text-lg text-gray-700">
        Rincian Biaya Perjalanan
    </h3>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            Biaya Transport
        </label>

        <input type="number"
               name="biaya_transport"
               value="{{ $skpd->biaya_transport }}"
               required
               min="0"
               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            Biaya Penginapan
        </label>

        <input type="number"
               name="biaya_penginapan"
               value="{{ $skpd->biaya_penginapan }}"
               required
               min="0"
               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            Biaya Konsumsi per Hari
        </label>

        <input type="number"
               name="biaya_konsumsi_per_hari"
               value="{{ $skpd->biaya_konsumsi_per_hari }}"
               required
               min="0"
               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            Status
        </label>

        <select name="status"
                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg">

            <option value="draft"
                {{ $skpd->status == 'draft' ? 'selected' : '' }}>
                Draft
            </option>

            <option value="disetujui"
                {{ $skpd->status == 'disetujui' ? 'selected' : '' }}>
                Disetujui
            </option>

            <option value="selesai"
                {{ $skpd->status == 'selesai' ? 'selected' : '' }}>
                Selesai
            </option>

        </select>
    </div>

    <div>

        <label class="block text-sm font-medium text-gray-700 mb-2">
            File Saat Ini
        </label>

        @if($skpd->file)

            <a href="{{ asset('storage/'.$skpd->file) }}"
               target="_blank"
               class="text-blue-600 underline">

                Lihat Lampiran

            </a>

        @else

            <span class="text-gray-500">
                Tidak ada file
            </span>

        @endif

    </div>

    <div>

        <label class="block text-sm font-medium text-gray-700 mb-2">
            Ganti Lampiran
        </label>

        <input type="file"
               name="file"
               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg">

    </div>

    <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-100">

        <a href="{{ route('skpd.index') }}"
           class="px-5 py-2.5 text-sm text-gray-600 bg-gray-100 rounded-lg">

            Batal

        </a>

        <button type="submit"
                class="px-5 py-2.5 text-sm text-white bg-purple-600 rounded-lg">

            Update SKPD

        </button>

    </div>

</form>

</div>

</body>
</html>
