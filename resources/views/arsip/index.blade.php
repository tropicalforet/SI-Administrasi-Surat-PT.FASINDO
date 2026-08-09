@extends('layouts.app')

@section('content')
<div class="p-6 sm:p-8">

    <div class="mb-6">
        <h2 class="text-2xl font-bold text-slate-800">Arsip Terhapus</h2>
        <p class="text-slate-500 text-sm mt-1.5">
            Dokumen yang dihapus disimpan di sini dan masih dapat dipulihkan. Berkas lampirannya tetap tersimpan di server.
        </p>
    </div>

    <div class="flex flex-wrap gap-2 mb-6">
        @foreach($daftarJenis as $kunci => $item)
            <a href="{{ route('arsip.index', ['jenis' => $kunci]) }}"
               class="px-4 py-2 rounded-xl text-sm font-semibold transition-all border {{ $jenis === $kunci ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
                {{ $item['label'] }}
                <span class="ml-1.5 text-xs {{ $jenis === $kunci ? 'text-blue-100' : 'text-slate-400' }}">
                    {{ $jumlah[$kunci] }}
                </span>
            </a>
        @endforeach
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
        @forelse($data as $item)
            <div class="flex flex-wrap items-start justify-between gap-4 p-5 border-b border-slate-100 last:border-0">
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-slate-800">
                        {{ $konfigurasi['nomor'] === 'id' ? 'Disposisi #' . $item->id : $item->{$konfigurasi['nomor']} }}
                    </p>
                    <p class="text-sm text-slate-600 mt-1 line-clamp-2">
                        {{ $item->{$konfigurasi['perihal']} ?? '-' }}
                    </p>
                    <p class="text-xs text-slate-400 mt-2">
                        Dihapus {{ $item->deleted_at->diffForHumans() }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <form action="{{ route('arsip.restore', [$jenis, $item->id]) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                                class="px-4 py-2 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-100 rounded-lg text-xs font-semibold transition-colors">
                            Pulihkan
                        </button>
                    </form>

                    <form action="{{ route('arsip.force-delete', [$jenis, $item->id]) }}"
                          method="POST"
                          class="inline"
                          onsubmit="event.preventDefault(); ConfirmModal.show({title:'Hapus Permanen',message:'Dokumen ini akan dihapus selamanya dan tidak dapat dipulihkan lagi. Lanjutkan?',variant:'danger',confirmText:'Ya, Hapus Permanen'}).then(ok=>{if(ok)this.submit()})">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="px-4 py-2 bg-red-50 text-red-600 hover:bg-red-100 border border-red-100 rounded-lg text-xs font-semibold transition-colors">
                            Hapus Permanen
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="p-12 text-center text-slate-500 flex flex-col items-center">
                <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                <p>Tidak ada {{ strtolower($konfigurasi['label']) }} yang terhapus.</p>
            </div>
        @endforelse
    </div>

    @if($data->hasPages())
        <div class="mt-6">
            {{ $data->links() }}
        </div>
    @endif
</div>
@endsection
