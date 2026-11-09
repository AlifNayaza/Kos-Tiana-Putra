{{-- Memuat Chart.js ramping HANYA di halaman yang memakai grafik (mode cadangan CDN sudah memuatnya sendiri). --}}
@if(file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite('resources/js/charts.js')
@endif
