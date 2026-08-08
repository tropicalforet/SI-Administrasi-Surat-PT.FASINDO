@extends('layouts.app')

@section('content')
<div class="p-6 sm:p-8 max-w-5xl mx-auto">

    <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Notifikasi</h2>
            <p class="text-slate-500 text-sm mt-1.5">
                Pemberitahuan disposisi baru, pengingat tenggat, dan eskalasi keterlambatan.
            </p>
        </div>

        @if(auth()->user()->unreadNotifications->count() > 0)
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
                $belumDibaca = is_null($item->read_at);
                $warna = match($data['tipe'] ?? '') {
                    'disposisi_terlambat', 'disposisi_eskalasi' => 'bg-red-50 text-red-600',
                    'disposisi_mendekati_tenggat' => 'bg-amber-50 text-amber-600',
                    default => 'bg-blue-50 text-blue-600',
                };
            @endphp

            <div class="flex items-start gap-4 p-5 border-b border-slate-100 last:border-0 {{ $belumDibaca ? 'bg-blue-50/40' : '' }}">
                <div class="w-10 h-10 rounded-full {{ $warna }} flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="font-semibold text-slate-800">{{ $data['judul'] ?? 'Notifikasi' }}</p>
                        @if($belumDibaca)
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">Baru</span>
                        @endif
                    </div>

                    <p class="text-sm text-slate-600 mt-1">{{ $data['pesan'] ?? '' }}</p>

                    <div class="flex items-center gap-4 mt-3 text-xs">
                        <span class="text-slate-400">{{ $item->created_at->diffForHumans() }}</span>

                        <a href="{{ route('notifikasi.baca', $item->id) }}"
                           class="text-blue-600 hover:text-blue-800 font-semibold">
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
