<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code', 'Error') – Kue Pandan Asli</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#f2f6f5] min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-lg p-8 text-center">
        <div class="text-6xl font-bold text-[#8BA870]">@yield('code')</div>
        <h1 class="mt-2 text-xl font-bold text-[#2C3E50]">@yield('title')</h1>
        <p class="mt-2 text-sm text-gray-600">@yield('message')</p>
        <div class="mt-6 flex flex-col gap-2">
            <a href="{{ url('/') }}" class="px-5 py-2 rounded-full bg-[#8BA870] text-white font-semibold hover:opacity-90">Kembali ke Beranda</a>
            <a href="https://wa.me/6285113658557?text=Halo%20Kue%20Pandan%20Asli,%20saya%20butuh%20bantuan" target="_blank" rel="noopener" class="px-5 py-2 rounded-full bg-gray-100 text-[#2C3E50] font-semibold hover:bg-gray-200">Hubungi Admin via WA</a>
        </div>
    </div>
</body>
</html>
