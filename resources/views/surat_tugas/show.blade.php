@extends('layouts.app')

@section('content')
<div class="py-8 px-4 sm:px-8 max-w-5xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6 flex justify-between items-center no-print">
        <a href="{{ route('surat-tugas.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-medium transition-colors">
           <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali
        </a>

        <div class="flex items-center gap-3">
            @if($surat_tugas->status == 'draft' && strtolower(auth()->user()->role) == 'dirut')
                <form action="{{ route('surat-tugas.approve', $surat_tugas->id) }}" method="POST" onsubmit="event.preventDefault(); ConfirmModal.show({title:'Terbitkan Surat Tugas',message:'Setelah diterbitkan, Surat Tugas tidak dapat diedit/dihapus lagi. Lanjutkan?',confirmText:'Ya, Terbitkan'}).then(ok=>{if(ok)this.submit()})">
                    @csrf
                    @method('PUT')
                    <button type="submit" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 rounded-lg text-sm font-semibold transition-colors shadow-sm">
                        Terbitkan Surat Tugas
                    </button>
                </form>
            @endif

            @if($surat_tugas->status == 'diterbitkan')
                @if($surat_tugas->skpd)
                    <a href="{{ route('skpd.show', $surat_tugas->skpd->id) }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-semibold transition-colors shadow-sm">
                        Lihat SKPD
                    </a>
                @else
                    @if(in_array(strtolower(auth()->user()->role), ['sekretaris', 'dirut']) || auth()->id() == $surat_tugas->user_id)
                    <a href="{{ route('skpd.create', ['surat_tugas_id' => $surat_tugas->id]) }}" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-lg text-sm font-semibold transition-colors shadow-sm">
                        Buat SKPD
                    </a>
                    @endif
                @endif
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-700 text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white p-8 sm:p-12 rounded-xl shadow-sm border border-slate-200">
        <div class="text-center mb-8 border-b border-slate-200 pb-6">
            <h1 class="text-2xl font-bold text-slate-800 uppercase tracking-wide">Surat Tugas</h1>
            <p class="text-slate-500 mt-1">Nomor: {{ $surat_tugas->nomor_surat_tugas }}</p>
            
            <div class="mt-4">
                @if($surat_tugas->status == 'draft')
                    <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-800">DRAFT (Belum Diterbitkan)</span>
                @else
                    <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold bg-emerald-100 text-emerald-800">DITERBITKAN</span>
                @endif
            </div>
        </div>

        <div class="space-y-6 text-slate-700">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="font-semibold text-slate-500">Ditugaskan Kepada</div>
                <div class="md:col-span-2 font-medium text-slate-900">{{ $surat_tugas->user->name ?? '-' }}</div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="font-semibold text-slate-500">Oleh (Pemberi Tugas)</div>
                <div class="md:col-span-2 font-medium text-slate-900">{{ $surat_tugas->penugasOleh->name ?? 'Pengajuan Mandiri (Bottom-up)' }}</div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="font-semibold text-slate-500">Perihal Tugas</div>
                <div class="md:col-span-2 font-medium text-slate-900">{{ $surat_tugas->perihal_tugas }}</div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="font-semibold text-slate-500">Tujuan Instansi/Lokasi</div>
                <div class="md:col-span-2 font-medium text-slate-900">{{ $surat_tugas->tujuan }}</div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="font-semibold text-slate-500">Waktu Pelaksanaan</div>
                <div class="md:col-span-2 font-medium text-slate-900">
                    {{ \Carbon\Carbon::parse($surat_tugas->tanggal_mulai)->translatedFormat('d F Y') }}
                    s/d
                    {{ \Carbon\Carbon::parse($surat_tugas->tanggal_selesai)->translatedFormat('d F Y') }}
                </div>
            </div>

            @if($surat_tugas->file)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 border-t border-slate-100">
                <div class="font-semibold text-slate-500">Lampiran</div>
                <div class="md:col-span-2">
                    <a href="{{ Storage::url($surat_tugas->file) }}" target="_blank" class="text-blue-600 hover:underline inline-flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                        Lihat Lampiran Dasar Penugasan
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
