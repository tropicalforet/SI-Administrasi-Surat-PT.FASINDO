@extends('layouts.app')

@section('content')
<div class="py-10 px-4 sm:px-6 max-w-3xl mx-auto">

    <div class="mb-4">
        <a href="{{ route('skpd.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 hover:text-blue-600 rounded-lg text-sm font-medium text-slate-600 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali ke Data SKPD
        </a>
    </div>

    <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-sm border border-slate-100">

        <div class="mb-8 border-b border-slate-100 pb-5">
            <h2 class="text-2xl font-bold text-slate-800">Buat SKPD</h2>
            <p class="text-slate-500 text-sm mt-1.5">
                @if($users->isNotEmpty())
                    Tugaskan pegawai untuk perjalanan dinas atau tugas internal.
                @else
                    Ajukan usulan penugasan untuk diri Anda. Usulan akan diteruskan
                    ke direktur unit Anda untuk disetujui.
                @endif
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                <p class="text-sm text-red-600 font-medium">Terdapat kesalahan pada form. Silakan periksa isian Anda.</p>
            </div>
        @endif

        <form action="{{ route('skpd.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            @include('skpd.partials.form', [
                'users' => $users,
                'nilai' => [
                    'user_id'           => old('user_id'),
                    'jenis'             => old('jenis', 'perjalanan_dinas'),
                    'tujuan_dinas'      => old('tujuan_dinas'),
                    'keperluan'         => old('keperluan'),
                    'tanggal_berangkat' => old('tanggal_berangkat'),
                    'tanggal_kembali'   => old('tanggal_kembali'),
                ],
            ])

            <div class="flex flex-wrap gap-3 pt-4 border-t border-slate-100">
                <button type="submit" name="aksi" value="ajukan"
                        class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-semibold text-sm transition-colors shadow-sm">
                    Simpan &amp; Ajukan
                </button>

                <button type="submit" name="aksi" value="draft"
                        class="px-6 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl font-semibold text-sm transition-colors">
                    Simpan sebagai Draft
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
