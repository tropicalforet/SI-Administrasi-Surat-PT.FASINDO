@extends('layouts.app')

@section('content')
<div class="p-6 sm:p-8 max-w-5xl mx-auto">

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('surat-masuk.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 hover:text-blue-600 rounded-lg text-sm font-medium text-slate-600 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali ke Daftar
        </a>

        @php
            $pengguna = auth()->user();
            $peran = strtolower($pengguna->role);

            // Yang boleh mendisposisikan: pimpinan, atau siapa pun yang punya
            // bawahan di unitnya.
            $bolehDisposisi = $pengguna->hasPermission('akses_disposisi')
                && (in_array($peran, ['dirut', 'sekretaris']) || !empty($pengguna->rolesBawahan()));
        @endphp

        <div class="flex items-center gap-3">
            @if($surat_masuk->file)
                <a href="{{ asset('storage/' . $surat_masuk->file) }}" target="_blank"
                   class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 px-5 py-2 rounded-lg text-sm font-semibold transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    Buka Lampiran
                </a>
            @endif

            @if($bolehDisposisi)
                <a href="{{ route('disposisi.create', $surat_masuk->id) }}"
                   class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-semibold transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    Disposisikan
                </a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-700 text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm font-medium">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden mb-6">
        <div class="p-6 sm:p-8 border-b border-slate-100 bg-slate-50/50">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Nomor Surat</p>
                    <h1 class="text-2xl font-bold text-slate-800 break-words">{{ $surat_masuk->nomor_surat }}</h1>
                </div>

                <div class="flex items-center gap-2">
                    @include('surat_masuk.partials.badge-sifat', ['surat' => $surat_masuk])
                    @include('surat_masuk.partials.badge-status', ['surat' => $surat_masuk])
                </div>
            </div>
        </div>

        <dl class="p-6 sm:p-8 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5">
            <div>
                <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Pengirim</dt>
                <dd class="text-slate-800 font-medium">{{ $surat_masuk->pengirim }}</dd>
            </div>

            <div>
                <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Ditujukan Kepada</dt>
                <dd class="text-slate-800 font-medium">
                    {{ $surat_masuk->label_penerima }}
                    @if($surat_masuk->penerima_role)
                        <span class="ml-1 text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700">Role</span>
                    @endif
                </dd>
            </div>

            <div>
                <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Tanggal Surat</dt>
                <dd class="text-slate-800">{{ \Carbon\Carbon::parse($surat_masuk->tanggal_surat)->translatedFormat('d F Y') }}</dd>
            </div>

            <div>
                <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Diterima Melalui</dt>
                <dd class="text-slate-800">{{ $surat_masuk->label_jalur }}</dd>
            </div>

            <div>
                <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Kategori</dt>
                <dd class="text-slate-800">{{ $surat_masuk->kategori_surat ?? '-' }}</dd>
            </div>

            <div>
                <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Diagendakan</dt>
                <dd class="text-slate-800">{{ $surat_masuk->created_at->translatedFormat('d F Y, H:i') }}</dd>
            </div>

            <div class="sm:col-span-2">
                <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Perihal</dt>
                <dd class="text-slate-800 leading-relaxed">{{ $surat_masuk->perihal }}</dd>
            </div>
        </dl>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
            <h2 class="text-lg font-bold text-slate-800">Riwayat Disposisi</h2>
            <p class="text-sm text-slate-500 mt-1">Perjalanan surat ini sejak diagendakan.</p>
        </div>

        <div class="p-6">
            @forelse($riwayat as $item)
                @php
                    $warna = match($item->status) {
                        'selesai'  => 'bg-emerald-500',
                        'diproses' => 'bg-blue-500',
                        default    => 'bg-amber-500',
                    };
                @endphp

                <div class="flex gap-4 {{ !$loop->last ? 'pb-6' : '' }} relative">
                    @if(!$loop->last)
                        <div class="absolute left-[7px] top-5 bottom-0 w-px bg-slate-200"></div>
                    @endif

                    <div class="w-4 h-4 rounded-full {{ $warna }} flex-shrink-0 mt-1 ring-4 ring-white"></div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="font-semibold text-slate-800">
                                {{ $item->dariUser->name ?? '-' }}
                                <span class="text-slate-400 font-normal">&rarr;</span>
                                {{ $item->kepadaUser->name ?? '-' }}
                            </span>
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                                {{ ucfirst($item->status) }}
                            </span>
                        </div>

                        <p class="text-sm text-slate-600 leading-relaxed">{{ $item->instruksi }}</p>

                        <div class="flex flex-wrap items-center gap-3 mt-2 text-xs text-slate-400">
                            <span>{{ \Carbon\Carbon::parse($item->tanggal_disposisi)->translatedFormat('d M Y, H:i') }}</span>

                            @if($item->batas_waktu)
                                <span>&bull; Tenggat {{ $item->batas_waktu->translatedFormat('d M Y') }}</span>
                            @endif
                        </div>

                        @if($item->catatan_tindak_lanjut)
                            <div class="mt-2 p-3 bg-slate-50 border border-slate-100 rounded-lg">
                                <p class="text-xs font-semibold text-slate-500 mb-1">Tindak lanjut</p>
                                <p class="text-sm text-slate-700">{{ $item->catatan_tindak_lanjut }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="py-8 text-center text-slate-500">
                    <svg class="w-12 h-12 text-slate-300 mb-3 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    <p>Surat ini belum didisposisikan.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
