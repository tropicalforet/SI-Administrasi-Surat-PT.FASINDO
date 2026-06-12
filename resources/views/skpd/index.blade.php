<!DOCTYPE html>

<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data SKPD - E-Office</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans p-6">

<div class="max-w-7xl mx-auto">

<div class="bg-white p-6 rounded-xl shadow-lg">

<div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">

    <div>
        <h2 class="text-2xl font-bold text-gray-800">
            Data SKPD
        </h2>

        <p class="text-sm text-gray-500 mt-1">
            Surat Keterangan Perjalanan Dinas
        </p>
    </div>

    <div class="flex gap-2">

        <a href="{{ route('dashboard') }}"
           class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 px-4 rounded-lg">
            Dashboard
        </a>

        <a href="{{ route('skpd.create') }}"
           class="bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 px-4 rounded-lg">
            + Tambah SKPD
        </a>

    </div>

</div>

@if(session('success'))
    <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6">
        {{ session('success') }}
    </div>
@endif

<div class="overflow-x-auto border rounded-lg">

    <table class="w-full">

        <thead class="bg-gray-50">

            <tr>
                <th class="px-4 py-3 text-left">No</th>
                <th class="px-4 py-3 text-left">Nomor SKPD</th>
                <th class="px-4 py-3 text-left">Nama Pegawai</th>
                <th class="px-4 py-3 text-left">Tujuan</th>
                <th class="px-4 py-3 text-left">Durasi</th>
                <th class="px-4 py-3 text-left">Total Biaya</th>
                <th class="px-4 py-3 text-left">Status</th>
                <th class="px-4 py-3 text-center">Aksi</th>
            </tr>

        </thead>

        <tbody>

        @forelse($data as $item)

            <tr class="border-t">

                <td class="px-4 py-3">{{ $loop->iteration }}</td>

                <td class="px-4 py-3">
                    {{ $item->nomor_skpd }}
                </td>

                <td class="px-4 py-3">
                    {{ $item->nama_pegawai }}
                </td>

                <td class="px-4 py-3">
                    {{ $item->tujuan_dinas }}
                </td>

                <td class="px-4 py-3">
                    {{ $item->durasi_hari }} Hari
                </td>

                <td class="px-4 py-3">
                    Rp {{ number_format($item->total_biaya,0,',','.') }}
                </td>

                <td class="px-4 py-3">
                    <span class="px-2 py-1 rounded text-xs bg-yellow-100 text-yellow-700">
                        {{ $item->status }}
                    </span>
                </td>

                <td class="px-4 py-3 text-center">

                    <div class="flex flex-wrap justify-center gap-2">

                        <a href="{{ route('skpd.show',$item->id) }}"
                           class="bg-blue-100 text-blue-700 px-3 py-1 rounded text-xs font-bold">
                            Lihat Surat
                        </a>

                        <a href="{{ route('skpd.edit',$item->id) }}"
                           class="bg-amber-100 text-amber-700 px-3 py-1 rounded text-xs font-bold">
                            Edit
                        </a>

                        <form action="{{ route('skpd.destroy',$item->id) }}"
                              method="POST">

                            @csrf
                            @method('DELETE')

                            <button type="submit"
                                    onclick="return confirm('Yakin ingin menghapus data?')"
                                    class="bg-red-100 text-red-700 px-3 py-1 rounded text-xs font-bold">
                                Hapus
                            </button>

                        </form>

                    </div>

                </td>

            </tr>

        @empty

            <tr>
                <td colspan="8"
                    class="text-center py-10 text-gray-500">
                    Belum ada data SKPD
                </td>
            </tr>

        @endforelse

        </tbody>

    </table>

</div>

</div>

</div>

</body>
</html>
