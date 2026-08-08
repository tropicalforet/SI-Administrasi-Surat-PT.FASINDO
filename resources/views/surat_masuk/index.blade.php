@extends('layouts.app')

@section('content')
<div class="p-4 sm:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        
        <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4 bg-white">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">
                    Data Surat Masuk
                </h2>
                <p class="text-sm text-slate-500 mt-1">
                    Kelola dan pantau semua daftar surat yang masuk ke sistem E-Office.
                </p>
            </div>

            <div class="flex items-center gap-3">
                @if(in_array(strtolower(auth()->user()->role), ['admin', 'administrator', 'superadmin']))
                    <form action="{{ route('surat-masuk.clear') }}" method="POST" onsubmit="event.preventDefault(); ConfirmModal.show({title:'Hapus Semua Surat Masuk',message:'Seluruh data surat masuk beserta dokumen lampirannya akan dihapus secara permanen. Anda yakin ingin melanjutkan?',variant:'danger',confirmText:'Ya, Bersihkan'}).then(ok=>{if(ok)this.submit()})">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="flex items-center gap-2 px-5 py-2.5 bg-red-50 text-red-600 hover:bg-red-600 hover:text-white border border-red-200 hover:border-red-600 rounded-xl text-sm font-semibold transition-all shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            Bersihkan Riwayat
                        </button>
                    </form>
                @endif
                @if(auth()->user()->role == 'sekretaris')
                <a href="/surat-masuk/create"
                   class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-5 rounded-xl transition duration-200 shadow-md shadow-blue-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Tambah Surat
                </a>
                @endif
            </div>
        </div>

        <form method="GET"
      action="{{ route('surat-masuk.index') }}"
      class="m-6">

    <div class="grid md:grid-cols-4 gap-4">

        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Cari nomor surat, pengirim, perihal..."
            class="px-4 py-2 border rounded-xl">

        <input
            type="date"
            name="tanggal_awal"
            value="{{ request('tanggal_awal') }}"
            class="px-4 py-2 border rounded-xl">

        <input
            type="date"
            name="tanggal_akhir"
            value="{{ request('tanggal_akhir') }}"
            class="px-4 py-2 border rounded-xl">

        <div class="flex gap-2">

            <button
                class="bg-blue-600 text-white px-5 rounded-xl w-full">

                Cari

            </button>

            <a href="{{ route('surat-masuk.index') }}"
               class="bg-slate-200 px-5 rounded-xl flex items-center">

                Reset

            </a>

        </div>

    </div>

</form>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                        <th class="py-4 px-6 font-semibold w-16">No</th>
                        <th class="py-4 px-6 font-semibold">Nomor Surat</th>
                        <th class="py-4 px-6 font-semibold">Kategori</th>
                        <th class="py-4 px-6 font-semibold">Tanggal</th>
                        <th class="py-4 px-6 font-semibold">Pengirim</th>
                        <th class="py-4 px-6 font-semibold">Penerima</th>
                        <th class="py-4 px-6 font-semibold">Perihal</th>
                        <th class="py-4 px-6 font-semibold text-center">File</th>
                        <th class="py-4 px-6 font-semibold text-center w-64">Aksi</th>
                    </tr>
                </thead>

                <tbody class="text-slate-700 text-sm divide-y divide-slate-100">
                    @foreach($data as $item)
                    <tr class="hover:bg-slate-50/80 transition-colors duration-150 group">
                        
                        <td class="py-4 px-6 text-slate-500">
                            {{ $loop->iteration }}
                        </td>

                        <td class="py-4 px-6 font-semibold text-slate-800">
                            {{ $item->nomor_surat }}
                        </td>

                        <td class="py-4 px-6 text-slate-600">
                            @php
                                $kategoriText = match(strtoupper($item->kategori_surat)) {
                                    'SK' => 'Surat Keputusan (SK)',
                                    'SU' => 'Surat Undangan (SU)',
                                    'SP' => 'Surat Pemberitahuan (SP)',
                                    'ST' => 'Surat Tugas (ST)',
                                    default => $item->kategori_surat ?? '-',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                {{ $kategoriText }}
                            </span>
                        </td>

                        <td class="py-4 px-6 whitespace-nowrap text-slate-500">
                            {{ $item->tanggal_surat }}
                        </td>

                        <td class="py-4 px-6">
                            {{ $item->pengirim }}
                        </td>

                        <td class="py-4 px-6">
                            {{ $item->penerimaUser ? $item->penerimaUser->name . ' (' . ucfirst($item->penerimaUser->role) . ')' : $item->penerima }}
                        </td>

                        <td class="py-4 px-6">
                            <span class="line-clamp-2" title="{{ $item->perihal }}">
                                {{ $item->perihal }}
                            </span>
                        </td>

                        <td class="py-4 px-6 text-center">
                            @if($item->file)
                                <a href="{{ asset('storage/'.$item->file) }}"
                                   target="_blank"
                                   class="inline-flex items-center justify-center p-2 text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors"
                                   title="Lihat Dokumen">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                </a>
                            @else
                                <span class="text-slate-300 inline-flex p-2" title="Tidak ada file">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                </span>
                            @endif
                        </td>

                        <td class="py-4 px-6">
                            <div class="flex justify-center items-center gap-2">
                                
                                
                                @if(in_array(auth()->user()->role, ['dirut', 'sekretaris', 'direktur1', 'direktur2']))
                                <a href="{{ route('disposisi.create', $item->id) }}"
                                   class="bg-indigo-50 text-indigo-600 hover:bg-indigo-100 border border-indigo-100 px-3 py-1.5 rounded-md text-xs font-semibold transition-colors whitespace-nowrap">
                                    Disposisi
                                </a>
                                @endif

                                @if(auth()->user()->role == 'sekretaris')
                                <a href="/surat-masuk/{{ $item->id }}/edit"
                                   class="bg-amber-50 text-amber-600 hover:bg-amber-100 border border-amber-100 px-3 py-1.5 rounded-md text-xs font-semibold transition-colors whitespace-nowrap">
                                    Edit
                                </a>
                                @endif

                                @if(in_array(strtolower(auth()->user()->role ?? ''), ['admin', 'administrator', 'superadmin']))
                                <form action="{{ route('surat-masuk.destroy', $item->id) }}"
                                      method="POST"
                                      onsubmit="event.preventDefault(); ConfirmModal.show({title:'Hapus Surat Masuk',message:'Data surat masuk yang dihapus tidak dapat dikembalikan. Yakin ingin melanjutkan?',variant:'danger',confirmText:'Ya, Hapus'}).then(ok=>{if(ok)this.submit()})"
                                      class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="bg-red-50 text-red-600 hover:bg-red-100 border border-red-100 px-3 py-1.5 rounded-md text-xs font-semibold transition-colors whitespace-nowrap">
                                        Hapus
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>

                    </tr>
                    @endforeach

                    @if($data->isEmpty())
                    <tr>
                        <td colspan="8" class="py-12 px-6 text-center">
                            <div class="flex flex-col items-center justify-center text-slate-400">
                                <svg class="w-12 h-12 mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <p class="text-slate-500 font-medium">Belum ada data surat masuk.</p>
                                <p class="text-sm mt-1">Silakan tambah surat masuk baru untuk memulai.</p>
                            </div>
                        </td>
                    </tr>
                    @endif

                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection