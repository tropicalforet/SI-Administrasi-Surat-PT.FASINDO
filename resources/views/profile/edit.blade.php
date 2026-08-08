@extends('layouts.app')

@section('content')
<div class="p-8 max-w-3xl">

    <h2 class="text-2xl font-bold text-slate-800 mb-6">Pengaturan Profil</h2>

    {{-- Info Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-6">
        <div class="flex items-center gap-4 mb-2">
            <div class="w-14 h-14 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xl font-bold">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <div>
                <p class="text-lg font-semibold text-slate-800">{{ auth()->user()->name }}</p>
                <p class="text-sm text-slate-500">{{ ucfirst(auth()->user()->role) }} &middot; {{ auth()->user()->email }}</p>
            </div>
        </div>
    </div>

    {{-- Update Profil --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-6">
        <h3 class="text-lg font-semibold text-slate-800 mb-4">Informasi Profil</h3>
        <p class="text-sm text-slate-500 mb-5">Perbarui nama dan alamat email akun Anda.</p>

        <form action="{{ route('profile.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block mb-2 font-medium text-sm text-slate-700">Nama</label>
                <input type="text"
                       name="name"
                       value="{{ old('name', $user->name) }}"
                       class="w-full border border-slate-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none transition-all"
                       required>
                @error('name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label class="block mb-2 font-medium text-sm text-slate-700">Email</label>
                <input type="email"
                       name="email"
                       value="{{ old('email', $user->email) }}"
                       class="w-full border border-slate-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none transition-all"
                       required>
                @error('email')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg font-medium transition-colors">
                Simpan Perubahan
            </button>
        </form>
    </div>

    {{-- Update Password --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
        <h3 class="text-lg font-semibold text-slate-800 mb-4">Ubah Password</h3>
        <p class="text-sm text-slate-500 mb-5">Pastikan akun Anda menggunakan password yang panjang dan aman.</p>

        <form action="{{ route('profile.password') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block mb-2 font-medium text-sm text-slate-700">Password Saat Ini</label>
                <input type="password"
                       name="current_password"
                       class="w-full border border-slate-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none transition-all"
                       required>
                @error('current_password')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block mb-2 font-medium text-sm text-slate-700">Password Baru</label>
                <input type="password"
                       name="password"
                       class="w-full border border-slate-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none transition-all"
                       required>
                @error('password')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label class="block mb-2 font-medium text-sm text-slate-700">Konfirmasi Password Baru</label>
                <input type="password"
                       name="password_confirmation"
                       class="w-full border border-slate-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none transition-all"
                       required>
            </div>

            <button class="bg-slate-800 hover:bg-slate-900 text-white px-5 py-2.5 rounded-lg font-medium transition-colors">
                Ubah Password
            </button>
        </form>
    </div>

</div>
@endsection
