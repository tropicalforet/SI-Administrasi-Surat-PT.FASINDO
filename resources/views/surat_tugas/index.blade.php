@extends('layouts.app')

@section('content')
<div class="p-4 sm:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4 bg-white">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800">
                        Surat Tugas
                    </h2>
                    <p class="text-sm text-slate-500 mt-1">
                        Kelola data Surat Tugas pegawai
                    </p>
                </div>
                <a href="{{ route('surat-tugas.create') }}" class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-5 rounded-xl transition duration-200 shadow-md shadow-blue-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Buat Surat Tugas
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                            <th class="py-4 px-6 font-semibold w-16">No</th>
                            <th class="py-4 px-6 font-semibold">Nomor</th>
                            <th class="py-4 px-6 font-semibold">Pegawai Ditugaskan</th>
                            <th class="py-4 px-6 font-semibold">Tujuan</th>
                            <th class="py-4 px-6 font-semibold">Periode</th>
                            <th class="py-4 px-6 font-semibold text-center">Status</th>
                            <th class="py-4 px-6 font-semibold text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-slate-700 text-sm divide-y divide-slate-100">
                        @forelse($data as $item)
                        <tr class="hover:bg-slate-50/80 transition-colors duration-150 group">
                            <td class="py-4 px-6 text-slate-500">{{ ($data->currentPage() - 1) * $data->perPage() + $loop->iteration }}</td>
                            <td class="py-4 px-6 font-semibold text-slate-800">{{ $item->nomor_surat_tugas }}</td>
                            <td class="py-4 px-6">{{ $item->user->name ?? '-' }}</td>
                            <td class="py-4 px-6">{{ $item->tujuan }}</td>
                            <td class="py-4 px-6">{{ \Carbon\Carbon::parse($item->tanggal_mulai)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($item->tanggal_selesai)->format('d/m/Y') }}</td>
                            <td class="py-4 px-6 text-center">
                                @if($item->status == 'draft')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-yellow-50 text-yellow-700 border border-yellow-200">Draft</span>
                                @elseif($item->status == 'diterbitkan')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Diterbitkan</span>
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex justify-center items-center gap-2">
                                    <a href="{{ route('surat-tugas.show', $item->id) }}" class="bg-indigo-50 text-indigo-600 hover:bg-indigo-100 border border-indigo-100 px-3 py-1.5 rounded-md text-xs font-semibold transition-colors">Detail</a>
                                    
                                    @php
                                        $role = strtolower(auth()->user()->role);
                                        $canEdit = $item->status == 'draft' && ($role == 'sekretaris' || $item->user_id == auth()->id() || $item->ditugaskan_oleh == auth()->id());
                                    @endphp

                                    @if($canEdit)
                                        <a href="{{ route('surat-tugas.edit', $item->id) }}" class="bg-amber-50 text-amber-600 hover:bg-amber-100 border border-amber-100 px-3 py-1.5 rounded-md text-xs font-semibold transition-colors">Edit</a>
                                        <form action="{{ route('surat-tugas.destroy', $item->id) }}" method="POST" onsubmit="event.preventDefault(); ConfirmModal.show({title:'Hapus Surat Tugas',message:'Yakin?',variant:'danger',confirmText:'Ya'}).then(ok=>{if(ok)this.submit()})" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="bg-red-50 text-red-600 hover:bg-red-100 border border-red-100 px-3 py-1.5 rounded-md text-xs font-semibold transition-colors">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-12 px-6 text-center text-slate-500">Belum ada data Surat Tugas.</td>
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
