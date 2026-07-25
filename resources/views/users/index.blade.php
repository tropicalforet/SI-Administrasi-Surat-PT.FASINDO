@extends('layouts.app')

@section('content')
<div class="p-8">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
        
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-slate-800">Manajemen User</h2>
            <a href="{{ route('users.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                + Tambah User
            </a>
        </div>

        <form method="GET" action="{{ route('users.index') }}" class="mb-5">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau email..." 
                   class="border border-slate-200 rounded-lg px-4 py-2 w-80 focus:ring-2 focus:ring-blue-500 outline-none">
            <button class="bg-slate-700 hover:bg-slate-800 text-white px-4 py-2 rounded-lg transition-colors">Cari</button>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full border border-slate-200">
                <thead class="bg-slate-100">
                    <tr>
                        <th class="border px-4 py-2 text-left">No</th>
                        <th class="border px-4 py-2 text-left">Nama</th>
                        <th class="border px-4 py-2 text-left">Email</th>
                        <th class="border px-4 py-2 text-left">Role</th>
                        <th class="border px-4 py-2 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $item)
                    <tr>
                        <td class="border px-4 py-2">{{ $loop->iteration }}</td>
                        <td class="border px-4 py-2">{{ $item->name }}</td>
                        <td class="border px-4 py-2">{{ $item->email }}</td>
                        <td class="border px-4 py-2">{{ ucfirst($item->role) }}</td>
                        <td class="border px-4 py-2 text-center">
                            <a href="{{ route('users.edit', $item->id) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded transition-colors mr-2">Edit</a>
                            @if($item->id != auth()->id())
                                <form action="{{ route('users.destroy', $item->id) }}" method="POST" onsubmit="event.preventDefault(); ConfirmModal.show({title:'Hapus Pengguna',message:'Akun pengguna yang dihapus tidak dapat dikembalikan. Yakin ingin melanjutkan?',variant:'danger',confirmText:'Ya, Hapus'}).then(ok=>{if(ok)this.submit()})" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded transition-colors">
                                        Hapus
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-6 text-slate-500">Tidak ada data.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</div>
@endsection