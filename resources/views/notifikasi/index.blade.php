@extends('layouts.app')

@section('content')
<div class="p-6 sm:p-8 max-w-5xl mx-auto">

    <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3 flex-wrap">
                <h2 class="text-2xl font-bold text-slate-800">Notifikasi</h2>

                @if($belumDibaca > 0)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-600 text-white text-xs font-bold">
                        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                        {{ $belumDibaca }} belum dibaca
                    </span>
                @endif
            </div>

            <p class="text-slate-500 text-sm mt-1.5">
                Pemberitahuan disposisi baru, pengingat tenggat, dan eskalasi keterlambatan.
            </p>
        </div>

        @if($belumDibaca > 0)
            <form action="{{ route('notifikasi.baca-semua') }}" method="POST">
                @csrf
                <button type="submit"
                        class="px-5 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl text-sm font-semibold transition-all shadow-sm">
                    Tandai semua dibaca
                </button>
            </form>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
        @forelse($notifikasi as $item)
            @php
                $data = $item->data;
                $baru = is_null($item->read_at);

                // Warna ikon menandai jenis kejadiannya; yang sudah dibaca
                // diredam menjadi abu-abu agar perhatian jatuh ke yang baru.
                $warna = $baru
                    ? match($data['tipe'] ?? '') {
                        'disposisi_terlambat', 'disposisi_eskalasi' => 'bg-red-600 text-white',
                        'disposisi_mendekati_tenggat' => 'bg-amber-500 text-white',
                        // Perintah dinas menuntut tindakan, bukan sekadar kabar.
                        'skpd_penugasan_diterima' => 'bg-emerald-600 text-white',
                        default => 'bg-blue-600 text-white',
                    }
                    : 'bg-slate-100 text-slate-400';
            @endphp

            <div data-status="{{ $baru ? 'belum-dibaca' : 'dibaca' }}"
                 class="flex items-start gap-4 p-5 border-b border-slate-100 last:border-0 border-l-4 transition-colors
                        {{ $baru ? 'bg-blue-50 border-l-blue-600' : 'bg-white border-l-transparent' }}">

                <div class="w-10 h-10 rounded-full {{ $warna }} flex items-center justify-center flex-shrink-0 relative">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>

                    @if($baru)
                        <span class="absolute -top-0.5 -right-0.5 w-3.5 h-3.5 rounded-full bg-red-500 border-2 border-blue-50"></span>
                    @endif
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="{{ $baru ? 'font-bold text-slate-900' : 'font-medium text-slate-500' }}">
                            {{ $data['judul'] ?? 'Notifikasi' }}
                        </p>
                        @if($baru)
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-blue-600 text-white">Baru</span>
                        @endif
                    </div>

                    <p class="text-sm mt-1 {{ $baru ? 'text-slate-700' : 'text-slate-400' }}">{{ $data['pesan'] ?? '' }}</p>

                    <div class="flex items-center gap-4 mt-3 text-xs">
                        <span class="{{ $baru ? 'text-blue-700 font-semibold' : 'text-slate-400' }}">
                            {{ $item->created_at->diffForHumans() }}
                        </span>

                        <a href="{{ route('notifikasi.baca', $item->id) }}"
                           class="{{ $baru ? 'text-white bg-blue-600 hover:bg-blue-700 px-3 py-1.5 rounded-lg' : 'text-blue-600 hover:text-blue-800' }} font-semibold">
                            Buka
                        </a>

                        <form action="{{ route('notifikasi.destroy', $item->id) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-slate-400 hover:text-red-600 font-semibold">
                                Hapus
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="p-12 text-center text-slate-500 flex flex-col items-center">
                <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                <p>Belum ada notifikasi.</p>
            </div>
        @endforelse
    </div>

    @if($notifikasi->hasPages())
        <div class="mt-6">
            {{ $notifikasi->links() }}
        </div>
    @endif
</div>
@endsection
