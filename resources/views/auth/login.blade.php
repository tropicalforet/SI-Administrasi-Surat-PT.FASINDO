<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login E-Office - PT. Fasadetama Indonesia</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-100 flex items-center justify-center min-h-screen p-4 sm:p-6 lg:p-8">

    <div class="bg-white rounded-3xl shadow-2xl flex w-full max-w-5xl overflow-hidden min-h-[600px]">
        
        <div class="hidden md:flex md:w-1/2 bg-gradient-to-br from-blue-700 via-blue-600 to-indigo-800 p-12 flex-col justify-center relative overflow-hidden text-white">
            
            <div class="absolute top-0 left-0 w-72 h-72 bg-white opacity-10 rounded-full mix-blend-overlay filter blur-3xl transform -translate-x-1/2 -translate-y-1/2"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-indigo-400 opacity-20 rounded-full mix-blend-overlay filter blur-3xl transform translate-x-1/3 translate-y-1/3"></div>

            <div class="relative z-10 flex items-center gap-4 mb-12">
                <div class="w-14 h-14 bg-white rounded-xl flex items-center justify-center shadow-lg p-2">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo PT Fasadetama" class="w-full h-full object-contain">
                </div>
                <div>
                    <h1 class="text-xl font-bold tracking-wide">E-Office</h1>
                    <p class="text-blue-200 text-sm">PT. Fasadetama Indonesia</p>
                </div>
            </div>

            <div class="relative z-10">
                <h2 class="text-4xl font-bold leading-tight mb-6">
                    Kelola Administrasi <br>Lebih Cerdas & Cepat
                </h2>
                <p class="text-blue-100 text-lg leading-relaxed max-w-md">
                    Sistem informasi manajemen persuratan, disposisi, dan perjalanan dinas yang terintegrasi dalam satu pintu.
                </p>
            </div>

        </div>

        <div class="w-full md:w-1/2 p-8 sm:p-12 lg:p-16 flex flex-col justify-center">
            
            <div class="max-w-sm w-full mx-auto">
                
                <div class="md:hidden flex items-center gap-3 mb-8">
                    <div class="w-10 h-10 flex items-center justify-center">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo PT Fasadetama" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-slate-800 tracking-wide">E-Office</h1>
                    </div>
                </div>

                <div class="mb-10">
                    <h2 class="text-3xl font-bold text-slate-800 mb-2">Selamat Datang 👋</h2>
                    <p class="text-slate-500">Silakan masukkan kredensial akun Anda untuk mengakses sistem E-Office.</p>
                </div>

                @if($errors->any())
                <div class="bg-red-50 text-red-600 border border-red-200 text-sm p-4 rounded-xl mb-6 flex items-start gap-3">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <div>
                        <span class="font-bold">Login Gagal!</span> Pastikan email dan password Anda benar.
                    </div>
                </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">Email Perusahaan</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                </svg>
                            </div>
                            <input type="email" name="email" id="email" required 
                                   class="pl-11 w-full px-4 py-3.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition duration-200 outline-none text-slate-800 placeholder-slate-400" 
                                   placeholder="nama@fasadetama.co.id">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-semibold text-slate-700 mb-2">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input type="password" name="password" id="password" required 
                                   class="pl-11 w-full px-4 py-3.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition duration-200 outline-none text-slate-800 placeholder-slate-400" 
                                   placeholder="••••••••">
                        </div>
                    </div>

                    <div class="flex items-center mt-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="remember" class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                            <span class="text-sm text-slate-600">Ingat Saya</span>
                        </label>
                    </div>

                    <div class="pt-4">
                        <button type="submit" 
                                class="flex items-center justify-center gap-2 w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3.5 px-4 rounded-xl transition duration-200 shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50">
                            Masuk ke Sistem
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </button>
                    </div>
                    
                </form>

                <p class="text-center text-sm text-slate-500 mt-10">
                    &copy; {{ date('Y') }} PT. Fasadetama Indonesia.<br>All rights reserved.
                </p>

            </div>
        </div>
    </div>

</body>
</html>