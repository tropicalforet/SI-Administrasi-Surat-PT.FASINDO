@extends('layouts.app')

@section('title', 'Laporan Disposisi')

@section('content')
<div class="p-4 sm:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto space-y-6">

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Laporan Disposisi</h2>
            <p class="text-sm text-slate-500 mt-1">Laporan rekapitulasi data disposisi surat beserta filternya.</p>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
        <form method="GET" action="{{ route('laporan.disposisi') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Awal (Masuk)</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full text-sm border-slate-300 rounded-lg focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Akhir (Masuk)</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full text-sm border-slate-300 rounded-lg focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Status</label>
                <select name="status" class="w-full text-sm border-slate-300 rounded-lg focus:border-blue-500 focus:ring-blue-500">
                    <option value="">-- Semua Status --</option>
                    <option value="menunggu" {{ request('status') == 'menunggu' ? 'selected' : '' }}>Menunggu (Diproses)</option>
                    <option value="selesai" {{ request('status') == 'selesai' ? 'selected' : '' }}>Selesai</option>
                </select>
            </div>
            <div class="md:col-span-3 flex gap-2 justify-end mt-2">
                <a href="{{ route('laporan.disposisi') }}" class="px-4 py-2 bg-slate-100 text-slate-600 hover:bg-slate-200 rounded-lg text-sm font-semibold transition-colors">Reset</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white hover:bg-blue-700 rounded-lg text-sm font-semibold transition-colors shadow-sm shadow-blue-500/30">Terapkan Filter</button>
                <a href="{{ route('laporan.disposisi.pdf', request()->all()) }}" target="_blank" class="px-4 py-2 bg-emerald-600 text-white hover:bg-emerald-700 rounded-lg text-sm font-semibold transition-colors shadow-sm shadow-emerald-500/30 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Download PDF
                </a>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 text-xs uppercase tracking-wider">
                        <th class="py-4 px-6 font-semibold">No</th>
                        <th class="py-4 px-6 font-semibold">Tgl. Disposisi</th>
                        <th class="py-4 px-6 font-semibold">Nomor Surat</th>
                        <th class="py-4 px-6 font-semibold">Dari</th>
                        <th class="py-4 px-6 font-semibold">Kepada</th>
                        <th class="py-4 px-6 font-semibold">Instruksi</th>
                        <th class="py-4 px-6 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="text-slate-700 text-sm divide-y divide-slate-100">
                    @forelse($data as $item)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-4 px-6">{{ $loop->iteration }}</td>
                            <td class="py-4 px-6 whitespace-nowrap">{{ \Carbon\Carbon::parse($item->created_at)->translatedFormat('d M Y') }}</td>
                            <td class="py-4 px-6 font-medium text-slate-800">{{ $item->suratMasuk?->nomor_surat ?? '-' }}</td>
                            <td class="py-4 px-6">{{ $item->dariUser->name ?? '-' }}</td>
                            <td class="py-4 px-6">{{ $item->kepadaUser->name ?? '-' }}</td>
                            <td class="py-4 px-6">{{ $item->instruksi }}</td>
                            <td class="py-4 px-6">
                                @if(strtolower($item->status) == 'menunggu')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-yellow-50 text-yellow-700 border border-yellow-200">Menunggu (Diproses)</span>
                                @elseif(strtolower($item->status) == 'selesai')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Selesai</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-50 text-slate-700 border border-slate-200">{{ ucfirst($item->status) }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                    <p class="font-medium text-slate-600">Tidak ada data laporan.</p>
                                    <p class="text-xs text-slate-400 mt-1">Ubah filter untuk melihat data lainnya.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
@endsection
