@extends('layouts.app')

@section('content')
<div class="py-10 px-4 sm:px-6 max-w-3xl mx-auto">

    <div class="mb-4">
        <a href="{{ route('skpd.show', $skpd->id) }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 hover:text-blue-600 rounded-lg text-sm font-medium text-slate-600 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali
        </a>
    </div>

    <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-sm border border-slate-100">

        <div class="mb-8 border-b border-slate-100 pb-5">
            <h2 class="text-2xl font-bold text-slate-800">Edit SKPD</h2>
            <p class="text-slate-500 text-sm mt-1.5">
                Nomor <strong>{{ $skpd->nomor_skpd }}</strong> atas nama {{ $skpd->nama_pegawai }}.
            </p>
        </div>

        @if($skpd->status === 'ditolak' && $skpd->catatan_revisi)
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                <p class="text-xs font-bold text-red-700 uppercase tracking-wider mb-1">Catatan Penolakan</p>
                <p class="text-sm text-red-700 italic">"{{ $skpd->catatan_revisi }}"</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                <p class="text-sm text-red-600 font-medium">Terdapat kesalahan pada form. Silakan periksa isian Anda.</p>
            </div>
        @endif

        <form action="{{ route('skpd.update', $skpd->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            @include('skpd.partials.form', [
                'users' => collect(),
                'nilai' => [
                    'user_id'           => $skpd->user_id,
                    'jenis'             => old('jenis', $skpd->jenis),
                    'tujuan_dinas'      => old('tujuan_dinas', $skpd->tujuan_dinas),
                    'keperluan'         => old('keperluan', $skpd->keperluan),
                    'tanggal_berangkat' => old('tanggal_berangkat', $skpd->tanggal_berangkat),
                    'tanggal_kembali'   => old('tanggal_kembali', $skpd->tanggal_kembali),
                ],
            ])

            <div class="flex gap-3 pt-4 border-t border-slate-100">
                <button type="submit"
                        class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-semibold text-sm transition-colors shadow-sm">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
