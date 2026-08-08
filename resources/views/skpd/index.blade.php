@extends('layouts.app')

@section('content')
<div class="p-4 sm:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            
            <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4 bg-white">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800">
                        @if(strtolower(auth()->user()->role) == 'sekretaris')
                            Data SKPD
                        @elseif(in_array(strtolower(auth()->user()->role), ['dirut', 'direktur1']))
                            Persetujuan SKPD
                        @else
                            SKPD Saya
                        @endif
                    </h2>
                    <p class="text-sm text-slate-500 mt-1">
                        @if(strtolower(auth()->user()->role) == 'sekretaris')
                            Kelola dan verifikasi Surat Keterangan Perjalanan Dinas (SKPD) pegawai.
                        @elseif(in_array(strtolower(auth()->user()->role), ['dirut', 'direktur1']))
                            Setujui atau tolak pengajuan Surat Keterangan Perjalanan Dinas (SKPD) pegawai, dan kelola pengajuan SKPD Anda sendiri.
                        @else
                            Daftar pengajuan Surat Keterangan Perjalanan Dinas (SKPD) Anda.
                        @endif
                    </p>
                </div>

            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                            <th class="py-4 px-6 font-semibold w-16">No</th>
                            <th class="py-4 px-6 font-semibold">No. SKPD</th>
                            <th class="py-4 px-6 font-semibold">No. Surat Tugas</th>
                            @if(in_array(strtolower(auth()->user()->role), ['sekretaris', 'dirut']))
                                <th class="py-4 px-6 font-semibold">Diajukan Oleh</th>
                            @endif
                            <th class="py-4 px-6 font-semibold">Nama Pegawai</th>
                            <th class="py-4 px-6 font-semibold">Tujuan</th>
                            <th class="py-4 px-6 font-semibold">Durasi</th>
                            <th class="py-4 px-6 font-semibold text-center">Status</th>
                            <th class="py-4 px-6 font-semibold text-center w-64">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="text-slate-700 text-sm divide-y divide-slate-100">
                        @forelse($data as $item)
                        <tr class="hover:bg-slate-50/80 transition-colors duration-150 group">
                            
                            <td class="py-4 px-6 text-slate-500">
                                {{ ($data->currentPage() - 1) * $data->perPage() + $loop->iteration }}
                            </td>

                            <td class="py-4 px-6 font-semibold text-slate-800">
                                {{ $item->nomor_skpd }}
                            </td>

                            <td class="py-4 px-6 text-slate-500">
                                {{ $item->suratTugas->nomor_surat_tugas ?? '-' }}
                            </td>

                            @if(in_array(strtolower(auth()->user()->role), ['sekretaris', 'dirut']))
                                <td class="py-4 px-6">
                                    {{ $item->user->name ?? 'Staf' }}
                                </td>
                            @endif

                            <td class="py-4 px-6 whitespace-nowrap">
                                {{ $item->nama_pegawai }}
                            </td>

                            <td class="py-4 px-6">
                                <span class="line-clamp-2" title="{{ $item->tujuan_dinas }}">
                                    {{ $item->tujuan_dinas }}
                                </span>
                            </td>

                            <td class="py-4 px-6 whitespace-nowrap">
                                <div class="flex items-center gap-1.5 text-slate-600">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    {{ $item->durasi_hari }} Hari
                                </div>
                            </td>

                            <td class="py-4 px-6 text-center">
                                @if(strtolower($item->status) == 'pengajuan')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                        Pengajuan
                                    </span>
                                @elseif(strtolower($item->status) == 'diperiksa')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-yellow-50 text-yellow-700 border border-yellow-200">
                                        Perlu Approval Dirut
                                    </span>
                                @elseif(strtolower($item->status) == 'disetujui')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Disetujui
                                    </span>
                                @elseif(strtolower($item->status) == 'ditolak')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200" title="{{ $item->catatan_revisi }}">
                                        Revisi/Ditolak
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-50 text-slate-700 border border-slate-200">
                                        {{ ucfirst($item->status) }}
                                    </span>
                                @endif
                            </td>

                            <td class="py-4 px-6">
                                <div class="flex justify-center items-center gap-2">
                                    
                                    <a href="{{ route('skpd.show', $item->id) }}"
                                       class="bg-indigo-50 text-indigo-600 hover:bg-indigo-100 border border-indigo-100 px-3 py-1.5 rounded-md text-xs font-semibold transition-colors whitespace-nowrap"
                                       title="Lihat Detail">
                                        Detail
                                    </a>

                                    @php
                                        $role = strtolower(auth()->user()->role);
                                        $isOwner = ($item->user_id ?? null) === auth()->id();
                                        $canEdit = ($role === 'sekretaris' || ($isOwner && in_array(strtolower($item->status), ['pengajuan', 'ditolak'])));
                                        $canDelete = ($role === 'sekretaris' || $isOwner);
                                    @endphp

                                    @if($canEdit)
                                        <a href="{{ route('skpd.edit', $item->id) }}"
                                           class="bg-amber-50 text-amber-600 hover:bg-amber-100 border border-amber-100 px-3 py-1.5 rounded-md text-xs font-semibold transition-colors whitespace-nowrap">
                                            Edit
                                        </a>
                                    @endif

                                    @if($canDelete)
                                        <form action="{{ route('skpd.destroy', $item->id) }}"
                                              method="POST"
                                              onsubmit="event.preventDefault(); ConfirmModal.show({title:'Hapus SKPD',message:'Data SKPD yang dihapus tidak dapat dikembalikan. Yakin ingin melanjutkan?',variant:'danger',confirmText:'Ya, Hapus'}).then(ok=>{if(ok)this.submit()})"
                                              class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="bg-red-50 text-red-600 hover:bg-red-100 border border-red-100 px-3 py-1.5 rounded-md text-xs font-semibold transition-colors whitespace-nowrap">
                                                Hapus
                                            </button>
                                        </form>
                                    @endif

                                    @if(strtolower($item->status) == 'disetujui')
                                        <a href="{{ route('skpd.download-pdf', $item->id) }}"
                                           class="bg-emerald-50 text-emerald-600 hover:bg-emerald-100 border border-emerald-100 px-3 py-1.5 rounded-md text-xs font-semibold transition-colors whitespace-nowrap">
                                            Unduh
                                        </a>
                                    @endif

                                </div>
                            </td>

                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="py-12 px-6 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <svg class="w-12 h-12 mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    <p class="text-slate-500 font-medium">Belum ada data SKPD.</p>
                                    @if(strtolower(auth()->user()->role) !== 'dirut')
                                        <p class="text-sm mt-1">Silakan ajukan SKPD baru untuk memulai.</p>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($data->hasPages())
                <div class="p-6 border-t border-slate-100">
                    {{ $data->links() }}
                </div>
            @endif

        </div>
    </div>
</div>
@endsection