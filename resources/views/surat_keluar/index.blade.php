<!DOCTYPE html>

<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Surat Keluar - E-Office</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans p-6">

<div class="max-w-6xl mx-auto">

<div class="bg-white p-6 rounded-xl shadow-lg">

    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">

        <div>

            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium text-gray-700 mb-3">
                ← Dashboard
            </a>

            <h2 class="text-2xl font-bold text-gray-800">
                Data Surat Keluar
            </h2>

            <p class="text-sm text-gray-500 mt-1">
                Daftar semua surat keluar pada sistem E-Office.
            </p>

        </div>

        <a href="{{ route('surat-keluar.create') }}"
           class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 shadow-sm hover:shadow">

            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 4v16m8-8H4">
                </path>
            </svg>

            Tambah Surat

        </a>

    </div>

    @if(session('success'))
    <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6 text-sm">
        {{ session('success') }}
    </div>
    @endif

    <div class="overflow-x-auto bg-white rounded-lg border border-gray-200">

        <table class="w-full text-left border-collapse">

            <thead>
                <tr class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider border-b border-gray-200">
                    <th class="py-4 px-6">No</th>
                    <th class="py-4 px-6">Nomor Surat</th>
                    <th class="py-4 px-6">Tanggal</th>
                    <th class="py-4 px-6">Tujuan</th>
                    <th class="py-4 px-6">Perihal</th>
                    <th class="py-4 px-6">Status</th>
                    <th class="py-4 px-6">File</th>
                    <th class="py-4 px-6 text-center">Aksi</th>
                </tr>
            </thead>

            <tbody class="text-gray-700 text-sm divide-y divide-gray-200">

                @forelse($data as $item)

                <tr class="hover:bg-blue-50">

                    <td class="py-4 px-6">
                        {{ $loop->iteration }}
                    </td>

                    <td class="py-4 px-6 font-medium">
                        {{ $item->nomor_surat }}
                    </td>

                    <td class="py-4 px-6">
                        {{ $item->tanggal_surat }}
                    </td>

                    <td class="py-4 px-6">
                        {{ $item->tujuan }}
                    </td>

                    <td class="py-4 px-6">
                        {{ $item->perihal }}
                    </td>

                    <td class="py-4 px-6">

                        @if($item->status == 'draft')
                            <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-xs">
                                Draft
                            </span>
                        @elseif($item->status == 'dikirim')
                            <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs">
                                Dikirim
                            </span>
                        @elseif($item->status == 'selesai')
                            <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs">
                                Selesai
                            </span>
                        @else
                            <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs">
                                {{ $item->status }}
                            </span>
                        @endif

                    </td>

                    <td class="py-4 px-6 whitespace-nowrap">

                        @if($item->file)

                            <a href="{{ asset('storage/'.$item->file) }}"
                               target="_blank"
                               class="text-blue-600 hover:text-blue-800 underline font-medium">
                                Lihat File
                            </a>

                        @else

                            <span class="text-gray-400">
                                Tidak ada file
                            </span>

                        @endif

                    </td>

                    <td class="py-4 px-6">

                        <div class="flex justify-center gap-2">

                            <a href="{{ route('surat-keluar.edit', $item->id) }}"
                               class="bg-amber-100 text-amber-700 hover:bg-amber-200 px-3 py-1 rounded text-xs font-bold">
                                Edit
                            </a>

                            <form action="{{ route('surat-keluar.destroy', $item->id) }}"
                                  method="POST">

                                @csrf
                                @method('DELETE')

                                <button type="submit"
                                        onclick="return confirm('Yakin ingin menghapus surat ini?')"
                                        class="bg-red-100 text-red-700 hover:bg-red-200 px-3 py-1 rounded text-xs font-bold">
                                    Hapus
                                </button>

                            </form>

                        </div>

                    </td>

                </tr>

                @empty

                <tr>
                    <td colspan="8"
                        class="text-center py-8 text-gray-500">
                        Belum ada data surat keluar.
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
