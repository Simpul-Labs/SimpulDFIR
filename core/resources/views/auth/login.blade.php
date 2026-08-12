<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Simpul DFIR Master Node</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .grid-bg {
            background-size: 40px 40px;
            background-image: linear-gradient(to right, rgba(255, 255, 255, 0.05) 1px, transparent 1px),
                              linear-gradient(to bottom, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-300 min-h-screen flex items-center justify-center relative overflow-hidden">
    
    <!-- Background Elements -->
    <div class="absolute inset-0 z-0">
        <div class="absolute inset-0 bg-slate-950 grid-bg opacity-20"></div>
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-cyan-900/20 rounded-full blur-3xl"></div>
        <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-indigo-900/20 rounded-full blur-3xl"></div>
    </div>

    <!-- Login Container -->
    <div class="relative z-10 w-full max-w-md px-6">
        <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800 rounded-2xl shadow-2xl p-8">
            
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-cyan-500/20 to-indigo-500/20 border border-cyan-500/30 mb-4 shadow-[0_0_15px_rgba(6,182,212,0.3)]">
                    <svg class="w-8 h-8 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-white tracking-tight">Simpul DFIR</h1>
                <p class="text-slate-400 text-sm mt-1">Master Node Authentication</p>
            </div>

            @if ($errors->any())
                <div class="bg-red-900/30 border border-red-500/30 rounded-lg p-4 mb-6">
                    <ul class="text-sm text-red-400 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1.5" for="username">Username</label>
                    <input type="text" id="username" name="username" value="{{ old('username') }}"
                        class="w-full bg-slate-950/50 border border-slate-700 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-colors"
                        placeholder="Enter username" required autofocus>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1.5" for="password">Password</label>
                    <input type="password" id="password" name="password"
                        class="w-full bg-slate-950/50 border border-slate-700 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-colors"
                        placeholder="••••••••" required>
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-cyan-600 to-indigo-600 hover:from-cyan-500 hover:to-indigo-500 text-white font-semibold py-2.5 rounded-lg shadow-lg shadow-cyan-900/20 transition-all active:scale-[0.98]">
                    Authenticate
                </button>
            </form>

            <div class="mt-6 text-center text-xs text-slate-500">
                Authorized personnel only. Access is monitored.
            </div>
        </div>
    </div>

</body>
</html>
