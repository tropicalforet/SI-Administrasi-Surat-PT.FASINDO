<!DOCTYPE html>

<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Surat Masuk - E-Office</title>
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
                Data Surat Masuk
            </h2>

            <p class="text-sm text-gray-500 mt-1">
                Daftar semua surat yang masuk ke sistem E-Office.
            </p>
        </div>

        <a href="/surat-masuk/create"
           class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 shadow-sm hover:shadow">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 4v16m8-8H4"></path>
            </svg>
            Tambah Surat
        </a>

    </div>

    @if(session('success'))
    <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6 text-sm flex items-center gap-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span class="font-medium">{{ session('success') }}</span>
    </div>
    @endif

    <div class="overflow-x-auto bg-white rounded-lg border border-gray-200">

        <table class="w-full text-left border-collapse">

            <thead>
                <tr class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider border-b border-gray-200">
                    <th class="py-4 px-6 font-semibold">No</th>
                    <th class="py-4 px-6 font-semibold">Nomor Surat</th>
                    <th class="py-4 px-6 font-semibold">Tanggal</th>
                    <th class="py-4 px-6 font-semibold">Pengirim</th>
                    <th class="py-4 px-6 font-semibold">Perihal</th>
                    <th class="py-4 px-6 font-semibold">File</th>
                    <th class="py-4 px-6 font-semibold text-center">Aksi</th>
                </tr>
            </thead>

            <tbody class="text-gray-700 text-sm divide-y divide-gray-200">

                @foreach($data as $item)
                <tr class="hover:bg-blue-50 transition duration-150">

                    <td class="py-4 px-6">
                        {{ $loop->iteration }}
                    </td>

                    <td class="py-4 px-6 font-medium text-gray-900">
                        {{ $item->nomor_surat }}
                    </td>

                    <td class="py-4 px-6 whitespace-nowrap">
                        {{ $item->tanggal_surat }}
                    </td>

                    <td class="py-4 px-6">
                        {{ $item->pengirim }}
                    </td>

                    <td class="py-4 px-6">
                        {{ $item->perihal }}
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

                            <a href="/surat-masuk/{{ $item->id }}/edit"
                               class="inline-flex items-center justify-center bg-amber-100 text-amber-700 hover:bg-amber-200 px-3 py-1.5 rounded-md text-xs font-bold transition duration-200">
                                Edit
                            </a>

                            <form action="/surat-masuk/{{ $item->id }}"
                                  method="POST">
                                @csrf
                                @method('DELETE')

                                <button type="submit"
                                        onclick="return confirm('Yakin ingin menghapus surat ini?')"
                                        class="inline-flex items-center justify-center bg-red-100 text-red-700 hover:bg-red-200 px-3 py-1.5 rounded-md text-xs font-bold transition duration-200">
                                    Hapus
                                </button>
                            </form>

                        </div>

                    </td>

                </tr>
                @endforeach

                @if($data->isEmpty())
                <tr>
                    <td colspan="7"
                        class="py-8 px-6 text-center text-gray-500">
                        Belum ada data surat masuk.
                    </td>
                </tr>
                @endif

            </tbody>

        </table>

    </div>

</div>

</div>

</body>
</html>
