@props(['form'])

{{-- Ringkasan kesalahan di bagian atas modal; hanya tampil untuk form yang baru saja dikirim. --}}
@if($errors->any() && old('_restore') === $form)
    <div role="alert" class="rounded-xl border border-alert/30 bg-alert-wash p-3.5 text-sm text-ink">
        <p class="font-bold text-alert">Data belum tersimpan. Periksa {{ $errors->count() }} isian berikut:</p>
        <ul class="mt-1.5 list-disc pl-5 space-y-0.5 text-ink-soft">
            @foreach($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
