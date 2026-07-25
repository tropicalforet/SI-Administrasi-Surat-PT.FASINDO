@extends('layouts.app')

@section('content')

<div class="bg-white rounded-xl shadow p-6 max-w-3xl">

    <h2 class="text-2xl font-bold mb-6">
        Edit User
    </h2>

    <form action="{{ route('users.update', $user->id) }}" method="POST">

    @csrf
    @method('PUT')

        <div class="mb-4">
            <label class="block mb-2 font-medium">
                Nama
            </label>

            <input
                type="text"
                name="name"
                value="{{ old('name', $user->name) }}"
                class="w-full border rounded-lg px-4 py-2"
                required>
        </div>

        <div class="mb-4">
            <label class="block mb-2 font-medium">
                Email
            </label>

            <input
                type="email"
                name="email"
                value="{{ old('email', $user->email) }}"
                class="w-full border rounded-lg px-4 py-2"
                required>
        </div>

        <div class="mb-4">
            <label class="block mb-2 font-medium">
                Password
            </label>

            <input
                type="password"
                name="password"
                class="w-full border rounded-lg px-4 py-2"
                placeholder="Kosongkan jika tidak ingin mengubah password">
        </div>

        <div class="mb-6">
            <label class="block mb-2 font-medium">
                Role
            </label>

            <select
                name="role"
                class="w-full border rounded-lg px-4 py-2"
                required>

                <option value="">-- Pilih Role --</option>

                <option value="admin">
                    {{ $user->role == 'admin' ? 'selected' : '' }}>
                    Administrator
                </option>

                <option value="sekretaris">
                    {{ $user->role == 'sekretaris' ? 'selected' : '' }}>
                    Sekretaris
                </option>

                <option value="dirut">
                    {{ $user->role == 'dirut' ? 'selected' : '' }}>
                    Direktur Utama
                </option>

                <option value="direktur1">
                    {{ $user->role == 'direktur1' ? 'selected' : '' }}>
                    Direktur I
                </option>

                <option value="direktur2">
                    {{ $user->role == 'direktur2' ? 'selected' : '' }}>
                    Direktur II
                </option>

                <option value="staff">
                    {{ $user->role == 'staff' ? 'selected' : '' }}>
                    Staff
                </option>

            </select>

        </div>

        <div class="flex gap-3">

            <button
                class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg">

                Update

            </button>

            <a href="{{ route('users.index') }}"
               class="bg-gray-400 hover:bg-gray-500 text-white px-5 py-2 rounded-lg">

                Kembali

            </a>

        </div>

    </form>

</div>

@endsection